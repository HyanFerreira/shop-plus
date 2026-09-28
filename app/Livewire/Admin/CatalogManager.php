<?php

namespace App\Livewire\Admin;

use App\Actions\Catalog\SaveCategory;
use App\Actions\Catalog\SaveProduct;
use App\Domain\Catalog\Price;
use App\Enums\CatalogStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CatalogManager extends Component
{
    public ?int $categoryId = null;

    public string $categoryName = '';

    public string $categoryDescription = '';

    public string $categoryStatus = 'active';

    public ?int $productId = null;

    public ?int $productCategoryId = null;

    public string $productName = '';

    public string $productSku = '';

    public string $productDescription = '';

    public string $productPrice = '';

    public int $productWeight = 0;

    public int $productWidth = 0;

    public int $productHeight = 0;

    public int $productLength = 0;

    public string $productStatus = 'active';

    public function boot(): void
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
    }

    public function saveCategory(SaveCategory $saveCategory): void
    {
        $validated = $this->validate([
            'categoryName' => ['required', 'string', 'max:120'],
            'categoryDescription' => ['nullable', 'string', 'max:2000'],
            'categoryStatus' => ['required', Rule::enum(CatalogStatus::class)],
        ]);

        $saveCategory->execute(
            Auth::user(),
            $this->categoryId,
            $validated['categoryName'],
            $validated['categoryDescription'] ?: null,
            CatalogStatus::from($validated['categoryStatus']),
        );

        $this->resetCategoryForm();
        session()->flash('catalogMessage', 'Categoria salva.');
    }

    public function editCategory(int $categoryId): void
    {
        $category = Category::query()->findOrFail($categoryId);

        $this->categoryId = $category->id;
        $this->categoryName = $category->name;
        $this->categoryDescription = $category->description ?? '';
        $this->categoryStatus = $category->status->value;
    }

    public function cancelCategoryEdit(): void
    {
        $this->resetCategoryForm();
    }

    public function deleteCategory(int $categoryId): void
    {
        Category::query()->findOrFail($categoryId)->delete();
        $this->resetCategoryForm();
        session()->flash('catalogMessage', 'Categoria removida.');
    }

    public function restoreCategory(int $categoryId): void
    {
        Category::onlyTrashed()->findOrFail($categoryId)->restore();
        session()->flash('catalogMessage', 'Categoria restaurada.');
    }

    public function saveProduct(SaveProduct $saveProduct): void
    {
        $validated = $this->validate([
            'productCategoryId' => ['required', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'productName' => ['required', 'string', 'max:160'],
            'productSku' => ['required', 'string', 'max:64'],
            'productDescription' => ['required', 'string', 'max:10000'],
            'productPrice' => ['required', 'regex:/^\d{1,10}(?:[,.]\d{1,2})?$/'],
            'productWeight' => ['required', 'integer', 'min:1', 'max:10000000'],
            'productWidth' => ['required', 'integer', 'min:1', 'max:1000000'],
            'productHeight' => ['required', 'integer', 'min:1', 'max:1000000'],
            'productLength' => ['required', 'integer', 'min:1', 'max:1000000'],
            'productStatus' => ['required', Rule::enum(CatalogStatus::class)],
        ]);

        $saveProduct->execute(Auth::user(), $this->productId, [
            'category_id' => $validated['productCategoryId'],
            'name' => $validated['productName'],
            'sku' => $validated['productSku'],
            'description' => $validated['productDescription'],
            'price' => $validated['productPrice'],
            'weight_grams' => $validated['productWeight'],
            'width_mm' => $validated['productWidth'],
            'height_mm' => $validated['productHeight'],
            'length_mm' => $validated['productLength'],
            'status' => $validated['productStatus'],
        ]);

        $this->resetProductForm();
        session()->flash('catalogMessage', 'Produto salvo.');
    }

    public function editProduct(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);

        $this->productId = $product->id;
        $this->productCategoryId = $product->category_id;
        $this->productName = $product->name;
        $this->productSku = $product->sku;
        $this->productDescription = $product->description;
        $this->productPrice = Price::fromCents($product->price_cents)->input();
        $this->productWeight = $product->weight_grams;
        $this->productWidth = $product->width_mm;
        $this->productHeight = $product->height_mm;
        $this->productLength = $product->length_mm;
        $this->productStatus = $product->status->value;
    }

    public function cancelProductEdit(): void
    {
        $this->resetProductForm();
    }

    public function deleteProduct(int $productId): void
    {
        Product::query()->findOrFail($productId)->delete();
        $this->resetProductForm();
        session()->flash('catalogMessage', 'Produto removido.');
    }

    public function restoreProduct(int $productId): void
    {
        Product::onlyTrashed()->findOrFail($productId)->restore();
        session()->flash('catalogMessage', 'Produto restaurado.');
    }

    public function render(): View
    {
        $categories = Category::withTrashed()->orderBy('name')->get();
        $products = Product::withTrashed()->with('category')->orderBy('name')->get();

        return view('livewire.admin.catalog-manager', compact('categories', 'products'));
    }

    private function resetCategoryForm(): void
    {
        $this->reset('categoryId', 'categoryName', 'categoryDescription', 'categoryStatus');
        $this->resetValidation();
    }

    private function resetProductForm(): void
    {
        $this->reset(
            'productId', 'productCategoryId', 'productName', 'productSku', 'productDescription',
            'productPrice', 'productWeight', 'productWidth', 'productHeight', 'productLength', 'productStatus',
        );
        $this->resetValidation();
    }
}
