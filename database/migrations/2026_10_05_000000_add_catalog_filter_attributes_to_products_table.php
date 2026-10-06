<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('brand', 80)->default('Genérica')->index()->after('sku');
            $table->decimal('rating_average', 2, 1)->default(0)->after('price_cents');
            $table->unsignedInteger('rating_count')->default(0)->after('rating_average');
            $table->boolean('free_shipping')->default(false)->index()->after('rating_count');
            $table->boolean('express_shipping')->default(false)->index()->after('free_shipping');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['brand', 'rating_average', 'rating_count', 'free_shipping', 'express_shipping']);
        });
    }
};
