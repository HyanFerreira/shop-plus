<?php
namespace Database\Factories;
use App\Models\InventoryItem; use App\Models\Product; use Illuminate\Database\Eloquent\Factories\Factory;
class InventoryItemFactory extends Factory { protected $model=InventoryItem::class; public function definition():array{return ['product_id'=>Product::factory(),'on_hand'=>0,'reserved'=>0,'minimum_level'=>0];} }
