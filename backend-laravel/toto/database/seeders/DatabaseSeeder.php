<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotent: skip if demo admin already exists (safe on Render redeploys)
        if (
            \Illuminate\Support\Facades\Schema::hasTable('users')
            && \App\Models\User::query()->where('email', 'admin@autochain.com')->exists()
        ) {
            $this->command?->info('Database already seeded — skipping.');
            return;
        }

        $this->call([
            RolePermissionSeeder::class,
            TestDataSeeder::class,
        ]);
    }
}