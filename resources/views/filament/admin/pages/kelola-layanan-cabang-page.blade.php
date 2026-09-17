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

                    <x-filament::button color="primary" size="sm" icon="heroicon-o-check" wire:click="save">
                        Simpan Pengaturan
                    </x-filament::button>
                </div>
            </div>

            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                💡 <strong>Petunjuk:</strong> Klik salah satu nama layanan di bawah untuk membuka accordion. Ubah centang untuk ketersediaan atau isi harga khusus untuk <strong>Cabang {{ $this->selectedCabangNama }}</strong> (jika kosong otomatis memakai default).
            </div>
        </x-filament::section>

        {{-- Accordion List per Layanan (Collapsed by default) --}}
        <div class="space-y-4">
            @forelse($this->layanans as $layanan)
                @php
                    $subCount = count($layanan->subLayanans);
                    $activeCount = 0;
                    foreach ($layanan->subLayanans as $s) {
                        if ($items[$s->id]['is_tersedia'] ?? true) {
                            $activeCount++;
                        }
                    }
                @endphp

                <x-filament::section 
                    :heading="$layanan->nama" 
                    collapsible 
                    :collapsed="true"
                >
                    <x-slot name="headerEnd">
                        <div class="flex items-center gap-2" onclick="event.stopPropagation()">
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
                        </div>
                    </x-slot>

                    {{-- Tabel Horizontal Compact di dalam Accordion --}}
                    <div class="overflow-x-auto -mx-6 -my-6">
                        <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                            <thead style="background: rgba(100, 116, 139, 0.06); border-bottom: 1px solid rgba(100, 116, 139, 0.15);">
                                <tr>
                                    <th style="padding: 10px 16px; font-weight: 700; font-size: 11px; text-transform: uppercase; color: #64748b; min-width: 220px;">Sub-Layanan / Paket</th>
                                    <th style="padding: 10px 16px; font-weight: 700; font-size: 11px; text-transform: uppercase; color: #64748b; text-align: center; width: 90px;">Tersedia</th>
                                    <th style="padding: 10px 16px; font-weight: 700; font-size: 11px; text-transform: uppercase; color: #64748b; width: 170px;">Harga Cabang (Rp)</th>
                                    <th style="padding: 10px 16px; font-weight: 700; font-size: 11px; text-transform: uppercase; color: #64748b; width: 140px;">Satuan Khusus</th>
                                    <th style="padding: 10px 16px; font-weight: 700; font-size: 11px; text-transform: uppercase; color: #64748b; width: 140px;">Label Awalan</th>
                                    <th style="padding: 10px 16px; font-weight: 700; font-size: 11px; text-transform: uppercase; color: #64748b; min-width: 180px;">Catatan Cabang (NB)</th>
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
                                                Default: <strong>Rp {{ number_format($sub->default_harga, 0, ',', '.') }}</strong> {{ $sub->default_satuan }}
                                                @if($sub->label_harga_custom) <span class="italic">({{ $sub->label_harga_custom }})</span> @endif
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
                                            <x-filament::input.wrapper>
                                                <x-filament::input
                                                    type="number"
                                                    wire:model.defer="items.{{ $id }}.custom_harga"
                                                    placeholder="Default ({{ number_format($sub->default_harga, 0, ',', '.') }})"
                                                />
                                            </x-filament::input.wrapper>
                                        </td>

                                        {{-- 4. Custom Satuan --}}
                                        <td style="padding: 10px 16px; vertical-align: middle;">
                                            <x-filament::input.wrapper>
                                                <x-filament::input
                                                    type="text"
                                                    wire:model.defer="items.{{ $id }}.custom_satuan"
                                                    placeholder="Default ({{ $sub->default_satuan ?: '-' }})"
                                                />
                                            </x-filament::input.wrapper>
                                        </td>

                                        {{-- 5. Custom Label --}}
                                        <td style="padding: 10px 16px; vertical-align: middle;">
                                            <x-filament::input.wrapper>
                                                <x-filament::input
                                                    type="text"
                                                    wire:model.defer="items.{{ $id }}.custom_label"
                                                    placeholder="Contoh: Start from"
                                                />
                                            </x-filament::input.wrapper>
                                        </td>

                                        {{-- 6. Custom Catatan/NB --}}
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
                </x-filament::section>
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
                    Setelah mengubah checklist atau mengisi tarif khusus, tekan tombol <strong>Simpan Pengaturan</strong>.
                </span>
                <x-filament::button color="primary" icon="heroicon-o-check" wire:click="save">
                    Simpan Pengaturan Layanan Cabang
                </x-filament::button>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
