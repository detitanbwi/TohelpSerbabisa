@extends('layouts.app')

@section('content')
    <section class="padding-small">
        <div class="container">
            <div class="mb-5 text-center">
                <h2 class="display-4 fw-bold">Jasa Nemenin</h2>
                <p class="lead">Layanan menemani aktivitas Anda seperti ngopi, nonton, atau kegiatan lainnya dengan tarif
                    terjangkau dan nyaman.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Tarif Durasi</h5>
                            <h2 class="card-text text-primary mb-4">15k/jam</h2>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Paket Setengah Hari</h5>
                            <h2 class="card-text text-primary mb-3">100k</h2>
                            <p class="card-text mb-0">
                                Durasi: 7 jam
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Paket Satu Hari</h5>
                            <h2 class="card-text text-primary mb-3">150k</h2>
                            <p class="card-text mb-0">
                                Durasi: 14 jam
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Paket Full Time</h5>
                            <h2 class="card-text text-primary mb-3">250k</h2>
                            <p class="card-text mb-0">
                                Durasi: 24 jam
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Transport</h5>
                            <h2 class="card-text text-primary mb-3">2k/km</h2>
                            <p class="card-text mb-0">
                                (tidak berlaku jika kendaraan dari customer)
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">NB :</h5>
                            <ol class="mb-0">
                                <li>Tarif durasi dan Transport dihitung bergantian atau tidak beriringan</li>
                                <li>Semua biaya di atas belum include biaya akomodasi tiket, parkir, konsumsi, dan
                                    penginapan jika di perlukan</li>
                                <li>Paket full time berlaku hanya ketika dalam nemenin yang mengharuskan stanby 24 jam</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <a class="btn btn-success w-100 order-btn" data-service="Nemenin" data-price="15.000" href="#">
                        <i class="fab fa-whatsapp"></i> Pesan Sekarang
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

                        $.ajax({
                            url: `{{ route('nemenin.pesan') }}`,
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
                                            `Jenis Jasa : Nemenin\n` +
                                            `Permintaan (pilih salah satu) : ngopi/nonton/night ride/yang lain…\n` +
                                            `Hari/tanggal : \n` +
                                            `Waktu : \n` +
                                            `Nama : \n` +
                                            `Pilih Talent : \n` +
                                            `Nomor WhatsApp : \n` +
                                            `Payment (Cash/TF) : \n\n` +
                                            `Dijemput / Menjemput : (tulis alamat kalian apabila ingin dijemput)\n` +
                                            `Noted : (untuk cek talent, bisa kunjungi website di menu bagian profile. Bingung? Tanya admin)`;

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
