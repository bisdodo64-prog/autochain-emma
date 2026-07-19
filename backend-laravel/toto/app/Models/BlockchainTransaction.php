<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockchainTransaction extends Model
{
    protected $fillable = [
        'tx_hash',
        'block_number',
        'contract_address',
        'function_name',
        'parameters',
        'status',
        'gas_used',
        'block_timestamp',
        'vehicle_id',
        'user_id'
    ];

    protected $casts = [
        'parameters' => 'array',
        'block_timestamp' => 'datetime',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}