<?php

namespace App\Services\Blockchain;

use Web3\Web3;
use Web3\Contract;
use Illuminate\Support\Facades\Log;

class EthereumService
{
    protected $web3;
    protected $contract;
    protected $contractAddress;
    protected $abi;

    public function __construct()
    {
        $this->web3 = new Web3(config('blockchain.rpc_url'));
        $this->contractAddress = config('blockchain.contract_address');
        $this->abi = json_decode(config('blockchain.abi'), true);
        $this->contract = new Contract($this->web3->provider, $this->abi);
        $this->contract->at($this->contractAddress);
    }

    public function registerVehicle(string $vin, int $initialMileage, string $privateKey)
    {
        try {
            // Get address from private key (simplified)
            $fromAddress = '0x742d35Cc6634C0532925a3b844Bc454e4438f44e';
            
            $result = null;
            $this->contract->send('registerVehicle', $vin, $initialMileage, [
                'from' => $fromAddress,
                'gas' => 200000,
            ], function ($err, $tx) use (&$result) {
                if ($err) throw new \Exception($err->getMessage());
                $result = $tx;
            });

            return [
                'transactionHash' => $result,
                'vehicleId' => $this->getVehicleCount(),
            ];
        } catch (\Exception $e) {
            Log::error('Blockchain register vehicle error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateMileage(int $vehicleId, int $newMileage, string $privateKey)
    {
        try {
            $fromAddress = '0x742d35Cc6634C0532925a3b844Bc454e4438f44e';
            
            $result = null;
            $this->contract->send('updateMileage', $vehicleId, $newMileage, [
                'from' => $fromAddress,
                'gas' => 150000,
            ], function ($err, $tx) use (&$result) {
                if ($err) throw new \Exception($err->getMessage());
                $result = $tx;
            });

            return [
                'transactionHash' => $result,
            ];
        } catch (\Exception $e) {
            Log::error('Blockchain update mileage error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function recordMaintenance(int $vehicleId, string $description, string $partsChanged, string $privateKey)
    {
        // Similar implementation...
    }

    public function getVehicleData(int $vehicleId)
    {
        try {
            $result = null;
            $this->contract->call('getVehicle', $vehicleId, function ($err, $data) use (&$result) {
                if ($err) throw new \Exception($err->getMessage());
                $result = $data;
            });

            if (!$result) return null;

            return [
                'vin' => $result[0],
                'mileage' => $result[1]->toString(),
                'lastUpdate' => $result[2]->toString(),
                'isActive' => $result[3],
            ];
        } catch (\Exception $e) {
            Log::error('Blockchain get vehicle error: ' . $e->getMessage());
            return null;
        }
    }

    public function getMaintenanceHistory(int $vehicleId)
    {
        try {
            $result = null;
            $this->contract->call('getMaintenanceHistory', $vehicleId, function ($err, $data) use (&$result) {
                if ($err) throw new \Exception($err->getMessage());
                $result = $data;
            });

            if (!$result) return [];

            return array_map(function ($item) {
                return [
                    'vehicleId' => $item['vehicleId']->toString(),
                    'description' => $item['description'],
                    'timestamp' => $item['timestamp']->toString(),
                    'mechanic' => $item['mechanic'],
                    'partsChanged' => $item['partsChanged'],
                ];
            }, $result);
        } catch (\Exception $e) {
            Log::error('Blockchain get maintenance error: ' . $e->getMessage());
            return [];
        }
    }

    public function getMileageHistory(int $vehicleId)
    {
        try {
            $result = null;
            $this->contract->call('getMileageHistory', $vehicleId, function ($err, $data) use (&$result) {
                if ($err) throw new \Exception($err->getMessage());
                $result = $data;
            });

            if (!$result) return [];

            return array_map(function ($item) {
                return [
                    'vehicleId' => $item['vehicleId']->toString(),
                    'mileage' => $item['mileage']->toString(),
                    'timestamp' => $item['timestamp']->toString(),
                    'recorder' => $item['recorder'],
                ];
            }, $result);
        } catch (\Exception $e) {
            Log::error('Blockchain get mileage history error: ' . $e->getMessage());
            return [];
        }
    }

    protected function getVehicleCount()
    {
        try {
            $result = null;
            $this->contract->call('getVehicleCount', function ($err, $data) use (&$result) {
                if ($err) throw new \Exception($err->getMessage());
                $result = $data;
            });
            
            return $result ? $result->toString() : '0';
        } catch (\Exception $e) {
            return '0';
        }
    }
}