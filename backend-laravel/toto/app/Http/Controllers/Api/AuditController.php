<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Maintenance;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function verify(Request $request)
    {
        $query = trim((string) ($request->query('q') ?? $request->query('query') ?? ''));
        if ($query === '') {
            return response()->json(['message' => 'VIN, plaque ou ID requis'], 422);
        }

        $vehicleQuery = Vehicle::query();

        if (is_numeric($query)) {
            $vehicle = $vehicleQuery->where('id', (int) $query)->first();
        } else {
            $normalized = strtoupper(str_replace([' ', '-'], '', $query));
            $vehicle = Vehicle::query()
                ->whereRaw('UPPER(REPLACE(REPLACE(plate_number, \' \', \'\'), \'-\', \'\')) = ?', [$normalized])
                ->orWhereRaw('UPPER(REPLACE(vin, \' \', \'\')) = ?', [$normalized])
                ->orWhere('vin', 'ILIKE', $query)
                ->orWhere('plate_number', 'ILIKE', $query)
                ->first();
        }

        if (!$vehicle) {
            return response()->json([
                'valid' => false,
                'message' => 'Aucun véhicule trouvé pour cette recherche.',
            ]);
        }

        $lastMaintenance = Maintenance::where('vehicle_id', $vehicle->id)
            ->orderByDesc('performed_at')
            ->first();

        $txCount = Maintenance::where('vehicle_id', $vehicle->id)
            ->whereNotNull('blockchain_tx_hash')
            ->count();

        if ($vehicle->blockchain_id) {
            $txCount = max($txCount, 1);
        }

        $anomalies = [];
        if ($vehicle->tech_control_expiry && $vehicle->tech_control_expiry->isPast()) {
            $anomalies[] = 'Contrôle technique expiré';
        }
        if ($vehicle->insurance_expiry && $vehicle->insurance_expiry->isPast()) {
            $anomalies[] = 'Assurance expirée';
        }

        $valid = count($anomalies) === 0;

        return response()->json([
            'valid' => $valid,
            'vehicle' => sprintf('%s %s (%s)', $vehicle->brand, $vehicle->model, $vehicle->plate_number),
            'message' => $valid
                ? 'Véhicule authentifié. Aucune anomalie détectée.'
                : 'Véhicule trouvé avec anomalies : ' . implode(', ', $anomalies),
            'mileage' => $vehicle->current_mileage,
            'last_maintenance' => $lastMaintenance?->performed_at?->format('d/m/Y'),
            'tx_count' => $txCount,
            'blockchain_verified' => !empty($vehicle->blockchain_id),
            'vehicle_id' => $vehicle->id,
            'anomalies' => $anomalies,
        ]);
    }
}
