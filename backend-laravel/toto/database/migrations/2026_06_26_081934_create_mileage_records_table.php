<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mileage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('recorded_by')->constrained('users');
            $table->integer('mileage');
            $table->timestamp('recorded_at');
            $table->string('blockchain_tx_hash')->nullable();
            $table->timestamps();

            $table->index('vehicle_id');
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mileage_records');
    }
};