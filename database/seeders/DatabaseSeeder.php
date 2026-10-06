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
            $this->personalData($customer, '529.982.247-25', '(11) 98765-4321', '01001-000', 'São Paulo', 'SP');
            $this->personalData($secondCustomer, '111.444.777-35', '(21) 98765-4321', '20040-020', 'Rio de Janeiro', 'RJ');

            $categories = collect([
                ['slug' => 'eletronicos', 'name' => 'Eletrônicos'], ['slug' => 'informatica', 'name' => 'Informática'],
                ['slug' => 'casa-decoracao', 'name' => 'Casa e Decoração'], ['slug' => 'moda', 'name' => 'Moda'],
                ['slug' => 'esportes', 'name' => 'Esportes'], ['slug' => 'beleza', 'name' => 'Beleza'],
            ])->mapWithKeys(fn (array $data) => [$data['slug'] => Category::withTrashed()->updateOrCreate(
                ['slug' => $data['slug']], ['name' => $data['name'], 'description' => 'Categoria de demonstração com produtos exclusivamente fictícios.', 'status' => CatalogStatus::Active, 'deleted_at' => null],
            )]);

            $catalog = [
                ['NIM-PHONE-01', 'Aurora Phone X', 'aurora-phone-x', 'Núcleo', 'eletronicos', 424900, 4.8, 534, true, true],
                ['LUM-FONE-01', 'Fone sem fio Lumina', 'fone-sem-fio-lumina', 'Lumina', 'eletronicos', 188900, 4.8, 367, true, true],
                ['NIM-WATCH-04', 'Relógio Pulse 4', 'relogio-pulse-4', 'Núcleo', 'eletronicos', 59990, 4.6, 178, false, true],
                ['AUR-SOM-06', 'Caixa de som Orion 6', 'caixa-de-som-orion-6', 'Aurora', 'eletronicos', 69990, 4.8, 421, true, false],
                ['VET-NOTE-15', 'Notebook Vértice 15', 'notebook-vertice-15', 'Vértice', 'informatica', 279900, 4.8, 521, true, true],
                ['LUM-MON-29', 'Monitor Lumina Wide 29', 'monitor-lumina-wide-29', 'Lumina', 'informatica', 129900, 4.7, 185, false, true],
                ['VET-KEY-01', 'Teclado Mecânico Atlas', 'teclado-mecanico-atlas', 'Vértice', 'informatica', 29990, 4.6, 142, true, false],
                ['NIM-MOUSE-02', 'Mouse Precision 2', 'mouse-precision-2', 'Núcleo', 'informatica', 34990, 4.7, 288, true, true],
                ['CAS-CAF-01', 'Cafeteira Brisa Mini', 'cafeteira-brisa-mini', 'Brisa', 'casa-decoracao', 34990, 4.7, 412, true, false],
                ['CAS-LUM-01', 'Luminária Nuvem', 'luminaria-nuvem', 'Brisa', 'casa-decoracao', 15990, 4.5, 95, false, true],
                ['MOD-TEN-01', 'Tênis Fluxo Urbano', 'tenis-fluxo-urbano', 'Fluxo', 'moda', 49990, 4.7, 210, true, true],
                ['MOD-MOC-01', 'Mochila Horizonte', 'mochila-horizonte', 'Horizonte', 'moda', 18990, 4.5, 124, false, false],
                ['ESP-HAL-01', 'Kit Halteres Movimento', 'kit-halteres-movimento', 'Movimento', 'esportes', 27990, 4.6, 88, true, false],
                ['ESP-TAP-01', 'Tapete de Yoga Serena', 'tapete-yoga-serena', 'Serena', 'esportes', 11990, 4.8, 202, true, true],
                ['BEL-PER-01', 'Perfume Essência Clara', 'perfume-essencia-clara', 'Essência', 'beleza', 24990, 4.7, 156, false, true],
                ['BEL-KIT-01', 'Kit Cuidado Diário', 'kit-cuidado-diario', 'Essência', 'beleza', 13990, 4.5, 74, true, false],
            ];

            $products = collect($catalog)->map(function (array $item) use ($categories) {
                [$sku, $name, $slug, $brand, $categorySlug, $price, $rating, $ratingCount, $freeShipping, $expressShipping] = $item;
                $product = Product::withTrashed()->updateOrCreate(['sku' => $sku], [
                    'category_id' => $categories[$categorySlug]->id, 'name' => $name, 'slug' => $slug, 'brand' => $brand,
                    'description' => $name.' é um produto totalmente fictício, cadastrado para demonstração acadêmica do sistema de comércio.',
                    'price_cents' => $price, 'rating_average' => $rating, 'rating_count' => $ratingCount,
                    'free_shipping' => $freeShipping, 'express_shipping' => $expressShipping,
                    'weight_grams' => 850, 'width_mm' => 240, 'height_mm' => 120, 'length_mm' => 320,
                    'status' => CatalogStatus::Active, 'deleted_at' => null,
                ]);
                InventoryItem::updateOrCreate(['product_id' => $product->id], ['on_hand' => 12 + ($product->id % 24), 'reserved' => 0, 'minimum_level' => 5]);
                return $product;
            })->values();

            $supplierHash = app(BlindIndex::class)->for('11222333000181');
            $supplier = Supplier::withTrashed()->where('document_hash', $supplierHash)->first();
            $supplier = app(SaveSupplier::class)->execute($admin, $supplier?->id, [
                'name' => 'Distribuidora Fictícia Horizonte', 'document' => '11.222.333/0001-81',
                'email' => 'fornecedor@comercio.example.test', 'phone' => '(11) 4000-0000',
                'address' => 'Rua Exclusivamente Fictícia, 100 - São Paulo/SP', 'active' => true,
            ]);
            foreach ($products as $index => $product) {
                app(SaveSupplierProduct::class)->execute($admin, $supplier->id, $product->id, 'FORN-DEMO-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT), number_format($product->price_cents / 200, 2, '.', ''));
            }

            if (! PurchaseOrder::query()->exists()) {
                $purchase = app(SavePurchaseOrder::class)->execute($admin, null, $supplier->id, $products->take(3)->mapWithKeys(fn (Product $product) => [$product->id => 4])->all());
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

    private function personalData(User $user, string $cpf, string $phone, string $postalCode, string $city, string $state): void
    {
        app(SaveCustomerProfile::class)->execute($user, $cpf, '1990-01-01');
        app(SavePhone::class)->execute($user, $user->phones()->first()?->id, $phone, PhoneType::Mobile, true);
        app(SaveAddress::class)->execute($user, $user->addresses()->first()?->id, ['label' => 'Endereço fictício', 'recipient' => $user->name, 'postal_code' => $postalCode, 'street' => 'Rua de Demonstração Fictícia', 'number' => '100', 'complement' => 'Ambiente acadêmico', 'district' => 'Bairro Fictício', 'city' => $city, 'state' => $state, 'is_primary' => true]);
    }
}
