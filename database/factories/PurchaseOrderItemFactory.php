<?php
namespace Database\Factories;
use App\Models\Product; use App\Models\PurchaseOrder; use App\Models\PurchaseOrderItem; use Illuminate\Database\Eloquent\Factories\Factory;
class PurchaseOrderItemFactory extends Factory { protected $model=PurchaseOrderItem::class; public function definition():array{return ['purchase_order_id'=>PurchaseOrder::factory(),'product_id'=>Product::factory(),'quantity_ordered'=>2,'quantity_received'=>0,'unit_cost_cents'=>1000];} }
