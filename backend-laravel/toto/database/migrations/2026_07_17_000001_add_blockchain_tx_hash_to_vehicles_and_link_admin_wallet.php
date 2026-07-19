<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicles', 'blockchain_tx_hash')) {
                $table->string('blockchain_tx_hash')->nullable()->after('blockchain_id');
            }
        });

        $adminWallet = config('blockchain.admin_address')
            ?: '0x90F8bf6A479f320ead074411a4B0e7944Ea8c9C1';

        DB::table('users')
            ->where('email', 'admin@autochain.com')
            ->update(['wallet_address' => $adminWallet]);
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'blockchain_tx_hash')) {
                $table->dropColumn('blockchain_tx_hash');
            }
        });
    }
};
