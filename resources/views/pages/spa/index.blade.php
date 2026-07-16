@extends('layouts.app')

@section('content')
    <section class="padding-small">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">Jasa Penitipan Barang</h2>
                <p class="lead">Layanan packing, unboxing, serta penitipan barang dan kendaraan aman terpercaya.</p>
            </div>

            <div class="row g-4 justify-content-center">
                <!-- Packing -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body text-center d-flex flex-column justify-content-between py-4">
                            <div>
                                <div class="mb-3 text-primary">
                                    <i class="fas fa-box fa-3x"></i>
                                </div>
                                <h5 class="card-title fw-bold text-dark mb-2">Packing</h5>
                                <p class="text-muted small">Jasa pembungkusan barang secara rapi dan aman menggunakan kardus/bubble wrap.</p>
                            </div>
                            <div>
                                <h4 class="card-text text-primary fw-bold my-3"><small class="text-muted fs-6">mulai dari</small><br>Rp 15.000<span class="fs-6 text-muted">/kardus</span></h4>
                                <a href="#" class="btn btn-success w-100 order-btn" data-service="Packing">
                                    <i class="fab fa-whatsapp"></i> PESAN
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Unboxing -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body text-center d-flex flex-column justify-content-between py-4">
                            <div>
                                <div class="mb-3 text-primary">
                                    <i class="fas fa-box-open fa-3x"></i>
                                </div>
                                <h5 class="card-title fw-bold text-dark mb-2">Unboxing</h5>
                                <p class="text-muted small">Jasa pembongkaran paket belanja atau barang pindahan dengan hati-hati.</p>
                            </div>
                            <div>
                                <h4 class="card-text text-primary fw-bold my-3"><small class="text-muted fs-6">mulai dari</small><br>Rp 5.000<span class="fs-6 text-muted">/paket</span></h4>
                                <a href="#" class="btn btn-success w-100 order-btn" data-service="Unboxing">
                                    <i class="fab fa-whatsapp"></i> PESAN
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Titip Barang -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body text-center d-flex flex-column justify-content-between py-4">
                            <div>
                                <div class="mb-3 text-primary">
                                    <i class="fas fa-boxes fa-3x"></i>
                                </div>
                                <h5 class="card-title fw-bold text-dark mb-2">Titip Barang</h5>
                                <p class="text-muted small">Penitipan barang/paket harian yang aman dan terpantau dengan baik.</p>
                            </div>
                            <div>
                                <h4 class="card-text text-primary fw-bold my-3"><small class="text-muted fs-6">mulai dari</small><br>Rp 20.000<span class="fs-6 text-muted">/hari</span></h4>
                                <a href="#" class="btn btn-success w-100 order-btn" data-service="Titip Barang">
                                    <i class="fab fa-whatsapp"></i> PESAN
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Titip Kendaraan -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body text-center d-flex flex-column justify-content-between py-4">
                            <div>
                                <div class="mb-3 text-primary">
                                    <i class="fas fa-motorcycle fa-3x"></i>
                                </div>
                                <h5 class="card-title fw-bold text-dark mb-2">Titip Kendaraan</h5>
                                <p class="text-muted small">Penitipan kendaraan (motor/mobil) harian di area yang aman dan teduh.</p>
                            </div>
                            <div>
                                <h4 class="card-text text-primary fw-bold my-3"><small class="text-muted fs-6">mulai dari</small><br>Rp 10.000<span class="fs-6 text-muted">/hari</span></h4>
                                <a href="#" class="btn btn-success w-100 order-btn" data-service="Titip Kendaraan">
                                    <i class="fab fa-whatsapp"></i> PESAN
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- NB Card -->
                <div class="col-12 mt-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3 fw-bold text-dark">NB :</h5>
                            <ul class="mb-0 text-muted">
                                <li>Perhitungan harga berdasarkan volume, berat, ukuran barang, serta jenis kendaraan. Untuk informasi harga pasti silakan hubungi admin.</li>
                            </ul>
                        </div>
                    </div>
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
                    text: `Apakah anda yakin ingin memesan layanan ${service}?`,
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
                            url: `{{ route('penitipan.pesan') }}`,
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
                                            `Jenis Jasa : SPA\n` +
                                            `Layanan : ${service}\n` +
                                            `Nama / Deskripsi Barang : \n` +
                                            `Estimasi Durasi / Jumlah : \n` +
                                            `Nama Pelanggan : \n` +
                                            `Nomor WhatsApp : \n` +
                                            `Alamat Lengkap : `;

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
