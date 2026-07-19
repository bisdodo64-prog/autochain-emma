<?php

namespace App\Services\Blockchain;

class StubEthereumService
{
    public function registerVehicle(string $vin, int $initialMileage, string $privateKey): array
    {
        return [
            'vehicleId' => random_int(100, 999),
            'vin' => $vin,
            'initialMileage' => $initialMileage,
            'transactionHash' => '0x' . substr(hash('sha256', $vin . $initialMileage), 0, 64),
            'status' => 'registered_stub',
        ];
    }

    public function updateMileage(int $vehicleId, int $newMileage, string $privateKey): array
    {
        return [
            'vehicleId' => $vehicleId,
            'newMileage' => $newMileage,
            'transactionHash' => '0x' . substr(hash('sha256', "mileage-{$vehicleId}-{$newMileage}"), 0, 64),
            'status' => 'updated_stub',
        ];
    }

    public function recordMaintenance(int $vehicleId, string $description, string $partsChanged, string $privateKey): array
    {
        return [
            'vehicleId' => $vehicleId,
            'description' => $description,
            'partsChanged' => $partsChanged,
            'transactionHash' => '0x' . substr(hash('sha256', "mnt-{$vehicleId}-{$description}"), 0, 64),
            'status' => 'recorded_stub',
        ];
    }

    public function authorizeGarage(string $address, bool $status, string $privateKey): array
    {
        return [
            'address' => $address,
            'status' => $status,
            'transactionHash' => '0x' . substr(hash('sha256', "garage-{$address}"), 0, 64),
            'status_label' => 'authorized_stub',
        ];
    }

    public function getVehicleData(?int $vehicleId): ?array
    {
        if (!$vehicleId) {
            return null;
        }

        return [
            'vehicleId' => $vehicleId,
            'status' => 'active_stub',
            'lastUpdated' => now()->toIso8601String(),
        ];
    }

    public function getMaintenanceHistory(?int $vehicleId): array
    {
        return [];
    }

    public function getMileageHistory(?int $vehicleId): array
    {
        return [];
    }
}
