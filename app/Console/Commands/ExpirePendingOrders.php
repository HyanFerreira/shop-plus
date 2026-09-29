<?php

namespace App\Console\Commands;

use App\Actions\Checkout\ExpireOrder;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Console\Command;

class ExpirePendingOrders extends Command
{
    protected $signature = 'orders:expire';

    protected $description = 'Cancela pedidos fictícios cujo prazo de pagamento expirou';

    public function handle(ExpireOrder $action): int
    {
        $count = 0;
        Order::query()->where('status', OrderStatus::PendingPayment)->where('payment_expires_at', '<=', now())->orderBy('id')->chunkById(100, function ($orders) use ($action, &$count) {
            foreach ($orders as $order) {
                $action->execute($order);
                $count++;
            }
        });
        $this->info("Pedidos expirados: $count");

        return self::SUCCESS;
    }
}
