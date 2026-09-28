<?php

namespace Database\Factories;

use App\Enums\CatalogStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $sequence = $this->faker->unique()->numerify('########');
        $name = 'Produto fictício '.$sequence;

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'sku' => 'TEST-'.$sequence,
            'description' => 'Produto sintético para demonstração acadêmica.',
            'price_cents' => $this->faker->numberBetween(100, 100_000),
            'weight_grams' => $this->faker->numberBetween(50, 20_000),
            'width_mm' => $this->faker->numberBetween(10, 1_000),
            'height_mm' => $this->faker->numberBetween(10, 1_000),
            'length_mm' => $this->faker->numberBetween(10, 1_000),
            'status' => CatalogStatus::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => CatalogStatus::Inactive]);
    }
}
