<div class="container" id="services-container">
    <div class="text-center mb-5">
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 mb-3 rounded-pill bg-light border text-primary fw-semibold small shadow-sm">
            <i class="fas fa-map-marker-alt"></i>
            <span>Wilayah Layanan: {{ $activeCabang->nama ?? ($globalActiveCabang->nama ?? 'Cabang Utama') }}</span>
        </div>
        <h2 class="display-5 fw-bold">Layanan Kami yang Tersedia</h2>
        <p class="lead text-muted">Solusi praktis dan terpercaya yang siap melayani kebutuhan Anda di wilayah <strong>{{ $activeCabang->nama ?? ($globalActiveCabang->nama ?? 'cabang terpilih') }}</strong>.</p>
    </div>

    <div class="py-2">
        @if(isset($layanans) && $layanans->isNotEmpty())
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                @foreach ($layanans as $layanan)
                    <div class="col">
                        <div class="card card-custom h-100 border-0 shadow-sm bg-white">
                            <div class="card-body p-4 d-flex flex-column">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="card-header-icon shadow-sm"
                                        style="background-color: {{ $layanan->color_hex }}; color: white;">
                                        <i class="{{ $layanan->icon_class }} fa-2x"></i>
                                    </div>
                                    <div>
                                        <h4 class="card-title fw-bold mb-0 text-dark">{{ $layanan->nama }}</h4>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-check-circle text-success me-1"></i>Tersedia di {{ $activeCabang->nama ?? ($globalActiveCabang->nama ?? 'Cabang Ini') }}
                                        </small>
                                    </div>
                                </div>

                                @if(!empty($layanan->deskripsi))
                                    <p class="text-muted small mb-3">{{ Str::limit($layanan->deskripsi, 90) }}</p>
                                @endif

                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase fw-bold text-muted small mb-2" style="font-size: 0.75rem; letter-spacing: 0.5px;">Pilihan Paket & Tarif:</h6>
                                    <ul class="list-unstyled mb-3">
                                        @foreach ($layanan->subLayanans as $sub)
                                            <li class="mb-2 d-flex align-items-center justify-content-between gap-2 border-bottom pb-2">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-check-circle me-2"
                                                        style="color: {{ $layanan->color_hex }}; font-size: 0.8rem;"></i>
                                                    <span class="text-dark small fw-medium">{{ $sub->nama }}</span>
                                                </div>
                                                <span class="badge bg-light text-primary border small fw-semibold text-nowrap">
                                                    {{ $sub->getFormattedDisplayPrice($activeCabang->id ?? ($globalActiveCabang->id ?? null)) }}
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0 p-4 pt-0">
                                <a href="{{ $layanan->route_url }}"
                                    class="btn btn-success btn-lg w-100 card-footer-btn fw-semibold"
                                    data-jasa="{{ $layanan->nama }}">
                                    Lihat Layanan & Pesan <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="col-12 text-center py-5">
                <div class="p-5 bg-white rounded-4 shadow-sm border mx-auto" style="max-width: 580px;">
                    <div class="mb-3 text-warning">
                        <i class="fas fa-store-slash fa-4x"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">Layanan Belum Tersedia di Cabang Ini</h4>
                    <p class="text-muted mb-4">
                        Saat ini belum ada layanan aktif yang dibuka untuk wilayah <strong>{{ $activeCabang->nama ?? ($globalActiveCabang->nama ?? 'cabang yang dipilih') }}</strong>.
                        Silakan pilih cabang kota lainnya untuk melihat layanan yang tersedia.
                    </p>
                    <button type="button" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalGlobalPilihLokasi">
                        <i class="fas fa-map-marker-alt me-2"></i> Ganti Cabang / Lokasi
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
