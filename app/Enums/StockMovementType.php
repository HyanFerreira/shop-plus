<?php
namespace App\Enums;
enum StockMovementType:string { case PurchaseReceived='purchase_received'; case SaleReserved='sale_reserved'; case ReservationReleased='reservation_released'; case SaleCompleted='sale_completed'; case Returned='returned'; case Adjustment='adjustment'; }
