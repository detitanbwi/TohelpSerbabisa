@extends('layouts.app')

@section('content')
    <section class="padding-small">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">Editing, Fotografer, & Videografer</h2>
                <p class="lead">Layanan profesional untuk editing foto, video, serta jasa fotografer dan videografer untuk
                    berbagai kebutuhan Anda.</p>
            </div>

            <div class="row g-4">
                <div class="container mb-4 border p-4">
                    <h3 class="text-center mb-4">Jasa Editing</h3>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Foto</h5>
                                    <h2 class="card-text text-primary mb-3">Start from Rp 30.000</h2>
                                    <a href="#" class="btn btn-success w-100 order-btn" data-service="Editing Foto"
                                        data-price="30.000">
                                        <i class="fab fa-whatsapp"></i> Pesan Sekarang
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Video</h5>
                                    <h2 class="card-text text-primary mb-3">Start from Rp 50.000</h2>
                                    <a href="#" class="btn btn-success w-100 order-btn" data-service="Editing Video"
                                        data-price="50.000">
                                        <i class="fab fa-whatsapp"></i> Pesan Sekarang
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="container mb-4 border p-4">
                    <h3 class="text-center mb-4">Fotografer</h3>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Fotografer (30 menit)</h5>
                                    <h2 class="card-text text-primary mb-3">Rp 200.000</h2>
                                    <p class="card-text mb-4">Unlimited foto, 15 file edit, free Google Drive</p>
                                    <a href="#" class="btn btn-success w-100 order-btn"
                                        data-service="Fotografer 30 menit" data-price="200.000">
                                        <i class="fab fa-whatsapp"></i> Pesan Sekarang
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Fotografer (45 menit)</h5>
                                    <h2 class="card-text text-primary mb-3">Rp 250.000</h2>
                                    <p class="card-text mb-4">Unlimited foto, 20 file edit, free Google Drive</p>
                                    <a href="#" class="btn btn-success w-100 order-btn"
                                        data-service="Fotografer 45 menit" data-price="250.000">
                                        <i class="fab fa-whatsapp"></i> Pesan Sekarang
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Fotografer (60 menit)</h5>
                                    <h2 class="card-text text-primary mb-3">Rp 300.000</h2>
                                    <p class="card-text mb-4">Unlimited foto, 30 file edit, free Google Drive</p>
                                    <a href="#" class="btn btn-success w-100 order-btn"
                                        data-service="Fotografer 60 menit" data-price="300.000">
                                        <i class="fab fa-whatsapp"></i> Pesan Sekarang
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container mb-4 border p-4">
                    <h3 class="text-center mb-4">Videografer</h3>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Videografer</h5>
                                    <h2 class="card-text text-primary mb-3">Rp 400.000</h2>
                                    <a href="#" class="btn btn-success w-100 order-btn" data-service="Videografer"
                                        data-price="400.000">
                                        <i class="fab fa-whatsapp"></i> Pesan Sekarang
                                    </a>
                                </div>
                            </div>
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
                            total_harga: parseInt(price.replace(/\D/g, '')),
                        };

                        $.ajax({
                            url: `{{ route('editing.pesan') }}`,
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
                                            `Jenis Jasa : Editing\n` +
                                            `Tipe Jasa : ${service}\n` +
                                            `Hari/Tanggal : \n` +
                                            `Lokasi : \n` +
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
