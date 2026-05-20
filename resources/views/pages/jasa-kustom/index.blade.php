@extends('layouts.app')

@section('content')
    <section class="padding-small">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">Jasa Sesuai Permintaan</h2>
                <p class="lead">Dari membantu belanja, mengurus keperluan administrasi, hingga
                    tugas-tugas khusus lainnya, kami siap melayani Anda.</p>
            </div>

            <!-- Services Section -->
            @php
                $services = [
                    'Jastip' => [
                        'icon' => 'fas fa-shopping-bag',
                        'items' => ['Jastip Makanan', 'Jastip Minuman', 'Jastip Barang'],
                    ],
                    'Daily Activity' => [
                        'icon' => 'fas fa-tasks',
                        'items' => ['Rawat Peliharaan', 'Pasang Gas/Galon', 'Jaga Anak'],
                    ],
                    'Jasa Nemenin' => [
                        'icon' => 'fas fa-users',
                        'items' => [
                            'Teman Ngopi',
                            'Teman Nonton',
                            'Teman Curhat',
                            'Teman Acara (Kondangan, Pesta, Wisuda, dll)',
                            'Teman Wisata / Liburan',
                            'Night Ride',
                        ],
                    ],
                    'Laundry' => [
                        'icon' => 'fas fa-tshirt',
                        'items' => ['Antar Cuci Sepeda', 'Antar Cuci Mobil', 'Antar Cuci Baju'],
                    ],
                    'All Service' => [
                        'icon' => 'fas fa-tools',
                        'items' => ['Antar Service Sepeda', 'Antar Service Mobil'],
                    ],
                    'Travel' => [
                        'icon' => 'fas fa-car',
                        'items' => ['Driver Only', 'Rental Motor', 'Rental Mobil'],
                    ],
                    'Editing' => [
                        'icon' => 'fas fa-camera',
                        'items' => ['Edit Foto/Video', 'Fotographer', 'Videographer'],
                    ],
                    'Bantuan Online' => [
                        'icon' => 'fas fa-headset',
                        'items' => ['SleepCall', 'Stalker', 'Joki Game', 'Buzzer'],
                    ],
                    'Joki Tugas' => [
                        'icon' => 'fas fa-book',
                        'items' => ['Skripsi', 'Makalah', 'Praktikum', 'Pekerjaan Rumah (PR)'],
                    ],
                ];
            @endphp

            <div class="container">
                <div class="row g-4">
                    @foreach ($services as $category => $service)
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 hover-shadow">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="{{ $service['icon'] }} fa-lg me-2 text-primary"></i>
                                        <h4 class="card-title fw-bold mb-0">{{ $category }}</h4>
                                    </div>
                                    <ul class="list-unstyled">
                                        @foreach ($service['items'] as $item)
                                            <li class="mb-2 d-flex align-items-center">
                                                <i class="fas fa-circle text-primary me-2" style="font-size: 0.5rem;"></i>
                                                <span>{{ $item }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- WhatsApp Order Button -->
                <div class="text-center mt-5">
                    <button class="btn btn-success btn-lg order-btn">
                        <i class="fab fa-whatsapp me-2"></i>Pesan Sekarang
                    </button>
                </div>
            </div>
    </section>

    <style>
        .hover-shadow {
            transition: all 0.3s ease;
        }

        .hover-shadow:hover {
            transform: translateY(-5px);
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important;
        }

        .transition {
            transition: all 0.3s ease;
        }
    </style>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('.order-btn').click(function(e) {
                e.preventDefault();
                const service = $(this).data('service');

                Swal.fire({
                    title: "Apakah anda yakin?",
                    text: "Apakah anda yakin ingin memesan jasa ini?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Ya, pesan!",
                    cancelButtonText: "Batal",
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `{{ route('kustom.pesan') }}`,
                            method: 'POST',
                            data: {
                                // csrf
                                _token: '{{ csrf_token() }}',
                                // jasa: service,
                                // total_harga: price,
                                // alamat: result.value
                            },
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        title: 'Berhasil',
                                        text: 'Pesanan berhasil dibuat, Anda akan diarahkan ke WhatsApp Admin',
                                        icon: 'success'
                                    }).then(() => {
                                        const message =
                                            // Hii kak, saya ingin meminta bantuan To Help untuk (isi sesuai kebutuhan kalian)
                                            `Hii kak, saya ingin meminta bantuan To Help untuk *(isi sesuai kebutuhan kalian)*` +
                                            `Order ID : ${response.order_id}\n...`;

                                        window.open(
                                            `https://api.whatsapp.com/send?phone=6285695908981&text=${encodeURIComponent(message)}`,
                                            '_blank'
                                        );
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Gagal',
                                        text: 'Pesanan gagal dibuat, silahkan coba lagi',
                                        icon: 'error'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    title: 'Gagal',
                                    text: 'Pesanan gagal dibuat, silahkan coba lagi',
                                    icon: 'error'
                                });
                            }
                        });
                    }
                });

                // const message =
                //     `Format Pemesanan\n` +
                //     `Jenis layanan: ${service}\n` +
                //     `Nama: \n` +
                //     `No. HP/WA: \n` +
                //     `Alamat: \n` +
                //     `Tanggal/Waktu: \n` +
                //     `Detail pesanan: \n` +
                //     `Catatan tambahan: `;

                // window.open(
                //     `https://api.whatsapp.com/send?phone=6285695908981&text=${encodeURIComponent(message)}`,
                //     '_blank'
                // );
            });
        });
    </script>
@endpush
