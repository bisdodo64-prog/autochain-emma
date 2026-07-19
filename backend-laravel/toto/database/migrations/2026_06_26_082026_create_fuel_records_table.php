<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('driver_id')->constrained('users');
            $table->decimal('amount', 10, 2);
            $table->decimal('price_per_liter', 10, 2);
            $table->decimal('total_cost', 10, 2);
            $table->integer('mileage_at_fuel');
            $table->timestamp('fueled_at');
            $table->timestamps();

            $table->index('vehicle_id');
            $table->index('fueled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_records');
    }
};