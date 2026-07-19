<?php

namespace Tests\Unit;

use App\Services\Blockchain\EthereumService;
use App\Services\Blockchain\IPFSService;
use PHPUnit\Framework\TestCase;

class BlockchainServicesTest extends TestCase
{
    public function test_register_vehicle_returns_expected_payload(): void
    {
        $service = new EthereumService();

        $result = $service->registerVehicle('VIN123', 1000, '0xabc');

        $this->assertArrayHasKey('vehicleId', $result);
        $this->assertSame('VIN123', $result['vin']);
        $this->assertSame(1000, $result['initialMileage']);
        $this->assertArrayHasKey('transactionHash', $result);
    }

    public function test_ipfs_service_returns_content_identifier(): void
    {
        $service = new IPFSService();

        $result = $service->add(__FILE__);

        $this->assertStringStartsWith('Qm', $result);
    }
}
