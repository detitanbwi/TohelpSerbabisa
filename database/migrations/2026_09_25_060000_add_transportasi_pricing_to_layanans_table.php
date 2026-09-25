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
        Schema::table('layanans', function (Blueprint $table) {
            $table->boolean('is_transportasi')->default(false)->after('is_active');
            $table->unsignedInteger('tarif_minimum')->nullable()->after('is_transportasi');
            $table->unsignedInteger('tarif_per_km')->nullable()->after('tarif_minimum');
            $table->unsignedInteger('tarif_per_km_lanjutan')->nullable()->after('tarif_per_km');
            $table->unsignedInteger('surcharge_per_km')->nullable()->after('tarif_per_km_lanjutan');
            $table->decimal('free_distance_km', 5, 2)->default(3.0)->after('surcharge_per_km');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('layanans', function (Blueprint $table) {
            $table->dropColumn([
                'is_transportasi',
                'tarif_minimum',
                'tarif_per_km',
                'tarif_per_km_lanjutan',
                'surcharge_per_km',
                'free_distance_km',
            ]);
        });
    }
};
