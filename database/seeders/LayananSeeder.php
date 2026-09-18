<?php

namespace Database\Seeders;

use App\Models\Layanan;
use App\Models\SubLayanan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LayananSeeder extends Seeder
{
    /**
     * Run the database seeds with the EXACT original hardcoded services, pricing, and WhatsApp formats.
     */
    public function run(): void
    {
        $services = [
            [
                'nama' => 'Bersih-bersih',
                'slug' => 'bersih-bersih',
                'kode_layanan' => 'BSH-',
                'deskripsi' => 'Layanan pembersihan profesional untuk memastikan lingkungan Anda tetap bersih dan nyaman.',
                'catatan_nb' => "1. Biaya transportasi free 3km dari lokasi basecamp, jika diatas itu dikenakan charge Rp2.000/km.\n2. Peralatan dan sabun untuk bersih-bersih sudah disediakan dari kami.",
                'urutan' => 1,
                'wa_template' => "Hello Minhelp, saya ingin meminta bantuan Cleaning Service dan saya sudah membaca Price List di Website\n\nHarap Di Isi, Format Order Berikut\nID Order : {order_id}\nJenis Jasa : Cleaning Service\nJenis Ruangan : {sub_layanan}\nLuas : \nTanggal Pengerjaan : \nWaktu : \nAlamat : \nNama Pemesan : \nNo. WA : \nPayment (cash/TF) : \n\n*Noted : sertakan foto / video*",
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
                        'nama' => 'Ruang Tamu',
                        'default_harga' => 5000,
                        'default_satuan' => '/ m²',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Pembersihan menyeluruh area ruang tamu.',
                        'urutan' => 3,
                    ],
                    [
                        'nama' => 'Kamar Tidur',
                        'default_harga' => 5000,
                        'default_satuan' => '/ m²',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Pembersihan menyeluruh area kamar tidur.',
                        'urutan' => 4,
                    ],
                    [
                        'nama' => 'Dapur',
                        'default_harga' => 5000,
                        'default_satuan' => '/ m²',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Pembersihan kompor, meja dapur, wastafel, dan lantai dapur.',
                        'urutan' => 5,
                    ],
                    [
                        'nama' => 'Halaman',
                        'default_harga' => 5000,
                        'default_satuan' => '/ m²',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Pembersihan dan perapihan halaman depan atau belakang.',
                        'urutan' => 6,
                    ],
                    [
                        'nama' => 'Kamar Mandi (Kecil)',
                        'default_harga' => 50000,
                        'default_satuan' => '/ kamar mandi',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Pembersihan kerak, kloset, dinding, dan lantai kamar mandi ukuran kecil.',
                        'urutan' => 7,
                    ],
                    [
                        'nama' => 'Kamar Mandi (Besar)',
                        'default_harga' => 75000,
                        'default_satuan' => '/ kamar mandi',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Pembersihan kerak, kloset, bak mandi, dinding, dan lantai kamar mandi ukuran besar.',
                        'urutan' => 8,
                    ],
                    [
                        'nama' => 'Tandon',
                        'default_harga' => 50000,
                        'default_satuan' => '/ unit',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => "Price List:\n- 225L - 720L (start from 50k)\n- 840L - 1.200L (start 75k)\n- 2.200L - 3.300L (start from 100k)\n- 5.700L (125k)\n- 10.500L (175k)",
                        'urutan' => 9,
                    ],
                ],
            ],
            [
                'nama' => 'Pindahan & Angkut Barang',
                'slug' => 'pindahan',
                'kode_layanan' => 'PDH-',
                'deskripsi' => 'Solusi tepat dan mudah untuk pindahan rumah, kos, kantor, dan barang-barang besar Anda.',
                'catatan_nb' => 'Tarif transport berlaku Rp2.000 - Rp5.000/km jika jarak melebihi 3 km dari lokasi helpman.',
                'urutan' => 2,
                'wa_template' => "Hii kak, saya ingin meminta bantuan To Help\n\nID Order : {order_id}\nJenis pesanan : Pindahan/Angkut Barang\nAlamat ambil / order : \nList barang berat : \n1. ......\n2. ......\n3. ......\nList barang ringan :\n1. ......\n2. ......\n3. ......\n\nJenis jasa : {sub_layanan}\nPerlu kuli tambahan? (ya/tidak) : \nJika ya, berapa orang : \nAlamat tujuan / kirim (sertakan lantai) : \nTanggal/Waktu : \nNo.hp / wa : \nAtas nama : \nPayment (cash/TF) : ",
                'sub_layanans' => [
                    [
                        'nama' => 'Tossa',
                        'default_harga' => 50000,
                        'default_satuan' => '/ trip',
                        'label_harga_custom' => null,
                        'deskripsi' => "Armada motor roda 3 (Tossa) cocok untuk muatan sedang.\nTransport: Rp3.000/km jika > 3km.",
                        'urutan' => 1,
                    ],
                    [
                        'nama' => 'Pickup',
                        'default_harga' => 100000,
                        'default_satuan' => '/ trip',
                        'label_harga_custom' => null,
                        'deskripsi' => "Armada Mobil Pick-up bak terbuka untuk muatan besar.\nTransport: Rp5.000/km jika > 3km.",
                        'urutan' => 2,
                    ],
                    [
                        'nama' => 'Kuli Only',
                        'default_harga' => 30000,
                        'default_satuan' => '/ orang',
                        'label_harga_custom' => null,
                        'deskripsi' => "Tenaga angkut saja (tanpa sewa kendaraan armada).\nTransport: Rp2.000/km jika > 3km.",
                        'urutan' => 3,
                    ],
                ],
            ],
            [
                'nama' => 'Jasa Nemenin',
                'slug' => 'jasa-nemenin',
                'kode_layanan' => 'NMN-',
                'deskripsi' => 'Layanan pendamping ramah untuk berbagai aktivitas seperti belanja, kondangan, jalan-jalan, atau teman ngobrol.',
                'catatan_nb' => "1. Nemenin hanya sebatas teman ngobrol, jalan-jalan, kondangan, dll (NO PLUS PLUS).\n2. Biaya makan/minum/tiket masuk helpman saat bertugas ditanggung oleh customer.",
                'urutan' => 3,
                'wa_template' => "Hii kak, saya ingin meminta bantuan To Help\n\nID Order : {order_id}\nJenis Jasa : Nemenin\nPermintaan (pilih salah satu) : ngopi/nonton/night ride/yang lain…\nHari/tanggal : \nWaktu : \nNama : \nPilih Talent : \nNomor WhatsApp : \nPayment (Cash/TF) : \n\nDijemput / Menjemput : (tulis alamat kalian apabila ingin dijemput)\nNoted : (untuk cek talent, bisa kunjungi website di menu bagian profile. Bingung? Tanya admin)",
                'sub_layanans' => [
                    [
                        'nama' => 'Tarif Durasi',
                        'default_harga' => 15000,
                        'default_satuan' => '/ jam',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Tarif per jam fleksibel sesuai durasi yang Anda butuhkan.',
                        'urutan' => 1,
                    ],
                    [
                        'nama' => 'Paket Setengah Hari',
                        'default_harga' => 100000,
                        'default_satuan' => '/ 6 jam',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Pendampingan aktivitas hingga 6 jam.',
                        'urutan' => 2,
                    ],
                    [
                        'nama' => 'Paket Satu Hari',
                        'default_harga' => 150000,
                        'default_satuan' => '/ 12 jam',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Pendampingan aktivitas harian hingga 12 jam.',
                        'urutan' => 3,
                    ],
                    [
                        'nama' => 'Paket Full Time',
                        'default_harga' => 250000,
                        'default_satuan' => '/ 24 jam',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Pendampingan penuh 24 jam untuk perjalanan atau acara khusus.',
                        'urutan' => 4,
                    ],
                    [
                        'nama' => 'Transport',
                        'default_harga' => 2000,
                        'default_satuan' => '/ km',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Biaya tambahan transportasi helpman jika lokasi di luar radius gratis.',
                        'urutan' => 5,
                    ],
                ],
            ],
            [
                'nama' => 'Bantuan Online',
                'slug' => 'bantuan-online',
                'kode_layanan' => 'BON-',
                'deskripsi' => 'Layanan bantuan digital dan online seperti paid promote, sleep call, buzzer, dan joki game.',
                'catatan_nb' => 'Layanan diproses secara cepat dan terpercaya oleh tim profesional.',
                'urutan' => 4,
                'wa_template' => "Hii kak, saya ingin meminta bantuan To Help\n\nHarap Di Isi, Format Order Berikut\nOrder ID : {order_id}\nJenis Jasa : Bantuan online\nTipe Jasa : {sub_layanan}\nWaktu : \nNama : \nNomor Whatsapp : ",
                'sub_layanans' => [
                    [
                        'nama' => 'Paid Promote SW',
                        'default_harga' => 10000,
                        'default_satuan' => '/ postingan',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Promosi bisnis atau postingan Anda di Status WhatsApp dengan view organik.',
                        'urutan' => 1,
                    ],
                    [
                        'nama' => 'Sleep Call',
                        'default_harga' => 15000,
                        'default_satuan' => '/ jam',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Teman ngobrol via telepon sebelum tidur untuk menemani malam Anda.',
                        'urutan' => 2,
                    ],
                    [
                        'nama' => 'Buzzer',
                        'default_harga' => 50000,
                        'default_satuan' => '/ project',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Dukungan interaksi media sosial (like, comment, voting, review).',
                        'urutan' => 3,
                    ],
                    [
                        'nama' => 'Joki All Game',
                        'default_harga' => 100000,
                        'default_satuan' => '/ paket',
                        'label_harga_custom' => 'Tarif Tanya Admin',
                        'deskripsi' => 'Joki rank / leveling berbagai game populer (Mobile Legends, PUBG, FF, dll).',
                        'urutan' => 4,
                    ],
                ],
            ],
            [
                'nama' => 'Joki Tugas',
                'slug' => 'joki-tugas',
                'kode_layanan' => 'TGS-',
                'deskripsi' => 'Bantuan pengerjaan tugas sekolah, kuliah, makalah, presentasi, skripsi, dan karya ilmiah dengan cepat dan rapi.',
                'catatan_nb' => 'Hasil pengerjaan original, bebas plagiarisme, dan bergaransi revisi.',
                'urutan' => 5,
                'wa_template' => "Hii kak, saya ingin meminta bantuan To Help\n\nID Order : {order_id}\nJenis Jasa : Joki Tugas\nTipe Jasa : {sub_layanan}\nDeadline : \nNama : \nNomor WhatsApp : ",
                'sub_layanans' => [
                    [
                        'nama' => 'Makalah',
                        'default_harga' => 100000,
                        'default_satuan' => '/ makalah',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Penyusunan makalah lengkap dari cover, bab pendahuluan hingga daftar pustaka.',
                        'urutan' => 1,
                    ],
                    [
                        'nama' => 'Power Point',
                        'default_harga' => 50000,
                        'default_satuan' => '/ file',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Desain slide presentasi PPT modern, estetik, dan profesional.',
                        'urutan' => 2,
                    ],
                    [
                        'nama' => 'Tugas Custom',
                        'default_harga' => 30000,
                        'default_satuan' => '/ tugas',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Tugas harian, rangkuman, resume materi, dan tugas umum lainnya.',
                        'urutan' => 3,
                    ],
                    [
                        'nama' => 'Thesis',
                        'default_harga' => 3500000,
                        'default_satuan' => '/ project',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Pendampingan dan penyusunan tesis S2 secara terstruktur dan ilmiah.',
                        'urutan' => 4,
                    ],
                    [
                        'nama' => 'Judul Skripsi',
                        'default_harga' => 75000,
                        'default_satuan' => '/ paket',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Konsultasi dan rekomendasi judul skripsi menarik beserta outline latar belakang.',
                        'urutan' => 5,
                    ],
                    [
                        'nama' => 'Proposal (Sempro)',
                        'default_harga' => 750000,
                        'default_satuan' => '/ paket',
                        'label_harga_custom' => 'Paket Sempro',
                        'deskripsi' => 'Penyusunan proposal skripsi Bab 1 sampai Bab 3 siap seminar proposal.',
                        'urutan' => 6,
                    ],
                    [
                        'nama' => 'Analisis Data',
                        'default_harga' => 900000,
                        'default_satuan' => '/ project',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Olah data statistik kuantitatif / kualitatif (SPSS, SmartPLS, NVivo, dll).',
                        'urutan' => 7,
                    ],
                    [
                        'nama' => 'Jurnal',
                        'default_harga' => 150000,
                        'default_satuan' => '/ artikel',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Penyusunan artikel jurnal ilmiah nasional maupun konversi skripsi ke jurnal.',
                        'urutan' => 8,
                    ],
                    [
                        'nama' => 'Essai',
                        'default_harga' => 100000,
                        'default_satuan' => '/ naskah',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Penulisan esai kritis, opini, dan esai beasiswa berbobot.',
                        'urutan' => 9,
                    ],
                ],
            ],
            [
                'nama' => 'Editing & Dokumentasi',
                'slug' => 'editing',
                'kode_layanan' => 'EDT-',
                'deskripsi' => 'Layanan kreatif editing foto, video, serta fotografer & videografer untuk wisuda, event, dan konten.',
                'catatan_nb' => 'File hasil dokumentasi dan editing diberikan via link Google Drive dengan kualitas HD.',
                'urutan' => 6,
                'wa_template' => "Hii kak, saya ingin meminta bantuan To Help\n\nID Order : {order_id}\nJenis Jasa : Editing\nTipe Jasa : {sub_layanan}\nHari/Tanggal : \nLokasi : \nNama : \nNomor WhatsApp : ",
                'sub_layanans' => [
                    [
                        'nama' => 'Foto',
                        'default_harga' => 30000,
                        'default_satuan' => '/ foto',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Retouching, color grading, perbaikan pencahayaan, dan manipulasi foto.',
                        'urutan' => 1,
                    ],
                    [
                        'nama' => 'Video',
                        'default_harga' => 50000,
                        'default_satuan' => '/ video',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Editing video reels, TikTok, tugas video, vlog, dan video cinematic.',
                        'urutan' => 2,
                    ],
                    [
                        'nama' => 'Fotografer (30 menit)',
                        'default_harga' => 200000,
                        'default_satuan' => '/ 30 menit',
                        'label_harga_custom' => null,
                        'deskripsi' => "Include:\n- Unlimited foto\n- 15 file edit\n- Free Google Drive link",
                        'urutan' => 3,
                    ],
                    [
                        'nama' => 'Fotografer (45 menit)',
                        'default_harga' => 250000,
                        'default_satuan' => '/ 45 menit',
                        'label_harga_custom' => null,
                        'deskripsi' => "Include:\n- Unlimited foto\n- 20 file edit\n- Free Google Drive link",
                        'urutan' => 4,
                    ],
                    [
                        'nama' => 'Fotografer (60 menit)',
                        'default_harga' => 300000,
                        'default_satuan' => '/ 60 menit',
                        'label_harga_custom' => null,
                        'deskripsi' => "Include:\n- Unlimited foto\n- 30 file edit\n- Free Google Drive link",
                        'urutan' => 5,
                    ],
                    [
                        'nama' => 'Videografer',
                        'default_harga' => 400000,
                        'default_satuan' => '/ sesi',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Dokumentasi video acara, shooting profesional, dan final cinematic edit.',
                        'urutan' => 6,
                    ],
                ],
            ],
            [
                'nama' => 'Jastip (Jasa Titip)',
                'slug' => 'jastip',
                'kode_layanan' => 'JST-',
                'deskripsi' => 'Jasa titip pembelian makanan, minuman, obat, oleh-oleh, atau belanjaan kebutuhan harian langsung ke lokasi Anda.',
                'catatan_nb' => 'Biaya belum termasuk harga barang yang dibeli (dibayarkan sesuai struk belanja).',
                'urutan' => 7,
                'wa_template' => "Hello Minhelp, saya ingin meminta bantuan To Help\n\nHarap Di Isi, Format Order Berikut\nID Order : {order_id}\nJenis pesanan : Jastip\nJasa : {sub_layanan}\nAlamat Ambil/Beli : \nList order : \n1. ......\n2. ......\n3. ......\n\nAlamat tujuan / kirim : \nAtas nama : \nPayment (Cash/TF) : \n\nNo HP Penerima : \n\nNo HP Pengirim : (apabila pengirim dan penerima sama, di isi salah satu aja)\n\nNoted : Tetap berikan sharelok kepada driver untuk membantu memudahkan pengambilan / pengantaran 🙏🏻",
                'sub_layanans' => [
                    [
                        'nama' => 'Makanan, Minuman, atau Barang',
                        'default_harga' => 9000,
                        'default_satuan' => '/ trip',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Jasa pembelian makanan, minuman, atau titip beli barang dengan tarif terjangkau.',
                        'urutan' => 1,
                    ],
                ],
            ],
            [
                'nama' => 'Service Elektronik & Kendaraan',
                'slug' => 'service',
                'kode_layanan' => 'SVC-',
                'deskripsi' => 'Pengecekan dan perbaikan perangkat elektronik, HP, laptop, serta motor/mobil mogok langsung dipanggil.',
                'catatan_nb' => 'Harga di website adalah biaya pengecekan awal / panggil. Biaya penggantian sparepart disesuaikan saat pengerjaan.',
                'urutan' => 8,
                'wa_template' => "Hii kak, saya ingin meminta bantuan To Help\n\nID Order : {order_id}\nJenis Jasa : Service\nJenis barang : {sub_layanan}\nKeluhan/kerusakan :\nAmbil : (ya/tidak)?\nJika iya, Alamat ambil di : \nHari/tanggal : \nWaktu : \nNama : \nNomor WhatsApp : \nPayment (cash/TF) : ",
                'sub_layanans' => [
                    [
                        'nama' => 'Service Elektronik',
                        'default_harga' => 20000,
                        'default_satuan' => '/ unit',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Pengecekan awal kerusakan HP, laptop, komputer, AC, kipas, mesin cuci, dan elektronik lainnya.',
                        'urutan' => 1,
                    ],
                    [
                        'nama' => 'Service Kendaraan',
                        'default_harga' => 15000,
                        'default_satuan' => '/ unit',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Pengecekan awal motor/mobil mogok, aki drop, ganti oli, dan perbaikan ringan di lokasi.',
                        'urutan' => 2,
                    ],
                ],
            ],
            [
                'nama' => 'Penitipan & Packing',
                'slug' => 'penitipan',
                'kode_layanan' => 'SPA-',
                'deskripsi' => 'Layanan penitipan barang harian, penitipan kendaraan, packing kardus rapi, dan unboxing.',
                'catatan_nb' => 'Perhitungan harga berdasarkan volume, berat, ukuran barang, serta jenis kendaraan.',
                'urutan' => 9,
                'wa_template' => "Hii kak, saya ingin meminta bantuan To Help\n\nID Order : {order_id}\nJenis Jasa : Penitipan\nLayanan : {sub_layanan}\nNama / Deskripsi Barang : \nEstimasi Durasi / Jumlah : \nNama Pelanggan : \nNomor WhatsApp : \nAlamat Lengkap : ",
                'sub_layanans' => [
                    [
                        'nama' => 'Packing',
                        'default_harga' => 15000,
                        'default_satuan' => '/ kardus',
                        'label_harga_custom' => 'Mulai dari',
                        'deskripsi' => 'Jasa pengemasan barang menggunakan kardus, bubble wrap, dan lakban rapi serta aman.',
                        'urutan' => 1,
                    ],
                    [
                        'nama' => 'Unboxing',
                        'default_harga' => 5000,
                        'default_satuan' => '/ paket',
                        'label_harga_custom' => 'Mulai dari',
                        'deskripsi' => 'Jasa pembongkaran paket belanja atau barang pindahan dengan hati-hati.',
                        'urutan' => 2,
                    ],
                    [
                        'nama' => 'Titip Barang',
                        'default_harga' => 20000,
                        'default_satuan' => '/ hari',
                        'label_harga_custom' => 'Mulai dari',
                        'deskripsi' => 'Penitipan barang/paket harian yang aman dan terpantau dengan baik.',
                        'urutan' => 3,
                    ],
                    [
                        'nama' => 'Titip Kendaraan',
                        'default_harga' => 10000,
                        'default_satuan' => '/ hari',
                        'label_harga_custom' => 'Mulai dari',
                        'deskripsi' => 'Penitipan kendaraan (motor/mobil) harian di area yang aman dan teduh.',
                        'urutan' => 4,
                    ],
                ],
            ],
            [
                'nama' => 'Teknisi & Tukang',
                'slug' => 'teknisi',
                'kode_layanan' => 'TKN-',
                'deskripsi' => 'Tenaga tukang dan teknisi handal untuk perbaikan genteng, instalasi listrik, pipa air, dan renovasi rumah.',
                'catatan_nb' => 'Tarif belum termasuk material bahan bangunan yang diperlukan.',
                'urutan' => 10,
                'wa_template' => "Hii kak, saya ingin meminta bantuan To Help\n\nID Order : {order_id}\nJenis Jasa : Teknisi\nJenis Teknisi : {sub_layanan}\nKronologi Kerusakan : \nLokasi : \nNama : \nNomor WhatsApp : ",
                'sub_layanans' => [
                    [
                        'nama' => 'Bantuan Ringan',
                        'default_harga' => 50000,
                        'default_satuan' => '/ pengerjaan',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Perbaikan kran air bocor, pasang lampu, colokan listrik, gantung lukisan/pigura, perbaikan kunci.',
                        'urutan' => 1,
                    ],
                    [
                        'nama' => 'Bantuan Sedang',
                        'default_harga' => 80000,
                        'default_satuan' => '/ pengerjaan',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Perbaikan saluran pipa macet, cat dinding ruangan kecil, perbaikan pintu rusak.',
                        'urutan' => 2,
                    ],
                    [
                        'nama' => 'Bantuan Berat',
                        'default_harga' => 100000,
                        'default_satuan' => '/ hari',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Tukang harian untuk renovasi rumah, perbaikan atap/genteng bocor, dan plaster semen.',
                        'urutan' => 3,
                    ],
                ],
            ],
            [
                'nama' => 'Travel & Rental Kendaraan',
                'slug' => 'travel',
                'kode_layanan' => 'TRV-',
                'deskripsi' => 'Sewa kendaraan mobil dan motor lepas kunci / plus driver serta sewa driver berpengalaman.',
                'catatan_nb' => 'Harap siapkan KTP asli + jaminan (sepeda motor & STNK untuk sewa mobil, atau KTP asli untuk sewa motor).',
                'urutan' => 11,
                'wa_template' => "Hii kak, saya ingin menyewa kendaraan / Driver\n\nID Order : {order_id}\nJenis Kendaraan / Driver : {sub_layanan}\nNama Unit : \nTambahan Driver : ( ya / tidak)\nDi ambil / diantar : \nAlamat tujuan : (apabila diantar)\nWaktu Ambil : \nWaktu kembali : \nNama : \nNomor WhatsApp : \n\n*Tolong siapkan KTP asli sebagai jaminan 🙏🏻*",
                'sub_layanans' => [
                    [
                        'nama' => 'Rental Mobil',
                        'default_harga' => 250000,
                        'default_satuan' => '/ hari',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => "Varian Unit:\n1. Hiace Commuter 14 seat\n2. Hiace Premio 14 Seat\n3. Avanza\n4. Xenia\n5. Innova Grand / Barong\n6. Reborn Diesel\nNB: Tambahan driver Mobil (200rb - 350rb)",
                        'urutan' => 1,
                    ],
                    [
                        'nama' => 'Rental Motor',
                        'default_harga' => 70000,
                        'default_satuan' => '/ hari',
                        'label_harga_custom' => 'Only',
                        'deskripsi' => "Varian Unit:\n1. Beat\n2. Vario\nNB: Tambahan driver Motor (100rb - 150rb)",
                        'urutan' => 2,
                    ],
                    [
                        'nama' => 'Driver Only',
                        'default_harga' => 100000,
                        'default_satuan' => '/ sesi',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => "Tarif Driver:\n1. Driver Motor: 100k/7 jam, 150k/14 jam\n2. Driver Mobil Dalam Kota: 150k/7 jam, 200k/14 jam\n3. Driver Mobil Luar Kota: 250k/24 jam\n4. Driver Mobil Luar Provinsi: 350k/24 jam",
                        'urutan' => 3,
                    ],
                ],
            ],
            [
                'nama' => 'Daily Activity',
                'slug' => 'daily',
                'kode_layanan' => 'DLY-',
                'deskripsi' => 'Bantuan aktivitas harian fleksibel untuk berbagai keperluan mendesak Anda.',
                'catatan_nb' => 'Tarif menyesuaikan kondisi lapangan, tarif pasti ditentukan sebelum pengerjaan.',
                'urutan' => 12,
                'wa_template' => "Hii kak, saya ingin meminta bantuan To Help\n\nID Order : {order_id}\nJenis Jasa : Daily Activity\nMasalah Yang Sedang Dihadapi : \nBantuan yang di inginkan : \nHari/tanggal Bantuan : \nWaktu : \nLokasi Bantuan : \nNama : \nNomor WhatsApp : \nPayment (cash/TF) : ",
                'sub_layanans' => [
                    [
                        'nama' => 'Bantuan Ringan',
                        'default_harga' => 5000,
                        'default_satuan' => '/ Helpman',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Bantuan aktivitas ringan seperti mengambil barang tertinggal, membuang sampah, dll.',
                        'urutan' => 1,
                    ],
                    [
                        'nama' => 'Bantuan Sedang',
                        'default_harga' => 10000,
                        'default_satuan' => '/ Helpman',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Bantuan aktivitas sedang seperti antri tiket, belanja cepat, dll.',
                        'urutan' => 2,
                    ],
                    [
                        'nama' => 'Bantuan Berat',
                        'default_harga' => 20000,
                        'default_satuan' => '/ Helpman',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Bantuan aktivitas berat yang membutuhkan tenaga lebih.',
                        'urutan' => 3,
                    ],
                    [
                        'nama' => 'Bantuan Durasi',
                        'default_harga' => 15000,
                        'default_satuan' => '/ jam',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Bantuan berdasarkan durasi waktu: 10k/30 menit, 15k/jam.',
                        'urutan' => 4,
                    ],
                    [
                        'nama' => 'Transport',
                        'default_harga' => 2000,
                        'default_satuan' => '/ km',
                        'label_harga_custom' => null,
                        'deskripsi' => 'Transport 2k/km (jarak > 3km dari lokasi helpman).',
                        'urutan' => 5,
                    ],
                ],
            ],
            [
                'nama' => 'Jasa Kustom',
                'slug' => 'jasa-kustom',
                'kode_layanan' => 'KST-',
                'deskripsi' => 'Layanan kustom bebas sesuai kebutuhan spesifik Anda yang tidak tertera di menu lainnya.',
                'catatan_nb' => 'Hubungi admin untuk konsultasi kebutuhan dan penawaran harga terbaik.',
                'urutan' => 13,
                'wa_template' => "Hii kak, saya ingin meminta bantuan To Help untuk *(isi sesuai kebutuhan kalian)*\n\nOrder ID : {order_id}\nLayanan : {sub_layanan}\nDetail Pesanan : \nTanggal/Waktu : \nAlamat : \nNama : \nNo. HP/WA : ",
                'sub_layanans' => [
                    [
                        'nama' => 'Request Bantuan Apapun',
                        'default_harga' => 20000,
                        'default_satuan' => '/ request',
                        'label_harga_custom' => 'Start from',
                        'deskripsi' => 'Ceritakan bantuan apa yang Anda butuhkan, tim To Help siap membantu Anda.',
                        'urutan' => 1,
                    ],
                ],
            ],
        ];

        DB::beginTransaction();

        try {
            foreach ($services as $serviceData) {
                $subServices = $serviceData['sub_layanans'] ?? [];
                unset($serviceData['sub_layanans']);

                // Find or update Layanan
                $layanan = Layanan::updateOrCreate(
                    ['slug' => $serviceData['slug']],
                    $serviceData
                );

                // Sync Sub Layanan
                $existingSubIds = [];
                foreach ($subServices as $subData) {
                    $sub = SubLayanan::updateOrCreate(
                        [
                            'layanan_id' => $layanan->id,
                            'nama' => $subData['nama'],
                        ],
                        $subData
                    );
                    $existingSubIds[] = $sub->id;
                }

                // Delete obsolete sub layanans if any
                SubLayanan::where('layanan_id', $layanan->id)
                    ->whereNotIn('id', $existingSubIds)
                    ->delete();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
