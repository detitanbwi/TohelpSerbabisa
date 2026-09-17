@extends('layouts.app')

@section('content')
    <section class="padding-small">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">Joki Tugas</h2>
                <p class="lead">Layanan joki tugas profesional untuk membantu menyelesaikan tugas Anda dengan cepat dan
                    tepat.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Makalah</h5>
                            <h2 class="card-text text-primary mb-3">Start from<br>Rp 100.000</h2>
                            <a href="#" class="btn btn-success w-100 order-btn" data-service="Makalah" data-price="100000">
                                <i class="fab fa-whatsapp"></i> Pesan Sekarang
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Power Point</h5>
                            <h2 class="card-text text-primary mb-3">Start from<br>Rp 50.000</h2>
                            <a href="#" class="btn btn-success w-100 order-btn" data-service="Power Point" data-price="50000">
                                <i class="fab fa-whatsapp"></i> Pesan Sekarang
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Tugas Custom</h5>
                            <h2 class="card-text text-primary mb-3">Start from<br>Rp 30.000</h2>
                            <a href="#" class="btn btn-success w-100 order-btn" data-service="Tugas Custom" data-price="30000">
                                <i class="fab fa-whatsapp"></i> Pesan Sekarang
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Thesis</h5>
                            <h2 class="card-text text-primary mb-3">Start from<br>Rp 3.500.000</h2>
                            <a href="#" class="btn btn-success w-100 order-btn" data-service="Thesis" data-price="3500000">
                                <i class="fab fa-whatsapp"></i> Pesan Sekarang
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Judul Skripsi</h5>
                            <h2 class="card-text text-primary mb-3">Start from<br>Rp 75.000</h2>
                            <a href="#" class="btn btn-success w-100 order-btn" data-service="Judul Skripsi" data-price="75000">
                                <i class="fab fa-whatsapp"></i> Pesan Sekarang
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Proposal</h5>
                            <h2 class="card-text text-primary mb-3">Paket Sempro<br>Rp 750.000</h2>
                            <a href="#" class="btn btn-success w-100 order-btn" data-service="Proposal" data-price="750000">
                                <i class="fab fa-whatsapp"></i> Pesan Sekarang
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Analisis Data</h5>
                            <h2 class="card-text text-primary mb-3">Start from<br>Rp 900.000</h2>
                            <a href="#" class="btn btn-success w-100 order-btn" data-service="Analisis Data" data-price="900000">
                                <i class="fab fa-whatsapp"></i> Pesan Sekarang
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Jurnal</h5>
                            <h2 class="card-text text-primary mb-3">Start from<br>Rp 150.000</h2>
                            <a href="#" class="btn btn-success w-100 order-btn" data-service="Jurnal" data-price="150000">
                                <i class="fab fa-whatsapp"></i> Pesan Sekarang
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Essai</h5>
                            <h2 class="card-text text-primary mb-3">Start from Rp 100.000</h2>
                            <p class="card-text mb-4">
                                *Harga mulai untuk essai standar. Detail harga dapat berubah sesuai panjang dan tingkat kesulitan.
                            </p>
                            <a href="#" class="btn btn-success w-100 order-btn" data-service="Essai" data-price="100000">
                                <i class="fab fa-whatsapp"></i> Pesan Sekarang
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="alert alert-info border">
                        <strong>NB:</strong> apabila ada tugas diluar price list, bisa ditanyakan kepada admin.
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="alert alert-warning border">
                        <strong>Catatan:</strong> Harga bisa berubah sesuai tingkat kesulitan dan mepet tidaknya deadline.
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
                const price = $(this).data('price');

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
                        if (price) {
                            // pastikan total_harga disimpan sebagai integer (dalam rupiah, tanpa pemisah)
                            data.total_harga = parseInt(String(price).replace(/\D/g, ''));
                        }

                        $.ajax({
                            url: `{{ route('joki-tugas.pesan') }}`,
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
                                            `Jenis Jasa : Joki Tugas\n` +
                                            `Tipe Jasa : ${service}\n` +
                                            `Deadline : \n` +
                                            `Nama : \n` +
                                            `Nomor WhatsApp : `;

                                        let CABANG_WA = "{{ $globalActiveCabang->formatted_no_wa ?? '6285695908981' }}";
                                        window.open(
                                            `https://api.whatsapp.com/send?phone=${CABANG_WA}&text=${encodeURIComponent(message)}`,
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
