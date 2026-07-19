<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\FuelRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AlertSchedulerService
{
    protected $alertService;

    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    public function checkAllAlerts()
    {
        $vehicles = Vehicle::all();
        
        foreach ($vehicles as $vehicle) {
            $this->checkInsuranceExpiry($vehicle);
            $this->checkTechControlExpiry($vehicle);
            $this->checkOilChangeDue($vehicle);
        }
    }

    protected function checkInsuranceExpiry(Vehicle $vehicle)
    {
        if (!$vehicle->insurance_expiry) {
            return;
        }

        $daysUntilExpiry = Carbon::now()->diffInDays($vehicle->insurance_expiry, false);
        
        // Create alerts at 30, 7, and 1 days before expiry
        if ($daysUntilExpiry == 30) {
            $this->alertService->createAlert(
                $vehicle->id,
                'insurance_expiry',
                "Insurance expires in 30 days ({$vehicle->insurance_expiry->format('d/m/Y')})",
                'warning'
            );
        } elseif ($daysUntilExpiry == 7) {
            $this->alertService->createAlert(
                $vehicle->id,
                'insurance_expiry',
                "Insurance expires in 7 days ({$vehicle->insurance_expiry->format('d/m/Y')})",
                'critical'
            );
        } elseif ($daysUntilExpiry <= 0) {
            $this->alertService->createAlert(
                $vehicle->id,
                'insurance_expiry',
                "Insurance has expired on {$vehicle->insurance_expiry->format('d/m/Y')}",
                'critical'
            );
        }
    }

    protected function checkTechControlExpiry(Vehicle $vehicle)
    {
        if (!$vehicle->tech_control_expiry) {
            return;
        }

        $daysUntilExpiry = Carbon::now()->diffInDays($vehicle->tech_control_expiry, false);
        
        // Create alerts at 30, 7, and 1 days before expiry
        if ($daysUntilExpiry == 30) {
            $this->alertService->createAlert(
                $vehicle->id,
                'tech_control',
                "Technical control expires in 30 days ({$vehicle->tech_control_expiry->format('d/m/Y')})",
                'warning'
            );
        } elseif ($daysUntilExpiry == 7) {
            $this->alertService->createAlert(
                $vehicle->id,
                'tech_control',
                "Technical control expires in 7 days ({$vehicle->tech_control_expiry->format('d/m/Y')})",
                'critical'
            );
        } elseif ($daysUntilExpiry <= 0) {
            $this->alertService->createAlert(
                $vehicle->id,
                'tech_control',
                "Technical control has expired on {$vehicle->tech_control_expiry->format('d/m/Y')})",
                'critical'
            );
        }
    }

    protected function checkOilChangeDue(Vehicle $vehicle)
    {
        // Check if oil change is needed every 10,000 km
        $lastOilChange = $vehicle->maintenances()
            ->where('description', 'like', '%oil%')
            ->orWhere('description', 'like', '%vidange%')
            ->orderBy('performed_at', 'desc')
            ->first();

        if ($lastOilChange) {
            $kmSinceLastChange = $vehicle->current_mileage - $lastOilChange->mileage_at_service;
            
            if ($kmSinceLastChange >= 10000) {
                $this->alertService->createAlert(
                    $vehicle->id,
                    'maintenance',
                    "Oil change needed: {$kmSinceLastChange} km since last service",
                    'warning'
                );
            } elseif ($kmSinceLastChange >= 8000) {
                $this->alertService->createAlert(
                    $vehicle->id,
                    'maintenance',
                    "Oil change coming up: {$kmSinceLastChange} km since last service",
                    'info'
                );
            }
        } else {
            // No oil change recorded, check if mileage > 10000
            if ($vehicle->current_mileage >= 10000) {
                $this->alertService->createAlert(
                    $vehicle->id,
                    'maintenance',
                    "Oil change needed: Vehicle at {$vehicle->current_mileage} km",
                    'warning'
                );
            }
        }
    }

    public function checkFuelConsumption(Vehicle $vehicle)
    {
        // Calculate average consumption from last 5 fuel records
        $fuelRecords = $vehicle->fuelRecords()
            ->orderBy('refueled_at', 'desc')
            ->take(5)
            ->get();

        if ($fuelRecords->count() < 2) {
            return;
        }

        $totalFuel = $fuelRecords->sum('fuel_amount');
        $totalDistance = $fuelRecords->max('mileage') - $fuelRecords->min('mileage');
        
        if ($totalDistance > 0) {
            $avgConsumption = ($totalFuel / $totalDistance) * 100; // L/100km
            
            // Alert if consumption is abnormally high (> 15 L/100km)
            if ($avgConsumption > 15) {
                $this->alertService->createAlert(
                    $vehicle->id,
                    'fuel_consumption',
                    "High fuel consumption detected: {$avgConsumption} L/100km",
                    'warning'
                );
            }
        }
    }
}
