<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('payment_expires_at')->nullable()->after('placed_at')->index();
            $table->timestamp('cancelled_at')->nullable()->after('payment_expires_at');
            $table->timestamp('refunded_at')->nullable()->after('cancelled_at');
        });
        DB::table('orders')->where('status', 'pending_payment')->update(['payment_expires_at' => now()->addMinutes(30)]);
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['payment_expires_at', 'cancelled_at', 'refunded_at']));
    }
};
