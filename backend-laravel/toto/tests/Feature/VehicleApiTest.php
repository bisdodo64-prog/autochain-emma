<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VehicleApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_user_can_list_vehicles()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Vehicle::factory()->count(3)->create();

        $response = $this->getJson('/api/vehicles');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_vehicle()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $vehicleData = [
            'vin' => 'TEST1234567890',
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2024,
            'plate_number' => 'AB-123-CD',
            'current_mileage' => 1000,
        ];

        $response = $this->postJson('/api/vehicles', $vehicleData);

        $response->assertStatus(201)
            ->assertJsonFragment(['vin' => 'TEST1234567890']);
    }

    public function test_user_can_get_single_vehicle()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $vehicle = Vehicle::factory()->create();

        $response = $this->getJson("/api/vehicles/{$vehicle->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['vin' => $vehicle->vin]);
    }

    public function test_user_can_update_mileage()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $vehicle = Vehicle::factory()->create(['current_mileage' => 1000]);

        $response = $this->putJson("/api/vehicles/{$vehicle->id}/mileage", [
            'mileage' => 1500
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'current_mileage' => 1500
        ]);
    }

    public function test_unauthenticated_user_cannot_access_vehicles()
    {
        $response = $this->getJson('/api/vehicles');

        $response->assertStatus(401);
    }
}
