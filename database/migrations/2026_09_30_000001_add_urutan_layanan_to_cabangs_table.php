<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cabangs', function (Blueprint $table) {
            if (!Schema::hasColumn('cabangs', 'urutan_layanan')) {
                $table->json('urutan_layanan')->nullable()->after('free_distance_km');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cabangs', function (Blueprint $table) {
            if (Schema::hasColumn('cabangs', 'urutan_layanan')) {
                $table->dropColumn('urutan_layanan');
            }
        });
    }
};
