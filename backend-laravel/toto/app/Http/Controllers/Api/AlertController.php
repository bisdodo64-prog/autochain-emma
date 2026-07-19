<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Vehicle;
use App\Services\AlertService;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    protected $alertService;

    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    public function index(Request $request)
    {
        $vehicleId = $request->query('vehicle_id');
        $alerts = $this->alertService->getActiveAlerts($vehicleId);
        
        return response()->json($alerts);
    }

    public function dismiss(Request $request, $id)
    {
        $alert = $this->alertService->dismissAlert($id, $request->user()->id);
        
        if (!$alert) {
            return response()->json(['message' => 'Alert not found'], 404);
        }

        return response()->json([
            'message' => 'Alert dismissed successfully',
            'alert' => $alert,
        ]);
    }

    public function stats(Request $request)
    {
        $totalAlerts = Alert::whereNull('dismissed_at')->count();
        $criticalAlerts = Alert::whereNull('dismissed_at')->where('severity', 'critical')->count();
        $warningAlerts = Alert::whereNull('dismissed_at')->where('severity', 'warning')->count();
        $infoAlerts = Alert::whereNull('dismissed_at')->where('severity', 'info')->count();

        // Alerts by type
        $alertsByType = Alert::whereNull('dismissed_at')
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');

        return response()->json([
            'total' => $totalAlerts,
            'by_severity' => [
                'critical' => $criticalAlerts,
                'warning' => $warningAlerts,
                'info' => $infoAlerts,
            ],
            'by_type' => $alertsByType,
        ]);
    }

    public function create(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'type' => 'required|string',
            'message' => 'required|string',
            'severity' => 'in:info,warning,critical',
        ]);

        $alert = $this->alertService->createAlert(
            $request->vehicle_id,
            $request->type,
            $request->message,
            $request->severity ?? 'info'
        );

        return response()->json([
            'message' => 'Alert created successfully',
            'alert' => $alert,
        ], 201);
    }
}
