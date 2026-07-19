<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\Document;
use App\Models\Maintenance;
use App\Models\FuelRecord;
use App\Models\Alert;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create users with different roles (idempotent)
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@autochain.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
            'wallet_address' => '0x90F8bf6A479f320ead074411a4B0e7944Ea8c9C1',
                'phone' => '+2250701234567',
                'employee_id' => 'ADM001',
            ]
        );
        $superAdmin->syncRoles(['super_admin']);

        $fleetManager = User::updateOrCreate(
            ['email' => 'manager@autochain.com'],
            [
                'name' => 'Jean Dupont',
                'password' => bcrypt('password'),
                'wallet_address' => '0x2345678901234567890123456789012345678901',
                'phone' => '+2250702345678',
                'employee_id' => 'MGR001',
            ]
        );
        $fleetManager->syncRoles(['fleet_manager']);

        $driver1 = User::updateOrCreate(
            ['email' => 'driver1@autochain.com'],
            [
                'name' => 'Pierre Martin',
                'password' => bcrypt('password'),
                'wallet_address' => '0x3456789012345678901234567890123456789012',
                'phone' => '+2250703456789',
                'employee_id' => 'DRV001',
            ]
        );
        $driver1->syncRoles(['driver']);

        $driver2 = User::updateOrCreate(
            ['email' => 'driver2@autochain.com'],
            [
                'name' => 'Marie Bernard',
                'password' => bcrypt('password'),
                'wallet_address' => '0x4567890123456789012345678901234567890123',
                'phone' => '+2250704567890',
                'employee_id' => 'DRV002',
            ]
        );
        $driver2->syncRoles(['driver']);

        $garage = User::updateOrCreate(
            ['email' => 'garage@autochain.com'],
            [
                'name' => 'Garage Auto Plus',
                'password' => bcrypt('password'),
                'wallet_address' => '0x5678901234567890123456789012345678901234',
                'phone' => '+2250705678901',
                'employee_id' => 'GRG001',
            ]
        );
        $garage->syncRoles(['garage']);

        $auditor = User::updateOrCreate(
            ['email' => 'auditor@autochain.com'],
            [
                'name' => 'Sophie Bernard',
                'password' => bcrypt('password'),
                'wallet_address' => '0x6789012345678901234567890123456789012345',
                'phone' => '+2250706789012',
                'employee_id' => 'AUD001',
            ]
        );
        $auditor->syncRoles(['auditor']);

        // Create vehicles (blockchain_id null = a certifier sur le reseau courant)
        $vehicle1 = Vehicle::updateOrCreate(
            ['vin' => 'VF1ABC12345678901'],
            [
                'brand' => 'Renault',
                'model' => 'Clio',
                'year' => 2022,
                'plate_number' => 'AB-123-CD',
                'blockchain_id' => null,
                'blockchain_tx_hash' => null,
                'current_mileage' => 45000,
                'purchase_price' => 8500000,
                'status' => 'available',
                'driver_id' => null,
                'insurance_expiry' => Carbon::now()->addMonths(6),
                'tech_control_expiry' => Carbon::now()->addMonths(3),
                'last_sync_at' => null,
            ]
        );

        $vehicle2 = Vehicle::updateOrCreate(
            ['vin' => 'VF2DEF98765432109'],
            [
                'brand' => 'Peugeot',
                'model' => '308',
                'year' => 2023,
                'plate_number' => 'EF-456-GH',
                'blockchain_id' => null,
                'blockchain_tx_hash' => null,
                'current_mileage' => 32000,
                'purchase_price' => 12500000,
                'status' => 'in_mission',
                'driver_id' => $driver1->id,
                'insurance_expiry' => Carbon::now()->addMonths(8),
                'tech_control_expiry' => Carbon::now()->addMonths(5),
                'last_sync_at' => null,
            ]
        );

        $vehicle3 = Vehicle::updateOrCreate(
            ['vin' => 'VF3GHI56789012345'],
            [
                'brand' => 'Citroën',
                'model' => 'C3',
                'year' => 2021,
                'plate_number' => 'IJ-789-KL',
                'blockchain_id' => null,
                'blockchain_tx_hash' => null,
                'current_mileage' => 68000,
                'purchase_price' => 7200000,
                'status' => 'maintenance',
                'driver_id' => null,
                'insurance_expiry' => Carbon::now()->subDays(5),
                'tech_control_expiry' => Carbon::now()->addMonths(2),
                'last_sync_at' => null,
            ]
        );

        $vehicle4 = Vehicle::updateOrCreate(
            ['vin' => 'VF4JKL11223344556'],
            [
                'brand' => 'Toyota',
                'model' => 'Corolla',
                'year' => 2022,
                'plate_number' => 'AA-123-BB',
                'blockchain_id' => null,
                'blockchain_tx_hash' => null,
                'current_mileage' => 45000,
                'purchase_price' => 15800000,
                'status' => 'available',
                'driver_id' => $driver2->id,
                'insurance_expiry' => Carbon::now()->addMonths(10),
                'tech_control_expiry' => Carbon::now()->addMonths(4),
                'last_sync_at' => null,
            ]
        );

        $vehicle5 = Vehicle::updateOrCreate(
            ['vin' => 'VF5MNO99887766554'],
            [
                'brand' => 'BMW',
                'model' => 'Serie 3',
                'year' => 2024,
                'plate_number' => 'II-345-JJ',
                'blockchain_id' => null,
                'blockchain_tx_hash' => null,
                'current_mileage' => 5000,
                'purchase_price' => 28500000,
                'status' => 'in_mission',
                'driver_id' => $driver1->id,
                'insurance_expiry' => Carbon::now()->addMonths(12),
                'tech_control_expiry' => Carbon::now()->addMonths(6),
                'last_sync_at' => null,
            ]
        );

        // Create documents
        Document::updateOrCreate(
            [
                'vehicle_id' => $vehicle1->id,
                'document_type' => 'registration',
                'file_name' => 'carte_grise_AB123CD.pdf',
            ],
            [
                'file_path' => 'documents/1/carte_grise.pdf',
                'file_hash' => hash('sha256', 'fake_content_1'),
                'ipfs_hash' => 'QmXxxXxxXxxXxxXxxXxxXxxXxxXxxXxxXxxXxxXxxXxx',
                'expiry_date' => null,
                'uploaded_by' => $fleetManager->id,
                'is_public' => false,
            ]
        );

        Document::updateOrCreate(
            [
                'vehicle_id' => $vehicle1->id,
                'document_type' => 'insurance',
                'file_name' => 'assurance_AB123CD.pdf',
            ],
            [
                'file_path' => 'documents/1/assurance.pdf',
                'file_hash' => hash('sha256', 'fake_content_2'),
                'ipfs_hash' => 'QmYyyYyyYyyYyyYyyYyyYyyYyyYyyYyyYyyYyyYyyYyy',
                'expiry_date' => Carbon::now()->addMonths(6),
                'uploaded_by' => $fleetManager->id,
                'is_public' => true,
            ]
        );

        // Create maintenance records
        if (!Maintenance::where('vehicle_id', $vehicle1->id)->where('blockchain_tx_hash', '0xabc123def456')->exists()) {
            Maintenance::create([
                'vehicle_id' => $vehicle1->id,
                'garage_id' => $garage->id,
                'type' => 'vidange',
                'description' => 'Oil change and filter replacement',
                'parts_changed' => 'Oil filter, Air filter',
                'cost' => 85000,
                'performed_at' => Carbon::now()->subMonths(2),
                'mileage_at_maintenance' => 42000,
                'blockchain_tx_hash' => '0xabc123def456',
            ]);
        }

        if (!Maintenance::where('vehicle_id', $vehicle2->id)->where('blockchain_tx_hash', '0xdef456ghi789')->exists()) {
            Maintenance::create([
                'vehicle_id' => $vehicle2->id,
                'garage_id' => $garage->id,
                'type' => 'freinage',
                'description' => 'Brake pad replacement',
                'parts_changed' => 'Front brake pads',
                'cost' => 175000,
                'performed_at' => Carbon::now()->subMonths(1),
                'mileage_at_maintenance' => 30000,
                'blockchain_tx_hash' => '0xdef456ghi789',
            ]);
        }

        // Create fuel records
        if (!FuelRecord::where('vehicle_id', $vehicle1->id)->where('mileage_at_fuel', 44500)->exists()) {
            FuelRecord::create([
                'vehicle_id' => $vehicle1->id,
                'driver_id' => $driver1->id,
                'amount' => 45.5,
                'price_per_liter' => 850,
                'total_cost' => 38675,
                'mileage_at_fuel' => 44500,
                'fueled_at' => Carbon::now()->subDays(5),
            ]);
        }

        if (!FuelRecord::where('vehicle_id', $vehicle2->id)->where('mileage_at_fuel', 31500)->exists()) {
            FuelRecord::create([
                'vehicle_id' => $vehicle2->id,
                'driver_id' => $driver1->id,
                'amount' => 38.2,
                'price_per_liter' => 850,
                'total_cost' => 32470,
                'mileage_at_fuel' => 31500,
                'fueled_at' => Carbon::now()->subDays(3),
            ]);
        }

        // Create alerts
        if (!Alert::where('vehicle_id', $vehicle3->id)->where('type', 'insurance_expiry')->exists()) {
            Alert::create([
                'vehicle_id' => $vehicle3->id,
                'user_id' => $fleetManager->id,
                'type' => 'insurance_expiry',
                'message' => 'Insurance has expired on ' . Carbon::now()->subDays(5)->format('d/m/Y'),
                'severity' => 'critical',
                'triggered_at' => Carbon::now()->subDays(5),
                'dismissed_at' => null,
            ]);
        }

        if (!Alert::where('vehicle_id', $vehicle1->id)->where('type', 'maintenance')->exists()) {
            Alert::create([
                'vehicle_id' => $vehicle1->id,
                'user_id' => $fleetManager->id,
                'type' => 'maintenance',
                'message' => 'Oil change coming up: 3000 km since last service',
                'severity' => 'info',
                'triggered_at' => Carbon::now()->subDays(1),
                'dismissed_at' => null,
            ]);
        }

        $this->seedDefaultAvatars([
            $superAdmin,
            $fleetManager,
            $driver1,
            $driver2,
            $garage,
            $auditor,
        ]);

        $this->command->info('Test data seeded successfully!');
    }

    /**
     * Genere des avatars PNG colores (initiales) pour la demo.
     */
    private function seedDefaultAvatars(array $users): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            $this->command?->warn('GD absent: avatars non generes.');
            return;
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $disk->makeDirectory('avatars');

        $palette = [
            [14, 165, 233],
            [99, 102, 241],
            [16, 185, 129],
            [245, 158, 11],
            [239, 68, 68],
            [168, 85, 247],
        ];

        foreach (array_values($users) as $i => $user) {
            if (!$user?->id) {
                continue;
            }
            if ($user->avatar_path && $disk->exists($user->avatar_path)) {
                continue;
            }

            $parts = preg_split('/\s+/', trim((string) $user->name)) ?: ['U'];
            $initials = strtoupper(substr($parts[0] ?? 'U', 0, 1) . substr($parts[1] ?? '', 0, 1));
            if ($initials === '') {
                $initials = 'U';
            }

            $size = 256;
            $img = imagecreatetruecolor($size, $size);
            $rgb = $palette[$i % count($palette)];
            $bg = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
            $fg = imagecolorallocate($img, 255, 255, 255);
            imagefilledrectangle($img, 0, 0, $size, $size, $bg);

            $font = 5;
            $tw = imagefontwidth($font) * strlen($initials);
            $th = imagefontheight($font);
            imagestring($img, $font, (int) (($size - $tw) / 2), (int) (($size - $th) / 2), $initials, $fg);

            ob_start();
            imagepng($img);
            $png = ob_get_clean();
            imagedestroy($img);

            $path = 'avatars/' . $user->id . '.png';
            $disk->put($path, $png);
            $user->avatar_path = $path;
            $user->save();
        }
    }
}
