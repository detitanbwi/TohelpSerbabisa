<?php

namespace Database\Seeders;

use App\Models\Layanan;
use App\Models\SubLayanan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LayananSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'nama' => 'Bersih-bersih',
                'slug' => 'bersih-bersih',
                'kode_layanan' => 'BSH-',
                'deskripsi' => 'Layanan pembersihan profesional untuk memastikan lingkungan Anda tetap bersih dan nyaman.',
                'catatan_nb' => 'Harga paket sudah termasuk alat dan cairan pembersih standar.',
                'urutan' => 1,
                'sub_layanans' => [
                    [
                        'nama' => 'Rumah Subsidi',
                        'default_harga' => 200000,
                        'default_satuan' => '/ paket',
                        'label_harga_custom' => null,
                        'deskripsi' => "Include:\n- 2 kamar tidur\n- 1 kamar mandi kecil\n- 1 dapur\n- Ruang tamu\n- Halaman depan",
                        'urutan' => 1,
                    ],
                    [
                        'nama' => 'Rumah Komersil',
                        'default_harga' => 350000,
                        'default_satuan' => '/ paket',
                        'label_harga_custom' => null,
                        'deskripsi' => "Include:\n- 3 kamar tidur\n- 1 kamar mandi besar\n- 1 dapur besar\n- Ruang tengah\n- Ruang keluarga\n- Halaman depan",
                        'urutan' => 2,
                    ],
                    [
                        'nama' => 'Kamar Kos Biasa',
                        'default_harga' => 50000,
                        'default_satuan' => '/ kamar',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Pembersihan kamar kos standar termasuk menyapu, mengepel, dan merapikan ruangan.',
                        'urutan' => 3,
                    ],
                    [
                        'nama' => 'Kamar Kos Kamar Mandi Dalam',
                        'default_harga' => 75000,
                        'default_satuan' => '/ kamar',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Pembersihan menyeluruh kamar kos beserta pembersihan kamar mandi dalam.',
                        'urutan' => 4,
                    ],
                    [
                        'nama' => 'Kamar Mandi Only',
                        'default_harga' => 40000,
                        'default_satuan' => '/ kamar mandi',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Pembersihan kerak dan sanitasi kamar mandi.',
                        'urutan' => 5,
                    ],
                    [
                        'nama' => 'Cuci Piring',
                        'default_harga' => 35000,
                        'default_satuan' => '/ sesi',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Jasa cuci piring dan peralatan dapur.',
                        'urutan' => 6,
                    ],
                    [
                        'nama' => 'Menyetrika Pakaian',
                        'default_harga' => 35000,
                        'default_satuan' => '/ jam',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Jasa setrika pakaian rapi dan wangi.',
                        'urutan' => 7,
                    ],
                ],
            ],
            [
                'nama' => 'Pindahan',
                'slug' => 'pindahan',
                'kode_layanan' => 'PDH-',
                'deskripsi' => 'Solusi tepat dan mudah untuk pindahan rumah, kos, kantor, dan barang-barang besar Anda.',
                'catatan_nb' => 'Harga dihitung per armada / jarak dan tenaga angkut.',
                'urutan' => 2,
                'sub_layanans' => [
                    [
                        'nama' => 'Pindahan Kos / Rumah',
                        'default_harga' => 150000,
                        'default_satuan' => '/ trip',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Termasuk armada pick-up/mobil box dan bantuan tenaga angkut barang.',
                        'urutan' => 1,
                    ],
                    [
                        'nama' => 'Jasa Angkut & Helper Saja',
                        'default_harga' => 75000,
                        'default_satuan' => '/ orang',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Tenaga bantuan profesional untuk bongkar muat dan angkut barang.',
                        'urutan' => 2,
                    ],
                ],
            ],
            [
                'nama' => 'Bantuan Online',
                'slug' => 'bantuan-online',
                'kode_layanan' => 'BTN-',
                'deskripsi' => 'Bantuan virtual, administrasi, riset, entri data, dan kebutuhan online lainnya secara cepat dan terpercaya.',
                'catatan_nb' => 'Pengerjaan tugas dikerjakan secara profesional sesuai deadline.',
                'urutan' => 3,
                'sub_layanans' => [
                    [
                        'nama' => 'Jasa Bantuan Virtual & Admin',
                        'default_harga' => 25000,
                        'default_satuan' => '/ tugas',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Input data, pengetikan dokumen, pengelolaan dokumen digital, dan tugas online.',
                        'urutan' => 1,
                    ],
                ],
            ],
            [
                'nama' => 'Jastip (Jasa Titip)',
                'slug' => 'jastip',
                'kode_layanan' => 'JTP-',
                'deskripsi' => 'Beli dan titip makanan, belanjaan pasar, barang kebutuhan, obat, atau oleh-oleh tanpa repot keluar rumah.',
                'catatan_nb' => 'Harga belum termasuk total belanjaan / struk barang.',
                'urutan' => 4,
                'sub_layanans' => [
                    [
                        'nama' => 'Jastip Kuliner / Makanan',
                        'default_harga' => 15000,
                        'default_satuan' => '/ lokasi',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Titip beli makanan resto, cafe, atau street food favorit Anda.',
                        'urutan' => 1,
                    ],
                    [
                        'nama' => 'Jastip Belanja Pasar / Supermarket',
                        'default_harga' => 25000,
                        'default_satuan' => '/ trip',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Belanja kebutuhan dapur, pasar tradisional, atau swalayan.',
                        'urutan' => 2,
                    ],
                ],
            ],
            [
                'nama' => 'Daily Activity',
                'slug' => 'daily',
                'kode_layanan' => 'DLY-',
                'deskripsi' => 'Asisten serbabisa untuk membantu berbagai aktivitas harian dan kebutuhan personal Anda.',
                'catatan_nb' => 'Durasi waktu dapat disesuaikan dengan kesepakatan.',
                'urutan' => 5,
                'sub_layanans' => [
                    [
                        'nama' => 'Asisten Harian Personal',
                        'default_harga' => 35000,
                        'default_satuan' => '/ jam',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Membantu urusan harian, antre, kirim dokumen, dan tugas fleksibel lainnya.',
                        'urutan' => 1,
                    ],
                ],
            ],
            [
                'nama' => 'Jasa Nemenin',
                'slug' => 'jasa-nemenin',
                'kode_layanan' => 'NMN-',
                'deskripsi' => 'Teman jalan, ngobrol santai, nemenin kondangan, nonton, wisuda, atau belanja dengan aman dan profesional.',
                'catatan_nb' => 'Layanan profesional, santun, dan menjaga privasi serta kenyamanan.',
                'urutan' => 6,
                'sub_layanans' => [
                    [
                        'nama' => 'Teman Jalan / Event (1 Jam)',
                        'default_harga' => 35000,
                        'default_satuan' => '/ jam',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Nemenin nongkrong, nonton film, makan bareng, atau jalan-jalan santai.',
                        'urutan' => 1,
                    ],
                    [
                        'nama' => 'Teman Kondangan / Wisuda',
                        'default_harga' => 100000,
                        'default_satuan' => '/ acara',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Pendamping acara formal atau keluarga dengan pakaian rapi dan sopan.',
                        'urutan' => 2,
                    ],
                ],
            ],
            [
                'nama' => 'Service Elektronik',
                'slug' => 'service',
                'kode_layanan' => 'SRV-',
                'deskripsi' => 'Perbaikan dan perawatan perangkat elektronik rumah tangga, laptop, komputer, dan peralatan lainnya.',
                'catatan_nb' => 'Biaya sparepart di luar biaya jasa pengecekan & perbaikan.',
                'urutan' => 7,
                'sub_layanans' => [
                    [
                        'nama' => 'Pengecekan / Service Ringan',
                        'default_harga' => 50000,
                        'default_satuan' => '/ unit',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Pengecekan kerusakan dan perbaikan ringan barang elektronik.',
                        'urutan' => 1,
                    ],
                    [
                        'nama' => 'Service AC / Cuci AC',
                        'default_harga' => 75000,
                        'default_satuan' => '/ unit',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Cuci AC berkala, isi freon, dan perbaikan pendingin ruangan.',
                        'urutan' => 2,
                    ],
                ],
            ],
            [
                'nama' => 'Travel & Wisata',
                'slug' => 'travel',
                'kode_layanan' => 'TRV-',
                'deskripsi' => 'Layanan transportasi antar kota, sewa mobil + supir, dan paket perjalanan wisata yang nyaman.',
                'catatan_nb' => 'Bisa carter drop-off atau sewa seharian penuh.',
                'urutan' => 8,
                'sub_layanans' => [
                    [
                        'nama' => 'Carter Drop Antar Kota',
                        'default_harga' => 300000,
                        'default_satuan' => '/ trip',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Perjalanan langsung ke kota tujuan dengan armada nyaman dan supir berpengalaman.',
                        'urutan' => 1,
                    ],
                    [
                        'nama' => 'Sewa Mobil + Driver (12 Jam)',
                        'default_harga' => 450000,
                        'default_satuan' => '/ hari',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Mobil ber-AC bersih siap mengantar aktivitas wisata atau keliling kota.',
                        'urutan' => 2,
                    ],
                ],
            ],
            [
                'nama' => 'Editing, Fotografer, & Videografer',
                'slug' => 'editing',
                'kode_layanan' => 'EDT-',
                'deskripsi' => 'Layanan profesional untuk editing foto, video, serta jasa fotografer dan videografer untuk berbagai kebutuhan Anda.',
                'catatan_nb' => 'Termasuk free Google Drive dan revisi minor.',
                'urutan' => 9,
                'sub_layanans' => [
                    [
                        'nama' => 'Editing Foto',
                        'default_harga' => 30000,
                        'default_satuan' => '/ foto',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Color grading, retouching, manipulasi, dan background removal.',
                        'urutan' => 1,
                    ],
                    [
                        'nama' => 'Editing Video',
                        'default_harga' => 50000,
                        'default_satuan' => '/ video',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Editing video reels, TikTok, YouTube, cinematic, atau tugas.',
                        'urutan' => 2,
                    ],
                    [
                        'nama' => 'Fotografer (30 Menit)',
                        'default_harga' => 200000,
                        'default_satuan' => '/ 30 menit',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Unlimited foto, 15 file edit, free Google Drive.',
                        'urutan' => 3,
                    ],
                    [
                        'nama' => 'Fotografer (45 Menit)',
                        'default_harga' => 250000,
                        'default_satuan' => '/ 45 menit',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Unlimited foto, 20 file edit, free Google Drive.',
                        'urutan' => 4,
                    ],
                    [
                        'nama' => 'Fotografer (60 Menit)',
                        'default_harga' => 300000,
                        'default_satuan' => '/ 60 menit',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Unlimited foto, 30 file edit, free Google Drive.',
                        'urutan' => 5,
                    ],
                    [
                        'nama' => 'Videografer',
                        'default_harga' => 400000,
                        'default_satuan' => '/ sesi',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Shooting video dokumentasi, event, branding, atau commercial.',
                        'urutan' => 6,
                    ],
                ],
            ],
            [
                'nama' => 'Joki Tugas',
                'slug' => 'joki-tugas',
                'kode_layanan' => 'JKT-',
                'deskripsi' => 'Bantuan pengerjaan tugas sekolah, kuliah, ketik makalah, presentasi, dan analisis data dengan cepat dan aman.',
                'catatan_nb' => 'Harga menyesuaikan tingkat kerumitan materi dan deadline.',
                'urutan' => 10,
                'sub_layanans' => [
                    [
                        'nama' => 'Pengetikan / Makalah Standar',
                        'default_harga' => 25000,
                        'default_satuan' => '/ tugas',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Ketik tugas, resume materi, susun powerpoint, dan format dokumen rapi.',
                        'urutan' => 1,
                    ],
                    [
                        'nama' => 'Tugas Hitungan / Pemrograman / Analisis',
                        'default_harga' => 50000,
                        'default_satuan' => '/ tugas',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Penyelesaian soal matematika, coding/pemrograman, olah data SPSS/Excel.',
                        'urutan' => 2,
                    ],
                ],
            ],
            [
                'nama' => 'Teknisi',
                'slug' => 'teknisi',
                'kode_layanan' => 'TKN-',
                'deskripsi' => 'Jasa pertukangan, kelistrikan, ledeng, instalasi jaringan, dan perbaikan perabotan rumah tangga.',
                'catatan_nb' => 'Material/bahan disediakan pelanggan atau dapat dibantu pembeliannya.',
                'urutan' => 11,
                'sub_layanans' => [
                    [
                        'nama' => 'Teknisi Listrik / Ledeng',
                        'default_harga' => 50000,
                        'default_satuan' => '/ titik',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Perbaikan instalasi lampu, stop kontak, kran bocor, dan pipa air.',
                        'urutan' => 1,
                    ],
                    [
                        'nama' => 'Tukang Bangunan / Perbaikan Rumah',
                        'default_harga' => 100000,
                        'default_satuan' => '/ hari',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Pengecatan, perbaikan plafon, pasang keramik, dan renovasi minor.',
                        'urutan' => 2,
                    ],
                ],
            ],
            [
                'nama' => 'Penitipan & Perawatan Hewan',
                'slug' => 'penitipan',
                'kode_layanan' => 'SPA-',
                'deskripsi' => 'Penitipan kucing/anjing kesayangan, grooming, pemandian, dan perawatan dengan kasih sayang.',
                'catatan_nb' => 'Makanan hewan dapat disediakan sendiri atau dari tempat penitipan.',
                'urutan' => 12,
                'sub_layanans' => [
                    [
                        'nama' => 'Penitipan Hewan (Daycare / Inap)',
                        'default_harga' => 35000,
                        'default_satuan' => '/ hari',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Kandang bersih, ber-AC/ventilasi baik, update foto & video harian.',
                        'urutan' => 1,
                    ],
                    [
                        'nama' => 'Grooming & Mandi Hewan',
                        'default_harga' => 50000,
                        'default_satuan' => '/ ekor',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Mandi wangi, potong kuku, bersihkan telinga, dan sisir bulu anti kutu.',
                        'urutan' => 2,
                    ],
                ],
            ],
            [
                'nama' => 'Jasa Kustom',
                'slug' => 'jasa-kustom',
                'kode_layanan' => 'KST-',
                'deskripsi' => 'Punya permintaan khusus di luar daftar layanan? Tim Serbabisa siap membantu kebutuhan unik Anda.',
                'catatan_nb' => 'Konsultasikan kebutuhan Anda terlebih dahulu ke admin kami.',
                'urutan' => 13,
                'sub_layanans' => [
                    [
                        'nama' => 'Permintaan Khusus (Custom Request)',
                        'default_harga' => 50000,
                        'default_satuan' => '/ request',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Bicarakan tugas apa pun yang ingin Anda serahkan kepada kami.',
                        'urutan' => 1,
                    ],
                ],
            ],
        ];

        foreach ($services as $srvData) {
            $subLayanans = $srvData['sub_layanans'];
            unset($srvData['sub_layanans']);

            $layanan = Layanan::updateOrCreate(
                ['slug' => $srvData['slug']],
                $srvData
            );

            foreach ($subLayanans as $sub) {
                $sub['layanan_id'] = $layanan->id;
                SubLayanan::updateOrCreate(
                    [
                        'layanan_id' => $layanan->id,
                        'nama' => $sub['nama'],
                    ],
                    $sub
                );
            }
        }
    }
}
