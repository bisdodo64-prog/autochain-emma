<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MileageRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'recorded_by',
        'mileage',
        'recorded_at',
        'blockchain_tx_hash',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'mileage' => 'integer',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
