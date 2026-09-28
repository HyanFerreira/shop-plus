<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 50);
            $table->text('recipient_encrypted');
            $table->text('postal_code_encrypted');
            $table->text('street_encrypted');
            $table->text('number_encrypted');
            $table->text('complement_encrypted')->nullable();
            $table->text('district_encrypted');
            $table->text('city_encrypted');
            $table->text('state_encrypted');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
