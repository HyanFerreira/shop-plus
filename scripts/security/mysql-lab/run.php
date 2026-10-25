<?php

require __DIR__.'/LabBootstrap.php';

use App\Actions\Cart\AddCartItem;
use App\Actions\Checkout\PlaceOrder;
use App\Actions\Payment\ProcessPayment;
use App\Domain\Payment\FictitiousCardValidator;
use App\Models\Address;
use App\Models\Cart;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$descriptor = $argv[2];
$lab = sdsLabDescriptor($descriptor);
$app = sdsLabApplication($descriptor, true);
DB::connection()->getPdo(); // Server UUID is checked before ANY migration or operation.

function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException('Lab assertion failed: '.$message);
    }
}

function rootQuery(array $lab, string $sql): string
{
    static $verified = [];
    if (! isset($verified[$lab['container_id']])) {
        $inspect = proc_open(['docker', 'inspect', $lab['container_id']], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $streams);
        $metadata = json_decode(stream_get_contents($streams[1]), true);
        stream_get_contents($streams[2]);
        fclose($streams[1]);
        fclose($streams[2]);
        check(proc_close($inspect) === 0 && ($metadata[0]['Config']['Labels']['shopplus.sds7c'] ?? '') === $lab['owner'], 'container ownership before privileged lab query');
        $verified[$lab['container_id']] = true;
    }
    $command = ['docker', 'exec', $lab['container_id'], 'sh', '-c',
        'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot -Nse "$1"', 'lab-query', $sql];
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $result = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    check(proc_close($process) === 0, 'lab coordinator query');

    return trim($result);
}

function concurrent(array $lab, string $descriptor, string $name, array $jobs, string $table, int $id): array
{
    check(in_array($table, ['inventory_items', 'carts', 'orders'], true), 'coordinator lock table');
    $directory = $lab['root'].'/jobs-'.$name.'-'.bin2hex(random_bytes(3));
    mkdir($directory, 0700);
    DB::beginTransaction();
    DB::table($table)->where('id', $id)->lockForUpdate()->firstOrFail();
    $processes = [];
    try {
        foreach ($jobs as $index => $job) {
            $jobPath = $directory.'/'.$index.'.json';
            file_put_contents($jobPath, json_encode($job, JSON_THROW_ON_ERROR));
            $processes[] = proc_open([PHP_BINARY, __DIR__.'/worker.php', $descriptor, $jobPath],
                [0 => ['pipe', 'r'], 1 => ['file', $jobPath.'.stdout', 'w'], 2 => ['file', $jobPath.'.stderr', 'w']], $pipes);
            fclose($pipes[0]);
        }
        $deadline = microtime(true) + 15;
        while (! is_file($directory.'/0.json.ready') || ! is_file($directory.'/1.json.ready')) {
            check(microtime(true) < $deadline, 'workers ready before deadline');
            usleep(20000);
        }
        $connectionIds = [];
        foreach ([0, 1] as $index) {
            $connectionIds[] = (int) json_decode(file_get_contents($directory.'/'.$index.'.json.ready'), true)['connection_id'];
        }
        check(count(array_unique($connectionIds)) === 2, 'two different MySQL connections');
        file_put_contents($directory.'/go', 'go');
        $waiting = 0;
        while ($waiting !== 2 && microtime(true) < $deadline) {
            $waiting = (int) rootQuery($lab, "SELECT COUNT(*) FROM information_schema.innodb_trx WHERE trx_state='LOCK WAIT' AND trx_mysql_thread_id IN (".implode(',', $connectionIds).')');
            if ($waiting !== 2) {
                usleep(50000);
            }
        }
        check($waiting === 2, 'both real transactions observed simultaneously in LOCK WAIT');
        DB::commit();
        $deadline = microtime(true) + 20;
        while (! is_file($directory.'/0.json.result') || ! is_file($directory.'/1.json.result')) {
            check(microtime(true) < $deadline, 'workers finish before deadline');
            usleep(20000);
        }
        $results = [];
        foreach ([0, 1] as $index) {
            $results[] = json_decode(file_get_contents($directory.'/'.$index.'.json.result'), true, flags: JSON_THROW_ON_ERROR);
        }

        return ['case' => $name, 'connections' => $connectionIds, 'simultaneous_lock_waits' => $waiting, 'workers' => $results];
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        foreach ($processes as $process) {
            proc_close($process);
        }
    }
}

