<section class="padding-small">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold">{{ $layanan->nama ?? 'Layanan' }}</h2>
            <p class="lead">{{ $layanan->deskripsi ?? '' }}</p>
        </div>

        <div class="row g-4 justify-content-center">
            @if(isset($layanan) && $layanan->subLayanans->isNotEmpty())
                @foreach($layanan->subLayanans as $sub)
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden">
                            <div class="card-body p-4 d-flex flex-column">
                                <h5 class="card-title fw-bold text-dark mb-2">{{ $sub->nama }}</h5>
                                
                                <h3 class="card-text text-primary fw-bold my-3">
                                    {{ $sub->getFormattedDisplayPrice($globalActiveCabang?->id ?? null) }}
                                </h3>

                                @if($sub->deskripsi)
                                    <p class="card-text text-muted mb-4 flex-grow-1" style="white-space: pre-line;">{!! nl2br(e($sub->deskripsi)) !!}</p>
                                @else
                                    <div class="flex-grow-1"></div>
                                @endif

                                @if($catatan = $sub->getCatatanNbForCabang($globalActiveCabang?->id ?? null))
                                    <div class="alert alert-light border small text-muted py-2 px-3 mb-3">
                                        ℹ️ {{ $catatan }}
                                    </div>
                                @endif

                                <a href="#" class="btn btn-success w-100 order-btn py-2 fw-semibold rounded-3 shadow-sm"
                                    data-service="{{ $sub->nama }}" 
                                    data-price="{{ (int) $sub->getHargaForCabang($globalActiveCabang?->id ?? null) }}"
                                    data-satuan="{{ $sub->getSatuanForCabang($globalActiveCabang?->id ?? null) }}">
                                    <i class="fab fa-whatsapp me-1"></i> Pesan Sekarang
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="col-12 text-center py-5">
                    <div class="alert alert-warning d-inline-block">
                        Layanan ini saat ini belum tersedia di cabang yang Anda pilih. Silakan pilih cabang kota lain atau hubungi admin.
                    </div>
                </div>
            @endif
        </div>

        @if(isset($layanan) && !empty($layanan->catatan_nb))
            <div class="mt-5 p-4 bg-light rounded-4 border text-center">
                <p class="mb-0 text-muted small"><strong>Catatan:</strong> {{ $layanan->catatan_nb }}</p>
            </div>
        @endif
    </div>
</section>

@push('scripts')
    <script>
        $(document).ready(function() {
            $('.order-btn').click(function(e) {
                e.preventDefault();

                const service = $(this).data('service');
                const price = $(this).data('price');
                const satuan = $(this).data('satuan');

                Swal.fire({
                    title: "Apakah anda yakin?",
                    text: `Apakah anda yakin ingin memesan paket ${service}?`,
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonText: "Ya, Pesan Sekarang!",
                    cancelButtonText: "Batal",
                    confirmButtonColor: "#25D366",
                }).then((result) => {
                    if (result.isConfirmed) {
                        const data = {
                            _token: '{{ csrf_token() }}',
                            jasa: service,
                            total_harga: price,
                            satuan: satuan,
                        };

                        $.ajax({
                            url: `{{ route($orderRoute) }}`,
                            method: 'POST',
                            data: data,
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        title: 'Berhasil',
                                        text: 'Pesanan berhasil dibuat, Anda akan diarahkan ke WhatsApp Resmi Cabang',
                                        icon: 'success',
                                        timer: 1500,
                                        showConfirmButton: false
                                    }).then(() => {
                                        const waNumber = response.wa_number || "{{ $globalActiveCabang->formatted_no_wa ?? '6285695908981' }}";
                                        const waMessage = response.wa_message;
                                        window.open(`https://api.whatsapp.com/send?phone=${waNumber}&text=${encodeURIComponent(waMessage)}`, '_blank');
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Gagal',
                                        text: response.message || 'Pesanan gagal dibuat, silahkan coba lagi',
                                        icon: 'error'
                                    });
                                }
                            },
                            error: function(xhr) {
                                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Terjadi kesalahan saat menghubungi server';
                                Swal.fire({
                                    title: 'Gagal',
                                    text: msg,
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
