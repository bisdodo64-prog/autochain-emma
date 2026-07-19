<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('garage_id')->nullable()->constrained('users');
            $table->string('type');
            $table->text('description');
            $table->text('parts_changed')->nullable();
            $table->integer('cost')->nullable();
            $table->integer('mileage_at_maintenance');
            $table->timestamp('performed_at');
            $table->string('blockchain_tx_hash')->nullable();
            $table->timestamps();

            $table->index('vehicle_id');
            $table->index('performed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};