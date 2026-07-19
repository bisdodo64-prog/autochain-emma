<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AlertNotification;

class AlertService
{
    public function createAlert($vehicleId, $type, $message, $severity = 'info')
    {
        $alert = Alert::create([
            'vehicle_id' => $vehicleId,
            'type' => $type,
            'message' => $message,
            'severity' => $severity,
            'triggered_at' => now()
        ]);

        // Send notification
        $vehicle = Vehicle::find($vehicleId);
        if ($vehicle && $vehicle->driver) {
            try {
                Notification::send($vehicle->driver, new AlertNotification($alert));
            } catch (\Exception $e) {
                Log::error("Failed to send alert notification: " . $e->getMessage());
            }
        }

        return $alert;
    }

    public function dismissAlert($alertId, $userId)
    {
        $alert = Alert::find($alertId);
        if ($alert) {
            $alert->dismissed_at = now();
            $alert->save();
            return $alert;
        }
        return null;
    }

    public function getActiveAlerts($vehicleId = null)
    {
        $query = Alert::whereNull('dismissed_at');
        
        if ($vehicleId) {
            $query->where('vehicle_id', $vehicleId);
        }
        
        return $query->orderBy('triggered_at', 'desc')->get();
    }
}