function fixture(int $stock, int $users = 1): array
{
    $product = Product::factory()->create(['price_cents' => 2500]);
    $inventory = InventoryItem::factory()->for($product)->create(['on_hand' => $stock, 'reserved' => 0]);
    $customers = [];
    foreach (range(1, $users) as $index) {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();
        app(AddCartItem::class)->execute($user, $product, 1);
        $customers[] = [$user, $address];
    }

    return [$product, $inventory, $customers];
}

function inventoryState(InventoryItem $inventory): array
{
    $inventory->refresh();

    return ['on_hand' => $inventory->on_hand, 'reserved' => $inventory->reserved];
}

try {
    if ($argv[1] === 'init') {
        check(DB::select('SHOW TABLES') === [], 'schema must be new and empty');
        check(Artisan::call('migrate', ['--force' => true]) === 0, 'migrations');
        check(Artisan::call('db:seed', ['--force' => true]) === 0, 'seeders');
        $before = DB::table('inventory_items')->orderBy('id')->get(['id', 'on_hand', 'reserved'])->toJson();
        check(Artisan::call('db:seed', ['--force' => true]) === 0, 'second seed');
        check($before === DB::table('inventory_items')->orderBy('id')->get(['id', 'on_hand', 'reserved'])->toJson(), 'seed preserves stock and reservations');
        $output = ['status' => 'PASS', 'mysql' => DB::selectOne('SELECT VERSION() AS version')->version,
            'server_uuid' => $lab['server_uuid'], 'counts' => [], 'reseed_preserves_inventory' => true];
        foreach (['users', 'products', 'suppliers', 'orders', 'payments', 'shipments', 'stock_movements'] as $table) {
            $output['counts'][$table] = DB::table($table)->count();
        }
        $output['catalog_slug'] = Product::where('sku', 'NIM-PHONE-01')->firstOrFail()->slug;
    } elseif ($argv[1] === 'concurrency') {
        $cases = [];
        [$product, $stock, $customers] = fixture(1, 2);
        $jobs = array_map(fn ($customer) => ['operation' => 'place', 'user_id' => $customer[0]->id,
            'address_id' => $customer[1]->id, 'key' => (string) Str::uuid()], $customers);
        $case = concurrent($lab, $descriptor, 'last-unit', $jobs, 'inventory_items', $stock->id);
        $statuses = array_column($case['workers'], 'status');
        sort($statuses);
        check($statuses === ['ok', 'validation'], 'only one last-unit winner');
        check(inventoryState($stock) === ['on_hand' => 1, 'reserved' => 1], 'last-unit stock');
        check(DB::table('order_items')->where('product_id', $product->id)->count() === 1, 'one last-unit order');
        $case['persisted_inventory'] = inventoryState($stock);
        $cases[] = $case;

        [$product, $stock, $customers] = fixture(3);
        [$user, $address] = $customers[0];
        $key = (string) Str::uuid();
        $job = ['operation' => 'place', 'user_id' => $user->id, 'address_id' => $address->id, 'key' => $key];
        $case = concurrent($lab, $descriptor, 'checkout-replay', [$job, $job], 'carts', Cart::where('user_id', $user->id)->value('id'));
        check(array_column($case['workers'], 'status') === ['ok', 'ok'], 'repeated checkout results');
        check($case['workers'][0]['id'] === $case['workers'][1]['id'], 'same checkout order');
        $order = Order::where('checkout_key', $key)->firstOrFail();
        check(Order::where('checkout_key', $key)->count() === 1 && $stock->fresh()->reserved === 1, 'one reservation for repeated checkout');
        $case['persisted_inventory'] = inventoryState($stock);
        $cases[] = $case;

        $job = ['operation' => 'pay', 'user_id' => $user->id, 'order_id' => $order->id, 'key' => (string) Str::uuid()];
        $case = concurrent($lab, $descriptor, 'payment-replay', [$job, $job], 'orders', $order->id);
        check(array_column($case['workers'], 'status') === ['ok', 'ok'], 'repeated payment results');
        check($case['workers'][0]['id'] === $case['workers'][1]['id'], 'same payment ID');
        check(DB::table('payments')->where('order_id', $order->id)->count() === 1, 'only one persisted payment');
        check(inventoryState($stock) === ['on_hand' => 2, 'reserved' => 0], 'single stock deduction');
        $case['persisted_inventory'] = inventoryState($stock);
        $case['persisted_payments'] = 1;
        $cases[] = $case;

        [$product, $stock, $customers] = fixture(3);
        [$user, $address] = $customers[0];
        $order = app(PlaceOrder::class)->execute($user, $address->id, 'pickup', (string) Str::uuid());
        $job = ['operation' => 'cancel', 'user_id' => $user->id, 'order_id' => $order->id];
        $case = concurrent($lab, $descriptor, 'reservation-release-replay', [$job, $job], 'orders', $order->id);
        check(array_column($case['workers'], 'status') === ['ok', 'ok'], 'repeated cancellation results');
        check(inventoryState($stock) === ['on_hand' => 3, 'reserved' => 0], 'reservation released once');
        check(DB::table('stock_movements')->where('reference_type', Order::class)->where('reference_id', $order->id)->where('type', 'reservation_released')->count() === 1, 'one release movement');
        $case['persisted_inventory'] = inventoryState($stock);
        $cases[] = $case;

        [$product, $stock, $customers] = fixture(3);
        [$user, $address] = $customers[0];
        $order = app(PlaceOrder::class)->execute($user, $address->id, 'pickup', (string) Str::uuid());
        Shipment::creating(fn () => throw new RuntimeException('Synthetic late failure'));
        try {
            app(ProcessPayment::class)->execute($user, $order, FictitiousCardValidator::APPROVED_VISA, '123', now()->month, now()->year + 1, (string) Str::uuid());
            throw new LogicException('Failure injection was not reached');
        } catch (RuntimeException $exception) {
            check($exception->getMessage() === 'Synthetic late failure', 'late rollback injection');
        } finally {
            Shipment::flushEventListeners();
        }
        check(DB::table('payments')->where('order_id', $order->id)->count() === 0, 'rollback payment');
        check(inventoryState($stock) === ['on_hand' => 3, 'reserved' => 1], 'rollback inventory');
        check($order->fresh()->status->value === 'pending_payment' && $order->statusHistories()->count() === 1, 'rollback order/history');
        $cases[] = ['case' => 'late-rollback', 'persisted_payments' => 0, 'persisted_inventory' => inventoryState($stock), 'status' => 'PASS'];
        check(DB::table('inventory_items')->whereColumn('reserved', '>', 'on_hand')->orWhere('on_hand', '<', 0)->orWhere('reserved', '<', 0)->count() === 0, 'global stock invariants');
        $output = ['status' => 'PASS', 'server_uuid' => $lab['server_uuid'], 'mysql' => DB::selectOne('SELECT VERSION() AS version')->version, 'cases' => $cases, 'negative_or_overreserved_rows' => 0];
    } elseif ($argv[1] === 'inspect') {
        $rehearsal = json_decode(file_get_contents($lab['root'].'/rehearsal.json'), true, flags: JSON_THROW_ON_ERROR);
        $user = User::where('email', $rehearsal['email'])->firstOrFail();
        $order = $user->orders()->where('public_number', $rehearsal['public_number'])->firstOrFail();
        $profile = $user->customerProfile()->firstOrFail();
        $phone = $user->phones()->firstOrFail();
        $address = $user->addresses()->firstOrFail();
        $payment = $order->payments()->firstOrFail();
        check(password_get_info($user->password)['algoName'] === 'argon2id', 'password hash');
        check($profile->getRawOriginal('cpf_encrypted') !== $profile->cpf_encrypted && strlen($profile->cpf_hash) === 64, 'CPF encryption/index');
        check($phone->getRawOriginal('number_encrypted') !== $phone->number_encrypted, 'phone encryption');
        check($address->getRawOriginal('street_encrypted') !== $address->street_encrypted, 'address encryption');
        check($order->items()->get()->sum(fn ($item) => $item->unit_price_cents * $item->quantity) === $order->subtotal_cents, 'server item total');
        check($order->subtotal_cents + $order->shipping_cents === $order->total_cents && $payment->amount_cents === $order->total_cents, 'server freight/payment total');
        check($order->payments()->count() === 1 && $payment->status->value === 'authorized', 'HTTP payment replay has one effect');
        $columns = array_column(DB::select('SHOW COLUMNS FROM payments'), 'Field');
        check(! in_array('cvv', $columns, true) && ! in_array('card_number', $columns, true), 'no CVV/PAN columns');
        check(! str_contains(json_encode($payment->getAttributes()), FictitiousCardValidator::APPROVED_VISA), 'no stored full test card');
        check($order->shipment()->count() === 1 && $order->shipment->events()->count() === 1, 'delivery persisted');
        check(DB::table('addresses')->where('id', 1)->exists(), 'foreign address manipulation did not delete it');
        check(DB::table('inventory_items')->whereColumn('reserved', '>', 'on_hand')->orWhere('on_hand', '<', 0)->orWhere('reserved', '<', 0)->count() === 0, 'all stock rows coherent');
        if (! isset($lab['reader_password'])) {
            $lab['reader_password'] = bin2hex(random_bytes(24));
            file_put_contents($descriptor, json_encode($lab, JSON_PRETTY_PRINT));
        }
        rootQuery($lab, "CREATE USER IF NOT EXISTS 'sds_reader'@'%' IDENTIFIED BY '".$lab['reader_password']."'; GRANT SELECT, SHOW VIEW ON `".$lab['database']."`.* TO 'sds_reader'@'%'");
        $reader = new PDO('mysql:host=127.0.0.1;port=33077;dbname='.$lab['database'].';charset=utf8mb4', 'sds_reader', $lab['reader_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_PERSISTENT => false]);
        check($reader->query('SELECT @@server_uuid')->fetchColumn() === $lab['server_uuid'], 'reader server identity');
        check((int) $reader->query('SELECT COUNT(*) FROM orders')->fetchColumn() > 0, 'Workbench account can inspect records');
        $grants = $reader->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN);
        check(! preg_match('/ALL PRIVILEGES|INSERT|UPDATE|DELETE|CREATE|DROP|ALTER/', implode(' ', $grants)), 'reader lacks write/admin grants');
        $output = ['status' => 'PASS', 'user_id' => $user->id, 'order_id' => $order->id, 'public_number' => $order->public_number,
            'password_algorithm' => 'argon2id', 'cpf_encrypted_and_hmac_indexed' => true,
            'phone_and_address_encrypted' => true, 'subtotal_cents' => $order->subtotal_cents,
            'shipping_cents' => $order->shipping_cents, 'total_cents' => $order->total_cents,
            'persisted_payments_after_http_replay' => 1, 'payment_status' => $payment->status->value,
            'cvv_or_full_pan_columns' => false, 'shipment_status' => $order->shipment->status->value,
            'stock' => $order->items()->get()->map(fn ($item) => [
                'product_id' => $item->product_id, 'purchased_quantity' => $item->quantity,
                'on_hand' => $item->product->inventoryItem->on_hand, 'reserved' => $item->product->inventoryItem->reserved,
            ])->all(), 'readonly_account' => 'sds_reader', 'readonly_connection_verified' => true,
            'workbench_gui' => 'not opened; same account verified over MySQL protocol'];
    } else {
        throw new RuntimeException('Unknown lab command');
    }
    echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $exception) {
    // Never emit SQL bindings, credentials, synthetic card input or PII in reports.
    echo json_encode(['status' => 'FAIL', 'class' => $exception::class,
        'assertion' => str_starts_with($exception->getMessage(), 'Lab assertion failed:') ? $exception->getMessage() : null,
        'file' => basename($exception->getFile()), 'line' => $exception->getLine()]).PHP_EOL;
    exit(1);
}
