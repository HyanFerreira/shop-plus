<?php
namespace App\Models;
use App\Enums\StockMovementType; use Database\Factories\StockMovementFactory; use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class StockMovement extends Model { use HasFactory; protected $fillable=['inventory_item_id','type','quantity_delta','idempotency_key','reference_type','reference_id','reason','actor_id']; protected function casts():array{return ['type'=>StockMovementType::class,'quantity_delta'=>'integer'];} public function inventoryItem():BelongsTo{return $this->belongsTo(InventoryItem::class);} }
