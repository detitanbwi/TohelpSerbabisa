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
            if (! Schema::hasColumn('cabangs', 'ojek_tarif_per_km_lanjutan')) {
                $table->unsignedInteger('ojek_tarif_per_km_lanjutan')->nullable()->after('ojek_tarif_per_km');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cabangs', function (Blueprint $table) {
            if (Schema::hasColumn('cabangs', 'ojek_tarif_per_km_lanjutan')) {
                $table->dropColumn('ojek_tarif_per_km_lanjutan');
            }
        });
    }
};
