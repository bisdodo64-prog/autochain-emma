<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('driver_id')->constrained('users');
            $table->timestamp('start_at');
            $table->timestamp('end_at')->nullable();
            $table->integer('start_mileage');
            $table->integer('end_mileage')->nullable();
            $table->string('destination')->nullable();
            $table->text('purpose')->nullable();
            $table->timestamps();

            $table->index('vehicle_id');
            $table->index('start_at');
            $table->index('end_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};