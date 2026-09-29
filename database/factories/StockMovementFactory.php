<?php
namespace Database\Factories;
use App\Enums\StockMovementType; use App\Models\InventoryItem; use App\Models\StockMovement; use Illuminate\Database\Eloquent\Factories\Factory; use Illuminate\Support\Str;
class StockMovementFactory extends Factory { protected $model=StockMovement::class; public function definition():array{return ['inventory_item_id'=>InventoryItem::factory(),'type'=>StockMovementType::Adjustment,'quantity_delta'=>1,'idempotency_key'=>(string)Str::uuid(),'reason'=>'Ajuste fictício de teste'];} }
