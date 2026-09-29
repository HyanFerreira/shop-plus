<?php

namespace App\Actions\Catalog;

use App\Domain\Catalog\Price;
use App\Enums\CatalogStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\SecurityAudit;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaveProduct
{
    /** @param array<string, int|string> $data */
    public function execute(User $actor, ?int $productId, array $data): Product
    {
        abort_unless($actor->isAdmin(), 403);

        $product = $productId ? Product::query()->findOrFail($productId) : new Product;
        Category::query()->findOrFail((int) $data['category_id']);

        $slug = Str::slug((string) $data['name']);
        $sku = Str::upper(trim((string) $data['sku']));
        $errors = [];

        if ($slug === '' || Product::withTrashed()->where('slug', $slug)->when($product->exists, fn ($query) => $query->whereKeyNot($product->getKey()))->exists()) {
            $errors['productName'] = 'Não foi possível usar esse nome de produto.';
        }

        if (! preg_match('/^[A-Z0-9._-]+$/', $sku)
            || Product::withTrashed()->where('sku', $sku)->when($product->exists, fn ($query) => $query->whereKeyNot($product->getKey()))->exists()) {
            $errors['productSku'] = 'Não foi possível usar esse SKU.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        try {
            $product->fill([
                'category_id' => (int) $data['category_id'],
                'name' => trim((string) $data['name']),
                'slug' => $slug,
                'sku' => $sku,
                'description' => trim((string) $data['description']),
                'price_cents' => Price::fromInput((string) $data['price'])->cents(),
                'weight_grams' => (int) $data['weight_grams'],
                'width_mm' => (int) $data['width_mm'],
                'height_mm' => (int) $data['height_mm'],
                'length_mm' => (int) $data['length_mm'],
                'status' => CatalogStatus::from((string) $data['status']),
            ])->save();
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                throw ValidationException::withMessages([
                    'productName' => 'Não foi possível salvar o produto.',
                    'productSku' => 'Não foi possível salvar o produto.',
                ]);
            }

            throw $exception;
        }

        $product->refresh();
        app(SecurityAudit::class)->record($actor, $productId ? 'product.updated' : 'product.created', $product);

        return $product;
    }
}
