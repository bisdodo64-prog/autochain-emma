<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'name')) {
                $table->dropColumn(['name', 'path', 'type']);
            }
        });

        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'document_type')) {
                $table->string('document_type')->after('vehicle_id');
            }
            if (!Schema::hasColumn('documents', 'file_path')) {
                $table->string('file_path')->after('document_type');
            }
            if (!Schema::hasColumn('documents', 'file_name')) {
                $table->string('file_name')->after('file_path');
            }
            if (!Schema::hasColumn('documents', 'file_hash')) {
                $table->string('file_hash')->nullable()->after('file_name');
            }
            if (!Schema::hasColumn('documents', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('ipfs_hash');
            }
            if (!Schema::hasColumn('documents', 'is_public')) {
                $table->boolean('is_public')->default(false)->after('uploaded_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn([
                'document_type',
                'file_path',
                'file_name',
                'file_hash',
                'expiry_date',
                'is_public',
            ]);
            $table->string('name');
            $table->string('path');
            $table->string('type');
        });
    }
};
