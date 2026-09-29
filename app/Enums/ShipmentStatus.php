<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case AwaitingProcessing = 'awaiting_processing';
    case Preparing = 'preparing';
    case Shipped = 'shipped';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Returned = 'returned';
    case Cancelled = 'cancelled';
}
