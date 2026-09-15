@php
    $cabangs = $globalCabangs ?? \App\Models\Cabang::all();
    $activeId = $globalActiveCabang->id ?? session('selected_cabang_id') ?? ($cabangs->first()?->id ?? null);
    $hasSelected = $globalHasSelectedCabang ?? session()->has('selected_cabang_id');
@endphp

@if(isset($cabangs) && $cabangs->count() > 0)
    <!-- Modal Global Pemilihan Wilayah / Kota (Tiles Responsive Grid) -->
    <div class="modal fade" id="modalGlobalPilihLokasi" tabindex="-1" aria-labelledby="modalGlobalPilihLokasiLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-0 pb-1 pt-4 px-4 position-relative">
                    <div class="text-center w-100">
                        <div class="d-inline-flex align-items-center justify-content-center bg-warning-subtle text-warning p-2.5 rounded-circle mb-2" style="width: 48px; height: 48px;">
                            <i class="fas fa-map-marked-alt fs-4 text-warning" style="color: #f59e0b !important;"></i>
                        </div>
                        <h5 class="modal-title fw-bold text-dark fs-4" id="modalGlobalPilihLokasiLabel">
                            Pilih Kota Layanan
                        </h5>
                        <p class="text-muted small mb-0 mt-1">Tentukan lokasi Anda untuk menyesuaikan ketersediaan layanan dan personil terdekat:</p>
                    </div>
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body px-4 py-3">
                    <!-- Tiles Grid: min 2 columns (mobile), max 4 columns (desktop) -->
                    <div class="row g-2.5 g-sm-3 row-cols-2 row-cols-sm-3 row-cols-md-4">
                        @foreach($cabangs as $cb)
                            @php
                                $isActive = ($activeId == $cb->id);
                            @endphp
                            <div class="col">
                                <a href="{{ route('set-cabang', ['id' => $cb->id, 'redirect' => url()->current()]) }}" 
                                   onclick="sessionStorage.setItem('tohelp_cabang_selected', 'true')"
                                   class="city-tile-btn text-decoration-none d-flex flex-column align-items-center justify-content-center p-3 rounded-3 border text-center position-relative w-100 {{ $isActive ? 'active' : '' }}">
                                    
                                    <div class="city-tile-icon mb-1.5">
                                        <i class="fas fa-city"></i>
                                    </div>
                                    <span class="city-tile-name fw-bold">{{ $cb->nama }}</span>
                                    
                                    @if($isActive)
                                        <span class="position-absolute top-0 end-0 m-1.5 badge rounded-pill bg-warning text-dark px-1.5 py-0.5" style="font-size: 0.65rem;" title="Lokasi Aktif">
                                            <i class="fas fa-check"></i>
                                        </span>
                                    @endif
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0 pb-3 px-4 justify-content-center text-center">
                    <small class="text-muted" style="font-size: 0.78rem;">
                        <i class="fas fa-info-circle me-1 text-secondary"></i> Lokasi ini berlaku untuk semua layanan ToHelp SerbaBisa.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <style>
        .city-tile-btn {
            background: #ffffff;
            color: #1e293b;
            border: 1.5px solid #e2e8f0;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 84px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            cursor: pointer;
        }
        .city-tile-btn:hover {
            transform: translateY(-2px);
            border-color: #f59e0b;
            box-shadow: 0 6px 14px -2px rgba(245, 158, 11, 0.18);
            color: #d97706;
            background: #ffffff;
        }
        .city-tile-btn .city-tile-icon {
            font-size: 1.25rem;
            color: #64748b;
            transition: color 0.2s;
        }
        .city-tile-btn:hover .city-tile-icon {
            color: #f59e0b;
        }
        .city-tile-btn .city-tile-name {
            font-size: 0.95rem;
            letter-spacing: -0.2px;
        }
        .city-tile-btn.active {
            background: #fffbeb !important;
            border-color: #f59e0b !important;
            color: #b45309 !important;
            box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.25), 0 2px 6px rgba(245, 158, 11, 0.12) !important;
        }
        .city-tile-btn.active .city-tile-icon {
            color: #f59e0b !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const hasSelected = {{ $hasSelected ? 'true' : 'false' }};
            const isServicePage = {{ in_array(request()->route()?->getName(), ['ojek', 'taxi', 'bersih', 'pindahan', 'bantuan', 'jastip', 'daily', 'nemenin', 'service', 'travel', 'editing', 'joki-tugas', 'teknisi', 'penitipan', 'kustom']) ? 'true' : 'false' }};
            
            if (isServicePage && !hasSelected && !sessionStorage.getItem('tohelp_cabang_selected')) {
                const modalEl = document.getElementById('modalGlobalPilihLokasi');
                if (modalEl) {
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                }
            }
        });
    </script>
@endif
