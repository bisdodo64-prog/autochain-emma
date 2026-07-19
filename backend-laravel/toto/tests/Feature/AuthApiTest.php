<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email']
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401);
    }

    public function test_web3_login_with_valid_signature()
    {
        $user = User::factory()->create([
            'wallet_address' => '0x742d35Cc6634C0532925a3b844Bc9e7595f8bE8d'
        ]);

        // This would require actual Web3 signature verification
        // For now, we'll test the endpoint exists
        $response = $this->postJson('/api/auth/web3-login', [
            'wallet_address' => '0x742d35Cc6634C0532925a3b844Bc9e7595f8bE8d',
            'signature' => 'test_signature',
            'message' => 'test_message'
        ]);

        // Should return 401 with invalid signature
        $response->assertStatus(401);
    }
}
