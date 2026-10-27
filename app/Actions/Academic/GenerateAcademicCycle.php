<?php

namespace App\Actions\Academic;

use App\Actions\Cart\AddCartItem;
use App\Actions\Checkout\PlaceOrder;
use App\Actions\Payment\ProcessPayment;
use App\Actions\PersonalData\SaveAddress;
use App\Domain\Academic\TransactionOutcomeSelector;
use App\Domain\Payment\FictitiousCardValidator;
use App\Enums\CatalogStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AcademicCycle;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Faker\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class GenerateAcademicCycle
{
    public function execute(): AcademicCycle
    {
        // Absolute UTC window: manual retries and the scheduler share the same key.
        $slot = (string) intdiv(now()->getTimestamp(), 300);

        return DB::transaction(function () use ($slot) {
            // Unique insert serializes concurrent first runs before the locking read.
            DB::table('academic_cycles')->insertOrIgnore([
                'cycle_key' => $slot, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $cycle = AcademicCycle::where('cycle_key', $slot)->lockForUpdate()->firstOrFail();
            if ($cycle->completed_at !== null) {
                return $cycle;
            }

            $outcome = app(TransactionOutcomeSelector::class)->choose();
            $faker = Factory::create('pt_BR');

            $user = new User;
            $user->forceFill([
                'name' => $faker->firstName().' '.$faker->lastName(),
                'email' => 'auto-'.$slot.'@comercio.example.test',
                'password' => Str::password(40),
                'role' => UserRole::Customer,
                'status' => UserStatus::Active,
                'is_automated_demo' => true,
            ])->save();

            $category = Category::active()->orderBy('id')->first()
                ?? Category::create([
                    'name' => 'Demonstração acadêmica', 'slug' => 'demonstracao-academica-'.$slot,
                    'description' => 'Produtos exclusivamente fictícios.', 'status' => CatalogStatus::Active,
                ]);
            $product = Product::create([
                'category_id' => $category->id, 'name' => 'Produto Fictício Automático '.$slot,
                'slug' => 'produto-automatico-'.$slot, 'sku' => 'AUTO-'.$slot,
                'brand' => 'ShopPlus Acadêmico', 'description' => 'Produto fictício criado para apresentação acadêmica.',
                'price_cents' => 2500, 'weight_grams' => 100, 'width_mm' => 100,
                'height_mm' => 100, 'length_mm' => 100, 'status' => CatalogStatus::Active,
            ]);
            $product->forceFill(['is_automated_demo' => true])->save();
            $product->inventoryItem()->create(['on_hand' => 10, 'reserved' => 0, 'minimum_level' => 2]);
            // Reuse a repository-owned illustration; deterministic path is safe on retries.
            $path = 'products/automation/'.$slot.'.svg';
            $illustration = file_get_contents(public_path('images/demo-products/phone.svg'));
            if ($illustration === false || ! Storage::disk('public')->put($path, $illustration)) {
                throw new RuntimeException('Não foi possível gravar a ilustração acadêmica.');
            }
            $product->images()->create(['path' => $path, 'alt_text' => 'Ilustração de produto fictício acadêmico', 'sort_order' => 0]);

            $order = null;
            $selection = app(SelectSyntheticPurchase::class);
            $buyer = $selection->customer();
            $selectedProduct = $selection->product();
            try {
                // Savepoint keeps a rejected purchase from leaving an address/cart/reservation.
                $order = DB::transaction(function () use ($buyer, $selectedProduct, $outcome) {
                    if (! $buyer->is_automated_demo || ! $buyer->isActive() || ! $selectedProduct?->is_automated_demo) {
                        throw ValidationException::withMessages(['purchase' => 'Compra acadêmica indisponível.']);
                    }
                    // Serialize the buyer before inspecting its address/cart; never buy a
                    // cart that was filled separately, even if this is a synthetic account.
                    $buyer = User::whereKey($buyer->id)->where('is_automated_demo', true)
                        ->where('status', UserStatus::Active)->lockForUpdate()->first();
                    if (! $buyer || $buyer->cart?->items()->exists()) {
                        throw ValidationException::withMessages(['purchase' => 'Compra acadêmica indisponível.']);
                    }
                    $address = $buyer->addresses()->oldest('id')->first();
                    $address ??= app(SaveAddress::class)->execute($buyer, null, [
                        'label' => 'Endereço fictício', 'recipient' => $buyer->name,
                        'postal_code' => '01001-000', 'street' => 'Rua Fictícia de Demonstração',
                        'number' => '100', 'complement' => 'Simulação acadêmica', 'district' => 'Bairro Fictício',
                        'city' => 'São Paulo', 'state' => 'SP', 'is_primary' => true,
                    ]);
                    app(AddCartItem::class)->execute($buyer, $selectedProduct, 1);
                    $order = app(PlaceOrder::class)->execute($buyer, $address->id, 'pickup', (string) Str::uuid());
                    $order->forceFill(['is_automated_demo' => true])->save();
                    if ($outcome !== 'pending') {
                        $number = $outcome === 'declined' ? FictitiousCardValidator::DECLINED_VISA : FictitiousCardValidator::APPROVED_VISA;
                        app(ProcessPayment::class)->execute($buyer, $order, $number, '123', now()->month, now()->year + 1, (string) Str::uuid());
                    }

                    return $order;
                });
            } catch (ValidationException) {
                // Only an expected business rejection skips purchase. Other errors retry the cycle.
            }

            $cycle->update([
                'user_id' => $user->id, 'buyer_id' => $buyer->id, 'product_id' => $product->id, 'order_id' => $order?->id,
                'outcome' => $outcome,
                'purchase_status' => $order ? 'completed' : 'skipped', 'completed_at' => now(),
            ]);

            return $cycle;
        }, 3);
    }
}
