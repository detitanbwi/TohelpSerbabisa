<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $enumJenis = ['ojek', 'taxi', 'bersih-rumah', 'angkutan', 'custom', 'bantuan-online', 'jastip', 'daily-activity', 'jasa-nemenin', 'all-service', 'travel', 'editing', 'joki-tugas', 'teknisi', 'spa', 'penitipan'];

        Schema::table('transaksis', function (Blueprint $table) use ($enumJenis) {
            $table->enum('jenis', $enumJenis)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $enumJenis = ['ojek', 'taxi', 'bersih-rumah', 'angkutan', 'custom', 'bantuan-online', 'jastip', 'daily-activity', 'jasa-nemenin', 'all-service', 'travel', 'editing', 'joki-tugas', 'teknisi', 'spa'];

        Schema::table('transaksis', function (Blueprint $table) use ($enumJenis) {
            $table->enum('jenis', $enumJenis)->change();
        });
    }
};
