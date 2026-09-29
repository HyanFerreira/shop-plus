<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount_cents');
            $table->string('status', 30)->index();
            $table->uuid('token')->unique();
            $table->string('brand', 30);
            $table->char('last_four', 4);
            $table->string('authorization_code', 40)->nullable();
            $table->uuid('idempotency_key')->unique();
            $table->timestamp('processed_at');
            $table->timestamps();
            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
