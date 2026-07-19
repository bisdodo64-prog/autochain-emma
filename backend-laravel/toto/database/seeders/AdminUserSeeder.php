<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@autochain.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'wallet_address' => config('blockchain.admin_address', '0x90F8bf6A479f320ead074411a4B0e7944Ea8c9C1'),
            ]
        );

        $admin->syncRoles(['super_admin']);
    }
}
