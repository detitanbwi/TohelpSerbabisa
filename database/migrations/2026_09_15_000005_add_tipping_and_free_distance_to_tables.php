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
            if (!Schema::hasColumn('cabangs', 'free_distance_km')) {
                $table->decimal('free_distance_km', 5, 2)->unsigned()->default(3.00)->after('lng');
            }
        });

        Schema::table('transaksis', function (Blueprint $table) {
            if (!Schema::hasColumn('transaksis', 'tip')) {
                $table->unsignedBigInteger('tip')->default(0)->after('total_harga');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cabangs', function (Blueprint $table) {
            if (Schema::hasColumn('cabangs', 'free_distance_km')) {
                $table->dropColumn('free_distance_km');
            }
        });

        Schema::table('transaksis', function (Blueprint $table) {
            if (Schema::hasColumn('transaksis', 'tip')) {
                $table->dropColumn('tip');
            }
        });
    }
};
