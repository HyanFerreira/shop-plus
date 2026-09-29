<?php

namespace App\Actions\Catalog;

use App\Enums\CatalogStatus;
use App\Models\Category;
use App\Models\User;
use App\Support\SecurityAudit;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaveCategory
{
    public function execute(
        User $actor,
        ?int $categoryId,
        string $name,
        ?string $description,
        CatalogStatus $status,
    ): Category {
        abort_unless($actor->isAdmin(), 403);

        $category = $categoryId ? Category::query()->findOrFail($categoryId) : new Category;
        $slug = Str::slug($name);

        if ($slug === '' || Category::withTrashed()->where('slug', $slug)->whereKeyNot($category->getKey())->exists()) {
            throw $this->validationFailure();
        }

        try {
            $category->fill([
                'name' => trim($name),
                'slug' => $slug,
                'description' => $description ? trim($description) : null,
                'status' => $status,
            ])->save();
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                throw $this->validationFailure();
            }

            throw $exception;
        }

        $category->refresh();
        app(SecurityAudit::class)->record($actor, $categoryId ? 'category.updated' : 'category.created', $category);

        return $category;
    }

    private function validationFailure(): ValidationException
    {
        return ValidationException::withMessages([
            'categoryName' => 'Não foi possível usar esse nome de categoria.',
        ]);
    }
}
