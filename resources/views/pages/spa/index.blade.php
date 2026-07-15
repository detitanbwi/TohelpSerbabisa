@extends('layouts.app')

@section('content')
    <section class="padding-small">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">Titip Barang</h2>
                <p class="lead">Layanan penitipan barang aman dan terpercaya untuk kenyamanan Anda.</p>
            </div>

            <div class="row g-4">
                <!-- Per Jam -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                            <h5 class="card-title fw-bold text-dark mb-3">Per jam</h5>
                            <h2 class="card-text text-primary mb-0"><small class="text-muted fs-6">mulai dari</small> Rp 4.000/jam</h2>
                        </div>
                    </div>
                </div>

                <!-- Harian -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                            <h5 class="card-title fw-bold text-dark mb-3">Harian</h5>
                            <h2 class="card-text text-primary mb-0"><small class="text-muted fs-6">mulai dari</small> Rp 10.000/hari</h2>
                        </div>
                    </div>
                </div>

                <!-- Bulanan -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                            <h5 class="card-title fw-bold text-dark mb-3">Bulanan</h5>
                            <h2 class="card-text text-primary mb-0"><small class="text-muted fs-6">mulai dari</small> Rp 40.000/bulan</h2>
                        </div>
                    </div>
                </div>

                <!-- NB Card -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3 fw-bold text-dark">NB :</h5>
                            <ul class="mb-0">
                                <li>Perhitungan harga berdasarkan waktu, berat dan ukuran barang. Untuk informasi harga fix bisa menghubungi admin</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Center Order Button -->
                <div class="text-center mt-4">
                    <a href="#" class="btn btn-success btn-lg px-5 order-btn" data-service="Titip Barang">
                        <i class="fab fa-whatsapp"></i> PESAN SEKARANG
                    </a>
                </div>

            </div>
        </div>
    </section>
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
                        const data = {
                            _token: '{{ csrf_token() }}',
                            jasa: service,
                        };

                        $.ajax({
                            url: `{{ route('spa.pesan') }}`,
                            method: 'POST',
                            data: data,
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        title: 'Berhasil',
                                        text: 'Pesanan berhasil dibuat, Anda akan diarahkan ke WhatsApp Admin',
                                        icon: 'success'
                                    }).then(() => {
                                        const message =
                                            `Hii kak, saya ingin meminta bantuan To Help\n\n` +
                                            `ID Order : ${response.order_id}\n` +
                                            `Jenis Jasa : Titip Barang\n` +
                                            `Paket : (Per Jam/Harian/Bulanan)\n` +
                                            `Nama Barang / Jumlah : \n` +
                                            `Estimasi Waktu Titip : \n` +
                                            `Nama : \n` +
                                            `Nomor WhatsApp : \n` +
                                            `Alamat Jemput/Titip : `;

                                        window.open(
                                            `https://api.whatsapp.com/send?phone=6285695908981&text=${encodeURIComponent(message)}`,
                                            '_blank');
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
            });
        });
    </script>
@endpush
