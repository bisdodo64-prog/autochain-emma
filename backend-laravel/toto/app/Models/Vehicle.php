<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'vin', 'brand', 'model', 'year', 'plate_number',
        'blockchain_id', 'blockchain_tx_hash', 'current_mileage', 'purchase_price', 'status',
        'driver_id', 'insurance_expiry', 'tech_control_expiry',
        'last_sync_at'
    ];

    protected $casts = [
        'insurance_expiry' => 'date',
        'tech_control_expiry' => 'date',
        'current_mileage' => 'integer',
        'purchase_price' => 'float',
    ];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function maintenances()
    {
        return $this->hasMany(Maintenance::class);
    }

    public function mileageRecords()
    {
        return $this->hasMany(MileageRecord::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function fuelRecords()
    {
        return $this->hasMany(FuelRecord::class);
    }
}