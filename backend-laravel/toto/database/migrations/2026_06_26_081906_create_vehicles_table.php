<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('vin')->unique();
            $table->string('brand');
            $table->string('model');
            $table->integer('year');
            $table->string('plate_number')->unique();
            $table->integer('blockchain_id')->nullable();
            $table->integer('current_mileage')->default(0);
            $table->enum('status', ['available', 'in_mission', 'maintenance', 'out_of_service'])->default('available');
            $table->foreignId('driver_id')->nullable()->constrained('users');
            $table->date('insurance_expiry')->nullable();
            $table->date('tech_control_expiry')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->index('blockchain_id');
            $table->index('status');
            $table->index('driver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};