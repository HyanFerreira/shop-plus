<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_automated_demo')->default(false);
            $table->index(['is_automated_demo', 'created_at', 'id'], 'users_automation_created_index');
            $table->index('created_at', 'users_created_at_index');
        });

        Schema::create('academic_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('cycle_key', 32)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('buyer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purchase_status', 16)->default('pending');
            $table->string('outcome', 16)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        foreach (['products', 'orders'] as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->boolean('is_automated_demo')->default(false);
                $table->index(['is_automated_demo', 'created_at', 'id'], $name.'_automation_created_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_cycles');
        foreach (['orders', 'products'] as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->dropIndex($name.'_automation_created_index');
                $table->dropColumn('is_automated_demo');
            });
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_automation_created_index');
            $table->dropIndex('users_created_at_index');
            $table->dropColumn('is_automated_demo');
        });
    }
};
