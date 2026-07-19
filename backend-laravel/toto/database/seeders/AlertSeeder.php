<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class AlertSeeder extends Seeder
{
    public function run(): void
    {
        $vehicle = Vehicle::first();

        if ($vehicle) {
            Alert::create([
                'vehicle_id' => $vehicle->id,
                'type' => 'insurance_expiry',
                'message' => "L'assurance du véhicule {$vehicle->plate_number} expire dans 15 jours",
                'severity' => 'warning',
                'triggered_at' => now(),
            ]);

            Alert::create([
                'vehicle_id' => $vehicle->id,
                'type' => 'tech_control_expiry',
                'message' => "Le contrôle technique du véhicule {$vehicle->plate_number} expire dans 5 jours",
                'severity' => 'critical',
                'triggered_at' => now(),
            ]);
        }
    }
}