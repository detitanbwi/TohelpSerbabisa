@extends('layouts.app')

@section('content')
    <section class="padding-small">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">Jasa Titip</h2>
                <p class="lead">Layanan jasa titip untuk makanan, minuman, atau barang dengan harga terjangkau dan
                    pelayanan cepat.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-12">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Makanan, Minuman, atau Barang</h5>
                            <h2 class="card-text text-primary mb-3">Start from Rp 9.000*</h2>
                            <p class="card-text mb-4">
                                Ketentuan:<br>
                                - Start from 9k (Jarak tempuh 3.5km)<br>
                                - Rincian biaya transport 2k/km + 2k/outlet<br>
                                - Bisa Jastip disemua outlet (kecuali alkohol / sejenisnya)<br>
                                - Antri pemesanan 5k/30 menit, 15k/jam (Jika ada)<br>
                            </p>
                            <a href="#" class="btn btn-success w-100 order-btn" data-service="Makanan/Minuman/Barang"
                                data-price="9.000">
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
                // console.log(parseInt(price.replace(/\D/g, '')));

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
                            url: `{{ route('jastip.pesan') }}`,
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
                                            `Hello Minhelp, saya ingin meminta bantuan To Help\n\n` +
                                            `Harap Di Isi, Format Order Berikut\n` +
                                            `ID Order : ${response.order_id}\n` +
                                            `Jenis pesanan : Jastip\n` +
                                            `Jasa : ${service}\n` +
                                            `Alamat Ambil/Beli : \n` +
                                            `List order : \n1. ......\n2. ......\n3. ......\n\n` +
                                            `Alamat tujuan / kirim : \n` +
                                            `Atas nama : \n` +
                                            `Payment (Cash/TF) : \n\n` +
                                            `No HP Penerima : \n\n` +
                                            `No HP Pengirim : (apabila pengirim dan penerima sama, di isi salah satu aja)\n\n` +
                                            `Noted : Tetap berikan sharelok kepada driver untuk membantu memudahkan pengambilan / pengantaran 🙏🏻`;

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
