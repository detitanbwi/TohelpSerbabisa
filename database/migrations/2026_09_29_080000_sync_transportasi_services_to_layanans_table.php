<?php

use App\Models\Layanan;
use App\Models\SubLayanan;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ensure transportation services (Ojek and Taxi) exist in the master layanans table.
     * This migration is idempotent and safe to run on existing production databases.
     */
    public function up(): void
    {
        // 1. Pastikan Layanan Ojek (Motor) terdaftar di Master Layanan
        $ojek = Layanan::where('slug', 'ojek')->first();
        if (! $ojek) {
            $ojek = Layanan::create([
                'nama' => 'Ojek (Motor)',
                'slug' => 'ojek',
                'kode_layanan' => 'OJK-',
                'is_transportasi' => true,
                'tarif_minimum' => 7000,
                'tarif_per_km' => 2000,
                'tarif_per_km_lanjutan' => null,
                'surcharge_per_km' => 1000,
                'free_distance_km' => 3.0,
                'deskripsi' => 'Layanan transportasi ojek motor cepat, andal, dan ramah kantong siap mengantar Anda ke tujuan dengan aman.',
                'catatan_nb' => "1. Tarif dihitung otomatis berdasarkan rute jarak tempuh Google Maps.\n2. Biaya penjemputan dari basecamp gratis sesuai batas kuota cabang.",
                'urutan' => 1,
                'is_active' => true,
                'icon_or_image' => 'fa-motorcycle',
                'icon_type' => 'icon',
                'warna' => '#FF5733',
                'wa_template' => "Hii kak, saya baru saja memesan To Help untuk meminta bantuan\n\n- Layanan: Ojek\nID Order : {order_id}\nTitik Penjemputan : \nTitik Pengantaran : \nHarga : {harga}\nMetode Pembayaran : Cash/Transfer",
            ]);
        } else {
            // Pastikan flag is_transportasi aktif jika record sudah ada
            if (! $ojek->is_transportasi) {
                $ojek->update(['is_transportasi' => true]);
            }
        }

        // Pastikan SubLayanan Ojek tersedia
        if ($ojek && ! SubLayanan::where('layanan_id', $ojek->id)->exists()) {
            SubLayanan::create([
                'layanan_id' => $ojek->id,
                'nama' => 'Perjalanan Motor (Ojek)',
                'default_harga' => 7000,
                'default_satuan' => null,
                'label_harga_custom' => 'Mulai dari',
                'deskripsi' => 'Pengantaran cepat menggunakan sepeda motor dengan helm bersih dan driver profesional.',
                'catatan_nb' => 'Tarif akhir disesuaikan dengan rute dan jarak tempuh Google Maps.',
                'urutan' => 1,
                'is_active' => true,
            ]);
        }

        // 2. Pastikan Layanan Taxi (Mobil) terdaftar di Master Layanan
        // Cek apakah ada record dengan slug 'mobil' atau 'taxi'
        $taxi = Layanan::whereIn('slug', ['mobil', 'taxi'])->first();
        if (! $taxi) {
            $taxi = Layanan::create([
                'nama' => 'Taxi (Mobil)',
                'slug' => 'mobil',
                'kode_layanan' => 'TX-',
                'is_transportasi' => true,
                'tarif_minimum' => 18000,
                'tarif_per_km' => 5000,
                'tarif_per_km_lanjutan' => 4000,
                'surcharge_per_km' => 2000,
                'free_distance_km' => 3.0,
                'deskripsi' => 'Layanan transportasi mobil ber-AC yang nyaman, aman, dan bersahabat untuk perjalanan pribadi maupun rombongan.',
                'catatan_nb' => "1. Tarif dihitung otomatis berdasarkan rute jarak tempuh Google Maps.\n2. Biaya penjemputan dari basecamp gratis sesuai batas kuota cabang.",
                'urutan' => 2,
                'is_active' => true,
                'icon_or_image' => 'fa-taxi',
                'icon_type' => 'icon',
                'warna' => '#2E86C1',
                'wa_template' => "Hii kak, saya baru saja memesan To Help untuk meminta bantuan\n\n- Layanan: Taxi (Mobil)\nID Order : {order_id}\nTitik Penjemputan : \nTitik Pengantaran : \nHarga : {harga}\nMetode Pembayaran : Cash/Transfer",
            ]);
        } else {
            // Pastikan flag is_transportasi aktif jika record sudah ada
            if (! $taxi->is_transportasi) {
                $taxi->update(['is_transportasi' => true]);
            }
        }

        // Pastikan SubLayanan Taxi tersedia
        if ($taxi && ! SubLayanan::where('layanan_id', $taxi->id)->exists()) {
            SubLayanan::create([
                'layanan_id' => $taxi->id,
                'nama' => 'Perjalanan Mobil (Taxi)',
                'default_harga' => 18000,
                'default_satuan' => null,
                'label_harga_custom' => 'Mulai dari',
                'deskripsi' => 'Kendaraan mobil ber-AC dengan kapasitas hingga 4-6 penumpang.',
                'catatan_nb' => 'Tarif akhir disesuaikan dengan rute dan jarak tempuh Google Maps.',
                'urutan' => 1,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Sengaja dibiarkan kosong agar rollback tidak menghapus data esensial produksi.
    }
};
