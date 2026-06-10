@extends('layouts.app')

@section('content')
    <section class="padding-small">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">Daily Activity</h2>
                <p class="lead">Layanan harian profesional untuk memastikan aktivitas Anda berjalan lancar dan nyaman.</p>
            </div>

            <div class="row g-4">
                <!-- Bantuan Ringan -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                            <h5 class="card-title fw-bold text-dark mb-3">Bantuan Ringan</h5>
                            <h2 class="card-text text-primary mb-0">5k/helpman</h2>
                        </div>
                    </div>
                </div>

                <!-- Bantuan Sedang -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                            <h5 class="card-title fw-bold text-dark mb-3">Bantuan Sedang</h5>
                            <h2 class="card-text text-primary mb-0">10k/helpman</h2>
                        </div>
                    </div>
                </div>

                <!-- Bantuan Berat -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                            <h5 class="card-title fw-bold text-dark mb-3">Bantuan Berat</h5>
                            <h2 class="card-text text-primary mb-0"><small class="text-muted fs-6">start from</small> 15k/helpman</h2>
                        </div>
                    </div>
                </div>

                <!-- Bantuan Durasi -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                            <h5 class="card-title fw-bold text-dark mb-3">Bantuan Durasi</h5>
                            <h2 class="card-text text-primary mb-0">10k/30 menit, 15k/jam</h2>
                        </div>
                    </div>
                </div>

                <!-- Transport -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                            <h5 class="card-title fw-bold text-dark mb-3">Transport</h5>
                            <h2 class="card-text text-primary mb-2">2k/km</h2>
                            <p class="card-text text-muted mb-0">(jarak > 3km dari lokasi helpman)</p>
                        </div>
                    </div>
                </div>

                <!-- NB Card -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3 fw-bold text-dark">NB :</h5>
                            <ul class="mb-0">
                                <li>Tarif menyesuaikan kondisi lapangan, tarif pasti ditentukan sebelum pengerjaan</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Center Order Button -->
                <div class="text-center mt-4">
                    <a href="#" class="btn btn-success btn-lg px-5 order-btn" data-service="Daily Activity">
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
                            url: `{{ route('daily.pesan') }}`,
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
                                            `Jenis Jasa : Daily Activity\n` +
                                            `Paket : (Durasi/Berat Ringan) \n` +
                                            `Masalah Yang Sedang Dihadapi : \n` +
                                            `Bantuan yang di inginkan : \n` +
                                            `Hari/tanggal Bantuan : \n` +
                                            `Waktu : \n` +
                                            `Lokasi Bantuan : \n` +
                                            `Nama : \n` +
                                            `Nomor WhatsApp : \n` +
                                            `Payment (cash/TF) : `;

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
