<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vehicle;
use App\Services\Blockchain\EthereumService;
use Illuminate\Support\Facades\Log;

class SyncBlockchain extends Command
{
    protected $signature = 'sync:blockchain {--vehicle= : Specific vehicle ID to sync}';
    protected $description = 'Sync vehicle data with blockchain';

    protected $blockchain;

    public function __construct(EthereumService $blockchain)
    {
        parent::__construct();
        $this->blockchain = $blockchain;
    }

    public function handle()
    {
        $this->info('Starting blockchain sync...');

        $query = Vehicle::query();
        
        if ($vehicleId = $this->option('vehicle')) {
            $query->where('id', $vehicleId);
        }

        $vehicles = $query->whereNotNull('blockchain_id')->get();

        $bar = $this->output->createProgressBar($vehicles->count());
        $bar->start();

        foreach ($vehicles as $vehicle) {
            try {
                $data = $this->blockchain->getVehicleData($vehicle->blockchain_id);
                
                if ($data) {
                    $vehicle->current_mileage = $data['mileage'];
                    $vehicle->last_sync_at = now();
                    $vehicle->save();
                    
                    $this->info("\nSynced vehicle #{$vehicle->id}: mileage = {$data['mileage']}");
                }
            } catch (\Exception $e) {
                Log::error("Sync failed for vehicle {$vehicle->id}: " . $e->getMessage());
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->info("\nSync completed.");
    }
}