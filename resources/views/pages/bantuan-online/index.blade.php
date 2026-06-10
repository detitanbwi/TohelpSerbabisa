@extends('layouts.app')

@section('content')
    <section class="padding-small">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">Bantuan Online</h2>
                <p class="lead">Layanan bantuan online profesional untuk berbagai kebutuhan Anda, mulai dari Stalker, Sleep
                    Call, hingga Joki All Game.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Paid Promote SW</h5>
                            <h3 class="card-text text-primary mb-4">Start from <br>Rp 10.000</h3>
                            <a href="#" class="btn btn-success w-100 order-btn" data-service="Paid Promote SW"
                                data-price="10.000">
                                <i class="fab fa-whatsapp"></i> Pesan Sekarang
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Sleep Call</h5>
                            <h2 class="card-text text-primary mb-4">Rp 15.000/jam</h2>
                            <a href="#" class="btn btn-success w-100 order-btn" data-service="Sleep Call"
                                data-price="15.000/jam">
                                <i class="fab fa-whatsapp"></i> Pesan Sekarang
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Buzzer</h5>
                            <h3 class="card-text text-primary mb-4">Start from <br>Rp 50.000</h3>
                            <a href="#" class="btn btn-success w-100 order-btn" data-service="Buzzer" data-price="50.000">
                                <i class="fab fa-whatsapp"></i> Pesan Sekarang
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title text-center">Joki All Game</h5>
                            <h2 class="card-text text-primary mb-4 text-center">Tarif Tanya Admin</h2>
                            <a href="#" class="btn btn-success w-100 order-btn" data-service="Joki All Game"
                                data-price="100.000">
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
        $(document).ready(function () {
            $('.order-btn').click(function (e) {
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
                        $.ajax({
                            url: `{{ route('bantuan.pesan') }}`,
                            method: 'POST',
                            data: {
                                // csrf
                                _token: '{{ csrf_token() }}',
                                jasa: service,
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        title: 'Berhasil',
                                        text: 'Pesanan berhasil dibuat, Anda akan diarahkan ke WhatsApp Admin',
                                        icon: 'success'
                                    }).then(() => {
                                        const message =
                                            `Hii kak, saya ingin meminta bantuan To Help\n\n` +
                                            `Harap Di Isi, Format Order Berikut\n` +
                                            `Order ID : ${response.order_id}\n` +
                                            `Jenis Jasa : Bantuan online\n` +
                                            `Tipe Jasa : ${service}\n` +
                                            `Waktu : \n` +
                                            `Nama : \n` +
                                            `Nomor Whatsapp : `;

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
                            error: function () {
                                Swal.fire({
                                    title: 'Gagal',
                                    text: 'Pesanan gagal dibuat, silahkan coba lagi',
                                    icon: 'error'
                                });
                            }
                        });
                    }
                });

                // $.ajax({
                //     url: `{{ route('bersih.pesan') }}`,
                //     method: 'POST',
                //     data: {
                //         // csrf
                //         _token: '{{ csrf_token() }}',
                //         jasa: service,
                //         price: parseInt(price.replace(/\D/g, ''))
                //     },
                //     success: function(response) {
                //         if (response.status)
                //     }
                // })

                // const message =
                //     `Hello Minhelp, saya ingin meminta bantuan Cleaning Service dan saya sudah membaca Price List di Website\n\n` +
                //     `Harap Di Isi, Format Order Berikut\n` +
                //     `Jasa : ${service}\n` +
                //     `Harga : Rp ${price}\n` +
                //     `Tanggal Pengerjaan : \n` +
                //     `Alamat : `;

                // window.open(
                //     `https://api.whatsapp.com/send?phone=6285695908981&text=${encodeURIComponent(message)}`,
                //     '_blank');
            });
        });
    </script>
@endpush