<?php

require __DIR__.'/LabBootstrap.php';

use App\Actions\Checkout\CancelOrder;
use App\Actions\Checkout\PlaceOrder;
use App\Actions\Payment\ProcessPayment;
use App\Domain\Payment\FictitiousCardValidator;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

$jobFile = $argv[2];
$job = json_decode(file_get_contents($jobFile), true, flags: JSON_THROW_ON_ERROR);
try {
    sdsLabApplication($argv[1], true);
    $connection = (int) DB::selectOne('SELECT CONNECTION_ID() AS id')->id;
    file_put_contents($jobFile.'.ready', json_encode(['connection_id' => $connection]));
    $deadline = microtime(true) + 20;
    while (! is_file(dirname($jobFile).'/go')) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Lab start barrier timed out.');
        }
        usleep(10000);
    }
    $started = microtime(true);
    $user = User::findOrFail($job['user_id']);
    $result = match ($job['operation']) {
        'place' => app(PlaceOrder::class)->execute($user, $job['address_id'], 'pickup', $job['key']),
        'pay' => app(ProcessPayment::class)->execute($user, Order::findOrFail($job['order_id']),
            FictitiousCardValidator::APPROVED_VISA, '123', now()->month, now()->year + 1, $job['key']),
        'cancel' => app(CancelOrder::class)->execute($user, Order::findOrFail($job['order_id'])),
        default => throw new RuntimeException('Unknown lab operation.'),
    };
    $output = ['status' => 'ok', 'id' => $result->id];
} catch (ValidationException $exception) {
    $output = ['status' => 'validation', 'fields' => array_keys($exception->errors())];
} catch (Throwable $exception) {
    $output = ['status' => 'unexpected_error', 'class' => $exception::class];
}
$output['connection_id'] = $connection ?? null;
$output['started'] = $started ?? null;
$output['finished'] = microtime(true);
file_put_contents($jobFile.'.result', json_encode($output, JSON_THROW_ON_ERROR));
exit($output['status'] === 'unexpected_error' ? 1 : 0);
