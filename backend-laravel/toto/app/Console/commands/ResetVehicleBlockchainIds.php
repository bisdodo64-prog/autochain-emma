<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use Illuminate\Console\Command;

class ResetVehicleBlockchainIds extends Command
{
    protected $signature = 'vehicles:reset-blockchain {--force : Confirmer sans question}';

    protected $description = 'Remet blockchain_id / tx_hash a null pour re-certifier sur le reseau courant (ex: Sepolia)';

    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('Remettre tous les IDs blockchain des vehicules a null ?')) {
            $this->info('Annule.');
            return self::SUCCESS;
        }

        $count = Vehicle::query()->update([
            'blockchain_id' => null,
            'blockchain_tx_hash' => null,
            'last_sync_at' => null,
        ]);

        $this->info("{$count} vehicule(s) prets a etre re-certifies.");
        return self::SUCCESS;
    }
}
