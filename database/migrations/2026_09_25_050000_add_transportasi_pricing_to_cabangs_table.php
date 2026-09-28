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
            // Pengaturan Transportasi Motor (Ojek) per Cabang
            if (!Schema::hasColumn('cabangs', 'is_ojek_aktif')) {
                $table->boolean('is_ojek_aktif')->default(true)->after('free_distance_km');
            }
            if (!Schema::hasColumn('cabangs', 'ojek_tarif_minimum')) {
                $table->unsignedInteger('ojek_tarif_minimum')->default(7000)->after('is_ojek_aktif');
            }
            if (!Schema::hasColumn('cabangs', 'ojek_tarif_per_km')) {
                $table->unsignedInteger('ojek_tarif_per_km')->default(2000)->after('ojek_tarif_minimum');
            }
            if (!Schema::hasColumn('cabangs', 'ojek_surcharge_per_km')) {
                $table->unsignedInteger('ojek_surcharge_per_km')->default(1000)->after('ojek_tarif_per_km');
            }

            // Pengaturan Transportasi Mobil (Taxi) per Cabang
            if (!Schema::hasColumn('cabangs', 'is_taxi_aktif')) {
                $table->boolean('is_taxi_aktif')->default(true)->after('ojek_surcharge_per_km');
            }
            if (!Schema::hasColumn('cabangs', 'taxi_tarif_minimum')) {
                $table->unsignedInteger('taxi_tarif_minimum')->default(18000)->after('is_taxi_aktif');
            }
            if (!Schema::hasColumn('cabangs', 'taxi_tarif_per_km')) {
                $table->unsignedInteger('taxi_tarif_per_km')->default(5000)->after('taxi_tarif_minimum');
            }
            if (!Schema::hasColumn('cabangs', 'taxi_tarif_per_km_lanjutan')) {
                $table->unsignedInteger('taxi_tarif_per_km_lanjutan')->default(4000)->after('taxi_tarif_per_km');
            }
            if (!Schema::hasColumn('cabangs', 'taxi_surcharge_per_km')) {
                $table->unsignedInteger('taxi_surcharge_per_km')->default(2000)->after('taxi_tarif_per_km_lanjutan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cabangs', function (Blueprint $table) {
            $columns = [
                'is_ojek_aktif',
                'ojek_tarif_minimum',
                'ojek_tarif_per_km',
                'ojek_surcharge_per_km',
                'is_taxi_aktif',
                'taxi_tarif_minimum',
                'taxi_tarif_per_km',
                'taxi_tarif_per_km_lanjutan',
                'taxi_surcharge_per_km',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('cabangs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
