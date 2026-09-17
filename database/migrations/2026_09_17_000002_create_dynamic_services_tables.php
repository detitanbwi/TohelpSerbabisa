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
        // 1. Master Layanan
        Schema::create('layanans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('slug')->unique();
            $table->string('kode_layanan', 20)->default('ORD-'); // Prefix ID Order, e.g. EDT-, BSH-, TRV-
            $table->text('wa_template')->nullable(); // Template format WhatsApp wa.me
            $table->string('icon_or_image')->nullable();
            $table->text('deskripsi')->nullable();
            $table->text('catatan_nb')->nullable(); // NB opsional di bagian bawah layanan
            $table->integer('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Sub-Layanan / Paket
        Schema::create('sub_layanans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layanan_id')->constrained('layanans')->onDelete('cascade');
            $table->string('nama');
            $table->decimal('default_harga', 12, 2)->default(0);
            $table->string('default_satuan', 50)->nullable(); // e.g. '/ foto', '/ jam', '/ paket'
            $table->string('label_harga_custom', 50)->nullable(); // e.g. 'Start from', 'Mulai dari'
            $table->text('deskripsi')->nullable();
            $table->text('catatan_nb')->nullable();
            $table->integer('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Ketersediaan & Custom Harga per Cabang
        Schema::create('cabang_layanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabang_id')->constrained('cabangs')->onDelete('cascade');
            $table->foreignId('sub_layanan_id')->constrained('sub_layanans')->onDelete('cascade');
            $table->boolean('is_tersedia')->default(true);
            $table->decimal('custom_harga', 12, 2)->nullable();
            $table->string('custom_satuan', 50)->nullable();
            $table->string('custom_label', 50)->nullable();
            $table->text('custom_catatan_nb')->nullable();
            $table->timestamps();

            $table->unique(['cabang_id', 'sub_layanan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cabang_layanan');
        Schema::dropIfExists('sub_layanans');
        Schema::dropIfExists('layanans');
    }
};
