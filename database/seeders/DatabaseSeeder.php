<?php

namespace Database\Seeders;

use App\Actions\Cart\AddCartItem;
use App\Actions\Checkout\PlaceOrder;
use App\Actions\Inventory\PlacePurchaseOrder;
use App\Actions\Inventory\ReceivePurchase;
use App\Actions\Inventory\SavePurchaseOrder;
use App\Actions\Inventory\SaveSupplier;
use App\Actions\Inventory\SaveSupplierProduct;
use App\Actions\Payment\ProcessPayment;
use App\Actions\PersonalData\SaveAddress;
use App\Actions\PersonalData\SaveCustomerProfile;
use App\Actions\PersonalData\SavePhone;
use App\Domain\Payment\FictitiousCardValidator;
use App\Domain\PersonalData\BlindIndex;
use App\Enums\CatalogStatus;
use App\Enums\PhoneType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = (string) env('DEMO_PASSWORD');
        if ($password === '') {
            $password = Str::password(20);
            $this->command?->warn('Senha local efêmera dos usuários demo: '.$password);
        }

        DB::transaction(function () use ($password) {
            $admin = $this->user('Administrador Fictício', 'admin@comercio.example.test', UserRole::Admin, $password);
            $customer = $this->user('Cliente Fictício Um', 'cliente@comercio.example.test', UserRole::Customer, $password);
            $secondCustomer = $this->user('Cliente Fictício Dois', 'cliente2@comercio.example.test', UserRole::Customer, $password);

            $this->personalData($customer, '529.982.247-25', '(11) 98765-4321', '01001-000', 'São Paulo');
            $this->personalData($secondCustomer, '111.444.777-35', '(21) 98765-4321', '20040-020', 'Rio de Janeiro');

            $category = Category::withTrashed()->updateOrCreate(
                ['slug' => 'demonstracao-academica'],
                ['name' => 'Demonstração acadêmica', 'description' => 'Categoria composta somente por itens fictícios.', 'status' => CatalogStatus::Active, 'deleted_at' => null],
            );

            $products = collect([
                ['sku' => 'DEMO-CADERNO', 'name' => 'Caderno Fictício', 'slug' => 'caderno-ficticio', 'price_cents' => 2590, 'weight_grams' => 400],
                ['sku' => 'DEMO-MOCHILA', 'name' => 'Mochila Fictícia', 'slug' => 'mochila-ficticia', 'price_cents' => 12990, 'weight_grams' => 900],
            ])->map(fn (array $data) => Product::withTrashed()->updateOrCreate(
                ['sku' => $data['sku']],
                [...$data, 'category_id' => $category->id, 'description' => 'Produto sintético para demonstração acadêmica.', 'width_mm' => 200, 'height_mm' => 100, 'length_mm' => 300, 'status' => CatalogStatus::Active, 'deleted_at' => null],
            ));

            foreach ($products as $product) {
                InventoryItem::updateOrCreate(['product_id' => $product->id], ['on_hand' => 25, 'reserved' => 0, 'minimum_level' => 5]);
            }

            $supplierHash = app(BlindIndex::class)->for('11222333000181');
            $supplier = Supplier::withTrashed()->where('document_hash', $supplierHash)->first();
            $supplier = app(SaveSupplier::class)->execute($admin, $supplier?->id, [
                'name' => 'Fornecedor Fictício Acadêmico', 'document' => '11.222.333/0001-81',
                'email' => 'fornecedor@comercio.example.test', 'phone' => '(11) 4000-0000',
                'address' => 'Rua Exclusivamente Fictícia, 100 - São Paulo/SP', 'active' => true,
            ]);
            foreach ($products as $index => $product) {
                app(SaveSupplierProduct::class)->execute($admin, $supplier->id, $product->id, 'FORN-DEMO-'.($index + 1), number_format($product->price_cents / 200, 2, ',', '.'));
            }

            if (! PurchaseOrder::query()->exists()) {
                $purchase = app(SavePurchaseOrder::class)->execute($admin, null, $supplier->id, [$products[0]->id => 5, $products[1]->id => 3]);
                app(PlacePurchaseOrder::class)->execute($admin, $purchase);
                app(ReceivePurchase::class)->execute($admin, $purchase, $purchase->items()->pluck('quantity_ordered', 'id')->all(), (string) Str::uuid());
            }

            if (! Order::query()->where('user_id', $customer->id)->exists()) {
                $address = $customer->addresses()->firstOrFail();
                app(AddCartItem::class)->execute($customer, $products[0], 1);
                app(PlaceOrder::class)->execute($customer, $address->id, 'economy', (string) Str::uuid());

                app(AddCartItem::class)->execute($customer, $products[1], 1);
                $paidOrder = app(PlaceOrder::class)->execute($customer, $address->id, 'express', (string) Str::uuid());
                app(ProcessPayment::class)->execute($customer, $paidOrder, FictitiousCardValidator::APPROVED_VISA, '123', now()->month, now()->year + 1, (string) Str::uuid());
            }

            $this->command?->info('Usuários fictícios: admin@comercio.example.test e cliente@comercio.example.test');
        });
    }

    private function user(string $name, string $email, UserRole $role, string $password): User
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->forceFill(['name' => $name, 'password' => Hash::make($password), 'role' => $role, 'status' => UserStatus::Active, 'email_verified_at' => now()])->save();

        return $user;
    }

    private function personalData(User $user, string $cpf, string $phone, string $postalCode, string $city): void
    {
        app(SaveCustomerProfile::class)->execute($user, $cpf, '1990-01-01');
        $existingPhone = $user->phones()->first();
        app(SavePhone::class)->execute($user, $existingPhone?->id, $phone, PhoneType::Mobile, true);
        $existingAddress = $user->addresses()->first();
        app(SaveAddress::class)->execute($user, $existingAddress?->id, [
            'label' => 'Endereço fictício', 'recipient' => $user->name, 'postal_code' => $postalCode,
            'street' => 'Rua de Demonstração Fictícia', 'number' => '100', 'complement' => 'Ambiente acadêmico',
            'district' => 'Bairro Fictício', 'city' => $city, 'state' => $city === 'São Paulo' ? 'SP' : 'RJ', 'is_primary' => true,
        ]);
    }
}
