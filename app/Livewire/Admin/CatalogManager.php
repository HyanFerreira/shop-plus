<?php

namespace App\Livewire\Admin;

use App\Actions\Catalog\SaveCategory;
use App\Enums\CatalogStatus;
use App\Models\Category;
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

    public function render(): View
    {
        $categories = Category::withTrashed()->orderBy('name')->get();

        return view('livewire.admin.catalog-manager', compact('categories'));
    }

    private function resetCategoryForm(): void
    {
        $this->reset('categoryId', 'categoryName', 'categoryDescription', 'categoryStatus');
        $this->resetValidation();
    }
}
