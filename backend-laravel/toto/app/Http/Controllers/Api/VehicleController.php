<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\Document;
use App\Models\Maintenance;
use App\Models\MileageRecord;
use App\Services\Blockchain\EthereumService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    protected $blockchain;

    public function __construct(EthereumService $blockchain)
    {
        $this->blockchain = $blockchain;
    }

    public function index()
    {
        $vehicles = Vehicle::with('driver')->get();
        return response()->json($vehicles);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vin' => 'required|string|unique:vehicles',
            'brand' => 'required|string',
            'model' => 'required|string',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'plate_number' => 'required|string|unique:vehicles',
            'initial_mileage' => 'nullable|integer|min:0',
            'license_plate' => 'nullable|string|unique:vehicles,plate_number',
            'purchase_price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $plateNumber = $request->plate_number ?? $request->license_plate;
        $initialMileage = (int) ($request->initial_mileage ?? $request->mileage ?? 0);

        $blockchainResult = null;
        $blockchainId = null;
        $blockchainTx = null;

        try {
            $blockchainResult = $this->blockchain->registerVehicle(
                $request->vin,
                $initialMileage,
                config('blockchain.admin_private_key', '')
            );
            $blockchainId = $blockchainResult['vehicleId'] ?? null;
            $blockchainTx = $blockchainResult['transactionHash'] ?? null;
        } catch (\Exception $e) {
            // La création locale reste possible ; certification on-chain plus tard
            \Log::warning('Blockchain register skipped on vehicle create: ' . $e->getMessage());
        }

        $vehicle = Vehicle::create([
            'vin' => $request->vin,
            'brand' => $request->brand,
            'model' => $request->model,
            'year' => $request->year,
            'plate_number' => $plateNumber,
            'blockchain_id' => $blockchainId,
            'blockchain_tx_hash' => $blockchainTx,
            'current_mileage' => $initialMileage,
            'status' => 'available',
            'purchase_price' => $request->purchase_price,
            'last_sync_at' => $blockchainId ? now() : null,
        ]);

        return response()->json([
            'message' => $blockchainId
                ? 'Véhicule enregistré et certifié blockchain'
                : 'Véhicule enregistré (certification blockchain en attente)',
            'vehicle' => $vehicle,
            'blockchain_tx' => $blockchainResult,
        ], 201);
    }

    public function destroy($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->delete();

        return response()->json([
            'message' => 'Véhicule supprimé avec succès',
        ]);
    }

    public function show($id)
    {
        $vehicle = Vehicle::with(['driver', 'maintenances', 'documents', 'mileageRecords'])
            ->findOrFail($id);

        // Fetch blockchain data
        try {
            $blockchainData = $this->blockchain->getVehicleData($vehicle->blockchain_id);
            $vehicle->blockchain_data = $blockchainData;
        } catch (\Exception $e) {
            $vehicle->blockchain_data = null;
        }

        return response()->json($vehicle);
    }

    public function updateMileage(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'new_mileage' => 'nullable|integer|min:0',
            'mileage' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vehicle = Vehicle::findOrFail($id);
        $newMileage = $request->new_mileage ?? $request->mileage;

        // Update on blockchain
        try {
            $blockchainResult = $this->blockchain->updateMileage(
                $vehicle->blockchain_id,
                (int) $newMileage,
                config('blockchain.admin_private_key', '')
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Blockchain error: ' . $e->getMessage()
            ], 500);
        }

        // Update local
        $vehicle->current_mileage = $newMileage;
        $vehicle->last_sync_at = now();
        $vehicle->save();

        // Create mileage record
        $vehicle->mileageRecords()->create([
            'mileage' => $newMileage,
            'recorded_at' => now(),
            'recorded_by' => $request->user()->id ?? 1,
            'blockchain_tx_hash' => $blockchainResult['transactionHash'] ?? null,
        ]);

        return response()->json([
            'message' => 'Mileage updated successfully',
            'new_mileage' => $newMileage,
            'blockchain_tx' => $blockchainResult,
        ]);
    }

    public function getTimeline($id)
    {
        $vehicle = Vehicle::findOrFail($id);

        // Fetch blockchain data
        try {
            $maintenances = $this->blockchain->getMaintenanceHistory($vehicle->blockchain_id);
            $mileageHistory = $this->blockchain->getMileageHistory($vehicle->blockchain_id);
        } catch (\Exception $e) {
            $maintenances = [];
            $mileageHistory = [];
        }

        return response()->json([
            'blockchain' => [
                'maintenances' => $maintenances,
                'mileage_history' => $mileageHistory,
            ],
            'local' => [
                'documents' => $vehicle->documents,
                'trips' => $vehicle->trips,
                'fuel_records' => $vehicle->fuelRecords,
            ]
        ]);
    }

    public function assignDriver(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vehicle = Vehicle::findOrFail($id);
        $vehicle->driver_id = $request->driver_id;
        $vehicle->status = 'in_mission';
        $vehicle->save();

        return response()->json([
            'message' => 'Driver assigned successfully',
            'vehicle' => $vehicle->load('driver'),
        ]);
    }

    public function verifyDocument($vehicleId, $docId)
    {
        $document = Document::where('vehicle_id', $vehicleId)->findOrFail($docId);

        if (!Storage::disk('local')->exists($document->file_path)) {
            return response()->json([
                'is_valid' => false,
                'message' => 'File not found',
                'stored_hash' => $document->file_hash,
            ]);
        }

        $filePath = Storage::disk('local')->path($document->file_path);
        $currentHash = hash_file('sha256', $filePath);
        $isValid = $currentHash === $document->file_hash;

        return response()->json([
            'is_valid' => $isValid,
            'stored_hash' => $document->file_hash,
            'current_hash' => $currentHash,
            'ipfs_hash' => $document->ipfs_hash,
        ]);
    }

    public function authorizeGarage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|string',
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $result = $this->blockchain->authorizeGarage(
            $request->address,
            (bool) $request->status,
            config('blockchain.admin_private_key', '')
        );

        return response()->json([
            'message' => 'Garage authorization updated',
            'address' => $request->address,
            'status' => $request->status,
            'blockchain' => $result,
            'live' => $this->blockchain->isLive(),
        ]);
    }

    public function certify(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $force = $request->boolean('force');

        if ($vehicle->blockchain_id && !$force) {
            return response()->json([
                'message' => 'Véhicule déjà certifié on-chain. Envoyez force=true pour re-certifier (nouveau réseau).',
                'vehicle' => $vehicle,
                'live' => $this->blockchain->isLive(),
            ]);
        }

        if ($force) {
            $vehicle->blockchain_id = null;
            $vehicle->blockchain_tx_hash = null;
            $vehicle->save();
        }

        try {
            $blockchainResult = $this->blockchain->registerVehicle(
                $vehicle->vin,
                (int) ($vehicle->current_mileage ?? 0),
                config('blockchain.admin_private_key', '')
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Blockchain error: ' . $e->getMessage(),
            ], 500);
        }

        $vehicle->blockchain_id = $blockchainResult['vehicleId'] ?? null;
        $vehicle->blockchain_tx_hash = $blockchainResult['transactionHash'] ?? null;
        $vehicle->last_sync_at = now();
        $vehicle->save();

        return response()->json([
            'message' => $force
                ? 'Véhicule re-certifié sur la blockchain'
                : 'Véhicule certifié sur la blockchain',
            'vehicle' => $vehicle->fresh(),
            'blockchain_tx' => $blockchainResult,
            'live' => $this->blockchain->isLive(),
        ]);
    }

    public function syncAll(Request $request)
    {
        @set_time_limit(300);

        $adminKey = config('blockchain.admin_private_key', '');
        $garageKey = config('blockchain.garage_private_key', '') ?: $adminKey;

        $garageAuth = null;
        try {
            $garageAuth = $this->blockchain->ensureGarageAuthorized();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Autorisation garage échouée: ' . $e->getMessage(),
                'live' => $this->blockchain->isLive(),
            ], 500);
        }

        $results = [
            'vehicles_registered' => 0,
            'mileage_synced' => 0,
            'maintenance_synced' => 0,
            'vehicles_updated' => 0,
            'errors' => [],
        ];

        foreach (Vehicle::whereNull('blockchain_id')->get() as $vehicle) {
            try {
                $blockchainResult = $this->blockchain->registerVehicle(
                    $vehicle->vin,
                    (int) ($vehicle->current_mileage ?? 0),
                    $adminKey
                );
                $vehicle->blockchain_id = $blockchainResult['vehicleId'] ?? null;
                $vehicle->blockchain_tx_hash = $blockchainResult['transactionHash'] ?? null;
                $vehicle->last_sync_at = now();
                $vehicle->save();
                $results['vehicles_registered']++;
            } catch (\Exception $e) {
                $results['errors'][] = "véhicule #{$vehicle->id}: {$e->getMessage()}";
            }
        }

        $results['vehicles_updated'] = Vehicle::whereNotNull('blockchain_id')
            ->update(['last_sync_at' => now()]);

        MileageRecord::with('vehicle')
            ->whereNull('blockchain_tx_hash')
            ->whereHas('vehicle', fn ($q) => $q->whereNotNull('blockchain_id'))
            ->orderBy('recorded_at')
            ->get()
            ->each(function (MileageRecord $record) use ($adminKey, &$results) {
                try {
                    $blockchainResult = $this->blockchain->updateMileage(
                        $record->vehicle->blockchain_id,
                        (int) $record->mileage,
                        $adminKey
                    );
                    $record->blockchain_tx_hash = $blockchainResult['transactionHash'] ?? null;
                    $record->save();
                    $results['mileage_synced']++;
                } catch (\Exception $e) {
                    $results['errors'][] = "kilométrage #{$record->id}: {$e->getMessage()}";
                }
            });

        Maintenance::with('vehicle')
            ->whereNull('blockchain_tx_hash')
            ->whereHas('vehicle', fn ($q) => $q->whereNotNull('blockchain_id'))
            ->orderBy('performed_at')
            ->get()
            ->each(function (Maintenance $record) use ($garageKey, &$results) {
                try {
                    $blockchainResult = $this->blockchain->recordMaintenance(
                        $record->vehicle->blockchain_id,
                        $record->description,
                        $record->parts_changed ?? '',
                        $garageKey
                    );
                    $record->blockchain_tx_hash = $blockchainResult['transactionHash'] ?? null;
                    $record->save();
                    $results['maintenance_synced']++;
                } catch (\Exception $e) {
                    $results['errors'][] = "maintenance #{$record->id}: {$e->getMessage()}";
                }
            });

        $totalSynced = $results['vehicles_registered']
            + $results['mileage_synced']
            + $results['maintenance_synced']
            + $results['vehicles_updated'];

        $message = $totalSynced > 0
            ? "Synchronisation terminée ({$totalSynced} opération(s))"
            : 'Aucune donnée en attente de certification';

        if (!empty($results['errors'])) {
            $preview = implode('; ', array_slice($results['errors'], 0, 3));
            $extra = count($results['errors']) > 3 ? '…' : '';
            $message .= ' — ' . count($results['errors']) . " erreur(s): {$preview}{$extra}";
        }

        return response()->json([
            'message' => $message,
            'garage_authorized' => $garageAuth !== null,
            'live' => $this->blockchain->isLive(),
            ...$results,
        ]);
    }
}