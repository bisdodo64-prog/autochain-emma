<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vehicle;
use App\Models\Alert;
use App\Services\AlertService;
use Carbon\Carbon;

class SendAlerts extends Command
{
    protected $signature = 'alerts:send';
    protected $description = 'Check and send vehicle alerts';

    protected $alertService;

    public function __construct(AlertService $alertService)
    {
        parent::__construct();
        $this->alertService = $alertService;
    }

    public function handle()
    {
        $this->info('Checking alerts...');
        $vehicles = Vehicle::all();

        foreach ($vehicles as $vehicle) {
            // Insurance expiry alert (30 days before)
            if ($vehicle->insurance_expiry) {
                $daysUntilExpiry = Carbon::now()->diffInDays($vehicle->insurance_expiry);
                if ($daysUntilExpiry <= 30 && $daysUntilExpiry >= 0) {
                    $this->alertService->createAlert(
                        $vehicle->id,
                        'insurance_expiry',
                        "Vehicle insurance expires in {$daysUntilExpiry} days",
                        $daysUntilExpiry <= 7 ? 'critical' : 'warning'
                    );
                }
            }

            // Tech control expiry (30 days before)
            if ($vehicle->tech_control_expiry) {
                $daysUntilExpiry = Carbon::now()->diffInDays($vehicle->tech_control_expiry);
                if ($daysUntilExpiry <= 30 && $daysUntilExpiry >= 0) {
                    $this->alertService->createAlert(
                        $vehicle->id,
                        'tech_control_expiry',
                        "Technical control expires in {$daysUntilExpiry} days",
                        $daysUntilExpiry <= 7 ? 'critical' : 'warning'
                    );
                }
            }

            // Maintenance alert (every 10000 km)
            if ($vehicle->current_mileage > 0 && ($vehicle->current_mileage % 10000) < 100) {
                $this->alertService->createAlert(
                    $vehicle->id,
                    'maintenance_due',
                    "Maintenance due at {$vehicle->current_mileage} km",
                    'warning'
                );
            }
        }

        $this->info('Alerts checked and sent.');
    }
}