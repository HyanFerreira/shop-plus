<?php

namespace App\Livewire\Admin;

use App\Actions\Catalog\DeleteProductImage;
use App\Actions\Catalog\SaveCategory;
use App\Actions\Catalog\SaveProduct;
use App\Actions\Catalog\StoreProductImage;
use App\Domain\Catalog\Price;
use App\Enums\CatalogStatus;
use App\Models\Category;
use App\Models\Product;
use App\Support\SecurityAudit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class CatalogManager extends Component
{
    use WithFileUploads;

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

    public ?int $imageProductId = null;

    public $productImage = null;

    public string $imageAltText = '';

    public int $imageSortOrder = 0;

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
        $category = Category::query()->findOrFail($categoryId);
        $category->delete();
        app(SecurityAudit::class)->record(Auth::user(), 'category.deleted', $category);
        $this->resetCategoryForm();
        session()->flash('catalogMessage', 'Categoria removida.');
    }

    public function restoreCategory(int $categoryId): void
    {
        $category = Category::onlyTrashed()->findOrFail($categoryId);
        $category->restore();
        app(SecurityAudit::class)->record(Auth::user(), 'category.restored', $category);
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
        $product = Product::query()->findOrFail($productId);
        $product->delete();
        app(SecurityAudit::class)->record(Auth::user(), 'product.deleted', $product);
        $this->resetProductForm();
        session()->flash('catalogMessage', 'Produto removido.');
    }

    public function restoreProduct(int $productId): void
    {
        $product = Product::onlyTrashed()->findOrFail($productId);
        $product->restore();
        app(SecurityAudit::class)->record(Auth::user(), 'product.restored', $product);
        session()->flash('catalogMessage', 'Produto restaurado.');
    }

    public function saveProductImage(StoreProductImage $storeProductImage): void
    {
        $validated = $this->validate([
            'imageProductId' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'productImage' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'imageAltText' => ['nullable', 'string', 'max:180'],
            'imageSortOrder' => ['required', 'integer', 'min:0', 'max:65535'],
        ]);

        $storeProductImage->execute(
            Auth::user(),
            $validated['imageProductId'],
            $validated['productImage'],
            $validated['imageAltText'] ?: null,
            $validated['imageSortOrder'],
        );

        $this->reset('imageProductId', 'productImage', 'imageAltText', 'imageSortOrder');
        $this->resetValidation();
        session()->flash('catalogMessage', 'Imagem salva.');
    }

    public function deleteProductImage(DeleteProductImage $deleteProductImage, int $imageId): void
    {
        $deleteProductImage->execute(Auth::user(), $imageId);
        session()->flash('catalogMessage', 'Imagem removida.');
    }

    public function render(): View
    {
        $categories = Category::withTrashed()->orderBy('name')->get();
        $products = Product::withTrashed()->with(['category', 'images'])->orderBy('name')->get();

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
