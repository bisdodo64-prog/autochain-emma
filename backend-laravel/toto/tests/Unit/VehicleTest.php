<?php

namespace Tests\Unit;

use App\Models\Vehicle;
use App\Models\User;
use App\Models\Maintenance;
use App\Models\MileageRecord;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_belongs_to_driver()
    {
        $driver = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['driver_id' => $driver->id]);

        $this->assertInstanceOf(User::class, $vehicle->driver);
        $this->assertEquals($driver->id, $vehicle->driver->id);
    }

    public function test_vehicle_has_many_maintenances()
    {
        $vehicle = Vehicle::factory()->create();
        $maintenance = Maintenance::factory()->create(['vehicle_id' => $vehicle->id]);

        $this->assertCount(1, $vehicle->maintenances);
        $this->assertEquals($maintenance->id, $vehicle->maintenances->first()->id);
    }

    public function test_vehicle_has_many_mileage_records()
    {
        $vehicle = Vehicle::factory()->create();
        $record = MileageRecord::factory()->create(['vehicle_id' => $vehicle->id]);

        $this->assertCount(1, $vehicle->mileageRecords);
        $this->assertEquals($record->id, $vehicle->mileageRecords->first()->id);
    }

    public function test_vehicle_has_many_documents()
    {
        $vehicle = Vehicle::factory()->create();
        $document = Document::factory()->create(['vehicle_id' => $vehicle->id]);

        $this->assertCount(1, $vehicle->documents);
        $this->assertEquals($document->id, $vehicle->documents->first()->id);
    }

    public function test_vehicle_fillable_fields()
    {
        $vehicle = Vehicle::factory()->create([
            'vin' => 'TEST1234567890',
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2024,
            'plate_number' => 'AB-123-CD',
            'current_mileage' => 1000,
            'status' => 'active'
        ]);

        $this->assertEquals('TEST1234567890', $vehicle->vin);
        $this->assertEquals('Toyota', $vehicle->brand);
        $this->assertEquals('Corolla', $vehicle->model);
        $this->assertEquals(2024, $vehicle->year);
        $this->assertEquals('AB-123-CD', $vehicle->plate_number);
        $this->assertEquals(1000, $vehicle->current_mileage);
        $this->assertEquals('active', $vehicle->status);
    }

    public function test_vehicle_casts_mileage_to_integer()
    {
        $vehicle = Vehicle::factory()->create(['current_mileage' => '1000']);

        $this->assertIsInt($vehicle->current_mileage);
        $this->assertEquals(1000, $vehicle->current_mileage);
    }
}
