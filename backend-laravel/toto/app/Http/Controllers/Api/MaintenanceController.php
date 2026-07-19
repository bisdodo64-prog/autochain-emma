<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Maintenance;
use App\Models\Vehicle;
use App\Services\Blockchain\EthereumService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MaintenanceController extends Controller
{
    protected $blockchain;

    public function __construct(EthereumService $blockchain)
    {
        $this->blockchain = $blockchain;
    }

    public function index(Request $request, $vehicleId)
    {
        $vehicle = Vehicle::findOrFail($vehicleId);
        $maintenances = $vehicle->maintenances()->with('garage')->orderBy('created_at', 'desc')->get();
        
        return response()->json($maintenances);
    }

    public function store(Request $request, $vehicleId)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required|string',
            'parts_changed' => 'required|string',
            'cost' => 'nullable|numeric|min:0',
            'garage_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vehicle = Vehicle::findOrFail($vehicleId);

        if (!$vehicle->blockchain_id) {
            return response()->json([
                'message' => 'Le véhicule doit être certifié on-chain avant d\'enregistrer une maintenance',
            ], 422);
        }

        // Record on blockchain
        try {
            $blockchainResult = $this->blockchain->recordMaintenance(
                $vehicle->blockchain_id,
                $request->description,
                $request->parts_changed,
                config('blockchain.garage_private_key', '')
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Blockchain error: ' . $e->getMessage()
            ], 500);
        }

        // Create in local DB
        $maintenance = $vehicle->maintenances()->create([
            'description' => $request->description,
            'parts_changed' => $request->parts_changed,
            'cost' => $request->cost ?? 0,
            'garage_id' => $request->garage_id ?? $request->user()->id,
            'type' => $request->type ?? 'maintenance',
            'performed_at' => now(),
            'mileage_at_maintenance' => $vehicle->current_mileage,
            'blockchain_tx_hash' => $blockchainResult['transactionHash'] ?? null,
        ]);

        return response()->json([
            'message' => 'Maintenance recorded successfully',
            'maintenance' => $maintenance->load('garage'),
            'blockchain_tx' => $blockchainResult,
        ], 201);
    }

    public function certify($id)
    {
        $maintenance = Maintenance::with('vehicle')->findOrFail($id);

        if ($maintenance->blockchain_tx_hash) {
            return response()->json([
                'message' => 'Maintenance déjà certifiée on-chain',
                'maintenance' => $maintenance,
                'live' => $this->blockchain->isLive(),
            ]);
        }

        $vehicle = $maintenance->vehicle;
        if (!$vehicle?->blockchain_id) {
            return response()->json([
                'message' => 'Le véhicule doit être certifié on-chain avant la maintenance',
            ], 422);
        }

        try {
            $blockchainResult = $this->blockchain->recordMaintenance(
                $vehicle->blockchain_id,
                $maintenance->description,
                $maintenance->parts_changed ?? $maintenance->description,
                config('blockchain.garage_private_key', '')
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Blockchain error: ' . $e->getMessage(),
            ], 500);
        }

        $maintenance->blockchain_tx_hash = $blockchainResult['transactionHash'] ?? null;
        $maintenance->save();

        return response()->json([
            'message' => 'Maintenance certifiée sur la blockchain',
            'maintenance' => $maintenance->fresh()->load('garage'),
            'blockchain_tx' => $blockchainResult,
            'live' => $this->blockchain->isLive(),
        ]);
    }

    public function show($id)
    {
        $maintenance = Maintenance::with(['vehicle', 'garage'])->findOrFail($id);
        
        return response()->json($maintenance);
    }
}
