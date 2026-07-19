<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'garage_id',
        'type',
        'description',
        'parts_changed',
        'cost',
        'mileage_at_maintenance',
        'performed_at',
        'blockchain_tx_hash',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
        'cost' => 'integer',
        'mileage_at_maintenance' => 'integer',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function garage()
    {
        return $this->belongsTo(User::class, 'garage_id');
    }
}
