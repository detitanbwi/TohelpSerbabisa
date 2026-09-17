@extends('layouts.app')

@section('content')
    <section class="padding-small">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">Jasa Service</h2>
                <p class="lead">Layanan perbaikan dan pemeliharaan untuk berbagai kebutuhan Anda dengan tarif terjangkau
                    dan nyaman.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Service Elektronik</h5>
                            <h2 class="card-text text-primary mb-3">Rp 20.000*</h2>
                            <p class="card-text mb-4">
                                Ketentuan:<br>
                                - Start form 20k/barang<br>
                                - Antar/jemput service 2k/km
                            </p>
                            <a href="#" class="btn btn-success w-100 order-btn" data-service="Service Elektronik" data-barang="Elektronik"
                                data-price="20.000">
                                <i class="fab fa-whatsapp"></i> Pesan Sekarang
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Service Kendaraan</h5>
                            <h2 class="card-text text-primary mb-3">Rp 15.000*</h2>
                            <p class="card-text mb-4">
                                Ketentuan:<br>
                                - Jasa tunggu service 15k/jam (Diluar Service)<br>
                                - Antar/jemput service 2k/km
                            </p>
                            <a href="#" class="btn btn-success w-100 order-btn" data-service="Service Kendaraan" data-barang="Kendaraan"
                                data-price="15.000">
                                <i class="fab fa-whatsapp"></i> Pesan Sekarang
                            </a>
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
                const jenisBarang = $(this).data('barang');

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
                            url: `{{ route('service.pesan') }}`,
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
                                            `Jenis Jasa : Service\n` +
                                            `Jenis barang : ${jenisBarang}\n` +
                                            `Keluhan/kerusakan :\n` +
                                            `Ambil : (ya/tidak)?\n` +
                                            `Jika iya, Alamat ambil di : \n` +
                                            `Hari/tanggal : \n` +
                                            `Waktu : \n` +
                                            `Nama : \n` +
                                            `Nomor WhatsApp : \n` +
                                            `Payment (cash/TF) : `;

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
