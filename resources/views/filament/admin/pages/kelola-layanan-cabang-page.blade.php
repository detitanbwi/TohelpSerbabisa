<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Card: Cabang Selector & Global Actions --}}
        <x-filament::section>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Pilih Cabang / Kota:</span>
                    <x-filament::input.wrapper class="min-w-[220px]">
                        <x-filament::input.select wire:model.live="selectedCabangId" :disabled="!auth()->user()->hasRole('super_admin')">
                            @foreach($this->cabangOptions as $id => $nama)
                                <option value="{{ $id }}">{{ $nama }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <x-filament::button color="gray" size="sm" icon="heroicon-o-check-circle" wire:click="toggleAll(true)">
                        Aktifkan Semua
                    </x-filament::button>

                    <x-filament::button color="gray" size="sm" icon="heroicon-o-x-circle" wire:click="toggleAll(false)">
                        Nonaktifkan Semua
                    </x-filament::button>
                </div>
            </div>

            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                💡 <strong>Petunjuk:</strong> Manager Cabang berhak menentukan tarif aktual untuk <strong>Cabang {{ $this->selectedCabangNama }}</strong>. Anda dapat menyesuaikan tarif Ojek, Taxi, dan paket jasa lainnya dengan tetap merujuk pada <strong>Rekomendasi Super Admin</strong>. Tekan tombol <strong>Simpan Pengaturan</strong> di bagian paling bawah untuk menerapkan perubahan.
            </div>
        </x-filament::section>

        {{-- Kuota Radius Bebas Biaya Jemput Basecamp --}}
        <x-filament::section icon="heroicon-o-map-pin" :heading="'Kuota Radius Bebas Biaya Jemput Basecamp - Cabang ' . $this->selectedCabangNama">
            <div class="max-w-md">
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Batas Radius Penjemputan Gratis (KM)</label>
                <x-filament::input.wrapper suffix="KM">
                    <x-filament::input
                        type="number"
                        step="0.1"
                        min="0"
                        wire:model.defer="free_distance_km"
                        placeholder="{{ $rekomendasi['free_distance_km'] ?? 3.0 }}"
                    />
                </x-filament::input.wrapper>
                <span class="text-[11px] text-gray-500 mt-1 block">
                    Batas jarak penjemputan driver dari basecamp yang bebas biaya surcharge. (Rekomendasi: <strong>{{ $rekomendasi['free_distance_km'] ?? 3.0 }} KM</strong>)
                </span>
            </div>
        </x-filament::section>

        {{-- Section Divider: Pengaturan & Urutan Layanan --}}
        <div class="pt-2">
            <div class="flex items-center gap-3">
                <span class="text-base font-bold text-gray-900 dark:text-white">Daftar & Urutan Layanan Cabang</span>
                <span class="h-px bg-gray-200 dark:bg-gray-700 flex-1"></span>
            </div>
            <p class="text-xs text-gray-500 mt-1">
                Atur urutan tampilan layanan di website cabang ini dengan <strong>menyeret (drag & drop)</strong> kartu layanan atau menggunakan tombol <strong>⬆️ / ⬇️</strong>. Anda juga dapat menyesuaikan tarif khusus atau ketersediaan masing-masing layanan.
            </p>
        </div>

        <style>
            .sortable-ghost {
                opacity: 0.35 !important;
                border: 2px dashed #f59e0b !important;
                border-radius: 0.75rem !important;
            }
            .sortable-chosen {
                cursor: grabbing !important;
            }
            .sortable-drag {
                opacity: 0.95 !important;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15) !important;
                transform: rotate(0.5deg);
            }
        </style>

        {{-- Draggable Accordion List per Layanan with SortableJS --}}
        <div 
            x-data="{
                initSortable() {
                    const setup = () => {
                        if (typeof Sortable === 'undefined') return;
                        if (this.sortableInstance) {
                            this.sortableInstance.destroy();
                        }
                        this.sortableInstance = new Sortable(this.$refs.sortableList, {
                            handle: '.drag-handle',
                            animation: 200,
                            ghostClass: 'sortable-ghost',
                            chosenClass: 'sortable-chosen',
                            dragClass: 'sortable-drag',
                            onEnd: (evt) => {
                                const elements = Array.from(this.$refs.sortableList.querySelectorAll('[data-layanan-id]'));
                                const orderedIds = elements.map(el => parseInt(el.getAttribute('data-layanan-id')));
                                $wire.updateLayananOrder(orderedIds);
                            }
                        });
                    };

                    if (typeof Sortable === 'undefined') {
                        const script = document.createElement('script');
                        script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js';
                        script.onload = setup;
                        document.head.appendChild(script);
                    } else {
                        setup();
                    }
                }
            }"
            x-init="initSortable()"
            x-ref="sortableList"
            class="space-y-4"
        >
            @forelse($this->layanans as $layanan)
                @php
                    $isOjek = $layanan->slug === 'ojek';
                    $isTaxi = in_array($layanan->slug, ['mobil', 'taxi']);
                    $isTransport = $isOjek || $isTaxi || $layanan->is_transportasi;
                    
                    $subCount = count($layanan->subLayanans);
                    $activeCount = 0;
                    if ($isOjek) {
                        $isActive = $is_ojek_aktif;
                    } elseif ($isTaxi) {
                        $isActive = $is_taxi_aktif;
                    } else {
                        foreach ($layanan->subLayanans as $s) {
                            if ($items[$s->id]['is_tersedia'] ?? true) {
                                $activeCount++;
                            }
                        }
                        $isActive = $activeCount > 0;
                    }
                @endphp

                <div 
                    wire:key="cabang-layanan-card-{{ $layanan->id }}"
                    data-layanan-id="{{ $layanan->id }}"
                    class="transition-all duration-150 select-none"
                >
                    <x-filament::section 
                        collapsible 
                        :collapsed="true"
                    >
                        <x-slot name="heading">
                            <div class="flex items-center gap-2.5">
                                {{-- Drag Handle Icon Only --}}
                                <div 
                                    class="drag-handle cursor-grab active:cursor-grabbing p-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-500 hover:text-gray-800 dark:hover:text-gray-200 transition mr-1" 
                                    onclick="event.stopPropagation()" 
                                    title="Tahan dan geser (drag & drop) untuk memindahkan urutan"
                                >
                                    <x-filament::icon icon="heroicon-o-bars-3" class="w-5 h-5" />
                                </div>

                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary-50 text-primary-700 dark:bg-primary-950 dark:text-primary-300 font-bold text-xs border border-primary-200 dark:border-primary-800">
                                    {{ $loop->iteration }}
                                </span>
                                <img src="{{ $layanan->image_url }}" alt="{{ $layanan->nama }}" class="w-7 h-7 rounded object-cover shadow-sm bg-white p-0.5 border">
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $layanan->nama }}</span>
                            </div>
                        </x-slot>

                        <x-slot name="headerEnd">
                            <div class="flex items-center gap-2" onclick="event.stopPropagation()">
                                @if($isOjek)
                                    <x-filament::badge :color="$is_ojek_aktif ? 'success' : 'danger'" size="sm">
                                        {{ $is_ojek_aktif ? 'Layanan Aktif' : 'Nonaktif' }}
                                    </x-filament::badge>
                                    <x-filament::button color="gray" size="xs" wire:click="resetTransportRates('ojek')" icon="heroicon-o-arrow-path">
                                        Reset Rekomendasi
                                    </x-filament::button>
                                @elseif($isTaxi)
                                    <x-filament::badge :color="$is_taxi_aktif ? 'success' : 'danger'" size="sm">
                                        {{ $is_taxi_aktif ? 'Layanan Aktif' : 'Nonaktif' }}
                                    </x-filament::badge>
                                    <x-filament::button color="gray" size="xs" wire:click="resetTransportRates('taxi')" icon="heroicon-o-arrow-path">
                                        Reset Rekomendasi
                                    </x-filament::button>
                                @else
                                    <x-filament::badge :color="$activeCount > 0 ? 'success' : 'danger'" size="sm">
                                        {{ $activeCount }}/{{ $subCount }} Paket Aktif
                                    </x-filament::badge>

                                    <x-filament::button 
                                        color="gray" 
                                        size="xs" 
                                        wire:click="toggleLayananGroup({{ $layanan->id }}, true)"
                                    >
                                        Aktifkan Semua
                                    </x-filament::button>

                                    <x-filament::button 
                                        color="gray" 
                                        size="xs" 
                                        wire:click="toggleLayananGroup({{ $layanan->id }}, false)"
                                    >
                                        Nonaktifkan Semua
                                    </x-filament::button>
                                @endif
                            </div>
                        </x-slot>

                        @if($isOjek)
                            {{-- Konten Ojek --}}
                            <div class="space-y-4">
                                <label class="flex items-center gap-3 cursor-pointer p-2 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700">
                                    <input type="checkbox" wire:model.live="is_ojek_aktif" class="w-4 h-4 rounded text-primary-600 focus:ring-primary-500">
                                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">Buka Layanan Ojek di Cabang {{ $this->selectedCabangNama }}</span>
                                </label>

                                @if($is_ojek_aktif)
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tarif Minimum</label>
                                            <x-filament::input.wrapper prefix="Rp">
                                                <x-filament::input
                                                    type="number"
                                                    min="0"
                                                    wire:model.defer="ojek_tarif_minimum"
                                                    placeholder="{{ $rekomendasi['ojek_tarif_minimum'] ?? 7000 }}"
                                                />
                                            </x-filament::input.wrapper>
                                            <span class="text-[11px] text-gray-500 mt-1 block">
                                                Rekomendasi: <strong>Rp {{ number_format($rekomendasi['ojek_tarif_minimum'] ?? 7000, 0, ',', '.') }}</strong>
                                            </span>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Surcharge Jemput</label>
                                            <x-filament::input.wrapper prefix="Rp" suffix="/KM">
                                                <x-filament::input
                                                    type="number"
                                                    min="0"
                                                    wire:model.defer="ojek_surcharge_per_km"
                                                    placeholder="{{ $rekomendasi['ojek_surcharge_per_km'] ?? 1000 }}"
                                                />
                                            </x-filament::input.wrapper>
                                            <span class="text-[11px] text-gray-500 mt-1 block">
                                                Rekomendasi: <strong>Rp {{ number_format($rekomendasi['ojek_surcharge_per_km'] ?? 1000, 0, ',', '.') }}/KM</strong>
                                            </span>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tarif Per KM (Standar)</label>
                                            <x-filament::input.wrapper prefix="Rp" suffix="/KM">
                                                <x-filament::input
                                                    type="number"
                                                    min="0"
                                                    wire:model.defer="ojek_tarif_per_km"
                                                    placeholder="{{ $rekomendasi['ojek_tarif_per_km'] ?? 2000 }}"
                                                />
                                            </x-filament::input.wrapper>
                                            <span class="text-[11px] text-gray-500 mt-1 block">
                                                Rekomendasi: <strong>Rp {{ number_format($rekomendasi['ojek_tarif_per_km'] ?? 2000, 0, ',', '.') }}/KM</strong>
                                            </span>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tarif Jarak Jauh (> 10 KM)</label>
                                            <x-filament::input.wrapper prefix="Rp" suffix="/KM">
                                                <x-filament::input
                                                    type="number"
                                                    min="0"
                                                    wire:model.defer="ojek_tarif_per_km_lanjutan"
                                                    placeholder="{{ $rekomendasi['ojek_tarif_per_km_lanjutan'] ?? 'Flat' }}"
                                                />
                                            </x-filament::input.wrapper>
                                            <span class="text-[11px] text-gray-500 mt-1 block">
                                                @if(!empty($rekomendasi['ojek_tarif_per_km_lanjutan']))
                                                    Rekomendasi: <strong>Rp {{ number_format($rekomendasi['ojek_tarif_per_km_lanjutan'], 0, ',', '.') }}/KM</strong>
                                                @else
                                                    Opsional (Kosongkan jika flat)
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    <div class="text-xs text-amber-600 dark:text-amber-400 p-2 rounded bg-amber-50 dark:bg-amber-950/30">
                                        ⚠️ Layanan Ojek di cabang ini dinonaktifkan.
                                    </div>
                                @endif
                            </div>
                        @elseif($isTaxi)
                            {{-- Konten Taxi --}}
                            <div class="space-y-4">
                                <label class="flex items-center gap-3 cursor-pointer p-2 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700">
                                    <input type="checkbox" wire:model.live="is_taxi_aktif" class="w-4 h-4 rounded text-primary-600 focus:ring-primary-500">
                                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">Buka Layanan Taxi di Cabang {{ $this->selectedCabangNama }}</span>
                                </label>

                                @if($is_taxi_aktif)
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tarif Min. (1-3 KM)</label>
                                            <x-filament::input.wrapper prefix="Rp">
                                                <x-filament::input
                                                    type="number"
                                                    min="0"
                                                    wire:model.defer="taxi_tarif_minimum"
                                                    placeholder="{{ $rekomendasi['taxi_tarif_minimum'] ?? 18000 }}"
                                                />
                                            </x-filament::input.wrapper>
                                            <span class="text-[11px] text-gray-500 mt-1 block">
                                                Rekomendasi: <strong>Rp {{ number_format($rekomendasi['taxi_tarif_minimum'] ?? 18000, 0, ',', '.') }}</strong>
                                            </span>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Surcharge Jemput</label>
                                            <x-filament::input.wrapper prefix="Rp" suffix="/KM">
                                                <x-filament::input
                                                    type="number"
                                                    min="0"
                                                    wire:model.defer="taxi_surcharge_per_km"
                                                    placeholder="{{ $rekomendasi['taxi_surcharge_per_km'] ?? 2000 }}"
                                                />
                                            </x-filament::input.wrapper>
                                            <span class="text-[11px] text-gray-500 mt-1 block">
                                                Rekomendasi: <strong>Rp {{ number_format($rekomendasi['taxi_surcharge_per_km'] ?? 2000, 0, ',', '.') }}/KM</strong>
                                            </span>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tarif Per KM (3-10 KM)</label>
                                            <x-filament::input.wrapper prefix="Rp" suffix="/KM">
                                                <x-filament::input
                                                    type="number"
                                                    min="0"
                                                    wire:model.defer="taxi_tarif_per_km"
                                                    placeholder="{{ $rekomendasi['taxi_tarif_per_km'] ?? 5000 }}"
                                                />
                                            </x-filament::input.wrapper>
                                            <span class="text-[11px] text-gray-500 mt-1 block">
                                                Rekomendasi: <strong>Rp {{ number_format($rekomendasi['taxi_tarif_per_km'] ?? 5000, 0, ',', '.') }}/KM</strong>
                                            </span>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tarif Jarak Jauh (> 10 KM)</label>
                                            <x-filament::input.wrapper prefix="Rp" suffix="/KM">
                                                <x-filament::input
                                                    type="number"
                                                    min="0"
                                                    wire:model.defer="taxi_tarif_per_km_lanjutan"
                                                    placeholder="{{ $rekomendasi['taxi_tarif_per_km_lanjutan'] ?? 4000 }}"
                                                />
                                            </x-filament::input.wrapper>
                                            <span class="text-[11px] text-gray-500 mt-1 block">
                                                Rekomendasi: <strong>Rp {{ number_format($rekomendasi['taxi_tarif_per_km_lanjutan'] ?? 4000, 0, ',', '.') }}/KM</strong>
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    <div class="text-xs text-amber-600 dark:text-amber-400 p-2 rounded bg-amber-50 dark:bg-amber-950/30">
                                        ⚠️ Layanan Taxi di cabang ini dinonaktifkan.
                                    </div>
                                @endif
                            </div>
                        @else
                            {{-- Tabel Horizontal Compact Sub-Layanan Reguler --}}
                            <div class="overflow-x-auto -mx-6 -my-6">
                                <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                                    <thead style="background: rgba(100, 116, 139, 0.06); border-bottom: 1px solid rgba(100, 116, 139, 0.15);">
                                        <tr>
                                            <th style="padding: 10px 16px; font-weight: 700; font-size: 11px; text-transform: uppercase; color: #64748b; min-width: 240px;">Sub-Layanan / Paket</th>
                                            <th style="padding: 10px 16px; font-weight: 700; font-size: 11px; text-transform: uppercase; color: #64748b; text-align: center; width: 90px;">Tersedia</th>
                                            <th style="padding: 10px 16px; font-weight: 700; font-size: 11px; text-transform: uppercase; color: #64748b; min-width: 180px;">Harga Cabang (Rp)</th>
                                            <th style="padding: 10px 16px; font-weight: 700; font-size: 11px; text-transform: uppercase; color: #64748b; min-width: 170px;">Satuan Khusus</th>
                                            <th style="padding: 10px 16px; font-weight: 700; font-size: 11px; text-transform: uppercase; color: #64748b; min-width: 220px;">Catatan Cabang (NB)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($layanan->subLayanans as $sub)
                                            @php 
                                                $id = $sub->id; 
                                                $isAvailable = $items[$id]['is_tersedia'] ?? true;
                                            @endphp
                                            <tr style="border-bottom: 1px solid rgba(100, 116, 139, 0.1); opacity: {{ $isAvailable ? '1' : '0.55' }};">
                                                {{-- 1. Nama & Default Info --}}
                                                <td style="padding: 12px 16px; vertical-align: middle;">
                                                    <div style="font-weight: 700;" class="text-gray-900 dark:text-white">
                                                        {{ $sub->nama }}
                                                    </div>
                                                    <div style="font-size: 11px; color: #64748b;" class="dark:text-gray-400 mt-0.5">
                                                        Default Superadmin: <strong>Rp {{ number_format($sub->default_harga, 0, ',', '.') }}</strong> {{ $sub->default_satuan }}
                                                    </div>
                                                </td>

                                                {{-- 2. Checkbox Tersedia --}}
                                                <td style="padding: 12px 16px; vertical-align: middle; text-align: center;">
                                                    <label style="display: inline-flex; align-items: center; justify-content: center; cursor: pointer;">
                                                        <input 
                                                            type="checkbox" 
                                                            wire:model.live="items.{{ $id }}.is_tersedia" 
                                                            style="width: 18px; height: 18px; cursor: pointer; accent-color: #f59e0b;"
                                                        >
                                                    </label>
                                                </td>

                                                {{-- 3. Custom Harga --}}
                                                <td style="padding: 10px 16px; vertical-align: middle;">
                                                    <x-filament::input.wrapper prefix="Rp">
                                                        <x-filament::input
                                                            type="text"
                                                            inputmode="numeric"
                                                            wire:model.defer="items.{{ $id }}.custom_harga"
                                                            placeholder="{{ number_format($sub->default_harga, 0, ',', '.') }}"
                                                        />
                                                    </x-filament::input.wrapper>
                                                </td>

                                                {{-- 4. Custom Satuan dengan Datalist Dropdown --}}
                                                <td style="padding: 10px 16px; vertical-align: middle;">
                                                    <datalist id="satuan-options-{{ $id }}">
                                                        <option value="/ paket"></option>
                                                        <option value="/ jam"></option>
                                                        <option value="/ hari"></option>
                                                        <option value="/ orang"></option>
                                                        <option value="/ kg"></option>
                                                        <option value="/ meter"></option>
                                                        <option value="/ sesi"></option>
                                                        <option value="/ pcs"></option>
                                                    </datalist>
                                                    <x-filament::input.wrapper>
                                                        <x-filament::input
                                                            type="text"
                                                            list="satuan-options-{{ $id }}"
                                                            wire:model.defer="items.{{ $id }}.custom_satuan"
                                                            placeholder="Default ({{ $sub->default_satuan ?: '-' }})"
                                                        />
                                                    </x-filament::input.wrapper>
                                                </td>

                                                {{-- 5. Custom Catatan/NB --}}
                                                <td style="padding: 10px 16px; vertical-align: middle;">
                                                    <x-filament::input.wrapper>
                                                        <x-filament::input
                                                            type="text"
                                                            wire:model.defer="items.{{ $id }}.custom_catatan_nb"
                                                            placeholder="Catatan khusus cabang..."
                                                        />
                                                    </x-filament::input.wrapper>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </x-filament::section>
                </div>
            @empty
                <x-filament::section>
                    <div class="text-center py-6 text-gray-500">
                        Belum ada layanan yang aktif. Silakan tambahkan layanan di Master Layanan.
                    </div>
                </x-filament::section>
            @endforelse
        </div>

        {{-- Bottom Sticky Save Bar --}}
        <x-filament::section>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <span class="text-xs text-gray-500">
                    Setelah mengubah checklist atau mengisi tarif khusus, tekan tombol <strong>Simpan Pengaturan</strong> untuk menerapkan ke cabang terpilih.
                </span>
                <x-filament::button color="primary" icon="heroicon-o-check" wire:click="save">
                    Simpan Pengaturan Layanan Cabang
                </x-filament::button>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
