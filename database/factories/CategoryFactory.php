<?php

namespace Database\Factories;

use App\Enums\CatalogStatus;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Category> */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = 'Categoria fictícia '.$this->faker->unique()->numerify('#####');

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => 'Descrição sintética para testes automatizados.',
            'status' => CatalogStatus::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => CatalogStatus::Inactive]);
    }
}
