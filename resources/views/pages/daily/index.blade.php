@extends('layouts.app')

@section('content')
    <section class="padding-small">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">Daily Activity</h2>
                <p class="lead">Layanan harian profesional untuk memastikan aktivitas Anda berjalan lancar dan nyaman.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-12">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-3">Price List Daily Activity :</h5>
                            <ol class="card-text mb-4 ps-3">
                                <li>Bantuan ringan : 5k / Helpman</li>
                                <li>Bantuan sedang : 10k / Helpman</li>
                                <li>Bantuan berat : start from 20k / helpman</li>
                                <li>Bantuan durasi : 10k/30 menit, 15k / jam</li>
                                <li>Transport : 2k/km (jarak > 3km dari lokasi helpman)</li>
                            </ol>
                            <p class="card-text text-muted mb-4">
                                <strong>NB :</strong> Tarif menyesuaikan kondisi lapangan, tarif pasti di tentukan sebelum pengerjaan
                            </p>
                            <a href="#" class="btn btn-success w-100 order-btn" data-service="Daily Activity">
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
                                            `Masalah Yang Sedang Dihadapi : \n` +
                                            `Bantuan yang di inginkan : \n` +
                                            `Hari/tanggal Bantuan : \n` +
                                            `Waktu : \n` +
                                            `Lokasi Bantuan : \n` +
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

