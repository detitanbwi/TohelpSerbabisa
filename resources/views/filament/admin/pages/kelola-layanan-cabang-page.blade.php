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
                💡 <strong>Petunjuk:</strong> Klik pada salah satu nama layanan di bawah untuk membuka dropdown accordion dan mengatur ketersediaan paket serta tarif khusus di <strong>Cabang {{ $this->selectedCabangNama }}</strong>.
            </div>
        </x-filament::section>

        {{-- Accordion List per Layanan (Collapsed / Minimized by default) --}}
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
                                Aktifkan
                            </x-filament::button>

                            <x-filament::button 
                                color="gray" 
                                size="xs" 
                                wire:click="toggleLayananGroup({{ $layanan->id }}, false)"
                            >
                                Nonaktifkan
                            </x-filament::button>
                        </div>
                    </x-slot>

                    {{-- Daftar Sub Layanan di dalam Accordion --}}
                    <div class="divide-y divide-gray-100 dark:divide-gray-800 -mx-4 -my-4">
                        @foreach($layanan->subLayanans as $sub)
                            @php $id = $sub->id; @endphp
                            <div class="p-4 transition hover:bg-gray-50/60 dark:hover:bg-gray-800/40 {{ !($items[$id]['is_tersedia'] ?? true) ? 'opacity-60 bg-gray-50/80 dark:bg-gray-900/60' : '' }}">
                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-center">
                                    {{-- Info & Switch --}}
                                    <div class="lg:col-span-4 space-y-1">
                                        <div class="flex items-center space-x-3">
                                            <input 
                                                type="checkbox" 
                                                id="sub_{{ $id }}"
                                                wire:model.live="items.{{ $id }}.is_tersedia" 
                                                class="w-4 h-4 text-primary-600 rounded border-gray-300 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-700"
                                            >
                                            <label for="sub_{{ $id }}" class="cursor-pointer">
                                                <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                                    {{ $sub->nama }}
                                                </h4>
                                                <span class="text-xs font-semibold {{ ($items[$id]['is_tersedia'] ?? true) ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400' }}">
                                                    {{ ($items[$id]['is_tersedia'] ?? true) ? '✓ Tersedia di Cabang' : '✗ Dinonaktifkan' }}
                                                </span>
                                            </label>
                                        </div>

                                        <div class="text-xs text-gray-500 dark:text-gray-400 pl-7">
                                            Tarif Default: <strong>Rp {{ number_format($sub->default_harga, 0, ',', '.') }}</strong> {{ $sub->default_satuan }}
                                            @if($sub->label_harga_custom) <span class="italic">({{ $sub->label_harga_custom }})</span> @endif
                                        </div>
                                    </div>

                                    {{-- Custom Harga & Satuan --}}
                                    <div class="lg:col-span-4 grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                                Harga Cabang (Rp)
                                            </label>
                                            <x-filament::input.wrapper>
                                                <x-filament::input
                                                    type="number"
                                                    wire:model.defer="items.{{ $id }}.custom_harga"
                                                    placeholder="Default ({{ number_format($sub->default_harga, 0, ',', '.') }})"
                                                />
                                            </x-filament::input.wrapper>
                                        </div>

                                        <div>
                                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                                Satuan Khusus
                                            </label>
                                            <x-filament::input.wrapper>
                                                <x-filament::input
                                                    type="text"
                                                    wire:model.defer="items.{{ $id }}.custom_satuan"
                                                    placeholder="Default ({{ $sub->default_satuan ?: '-' }})"
                                                />
                                            </x-filament::input.wrapper>
                                        </div>
                                    </div>

                                    {{-- Custom Label & Catatan/NB --}}
                                    <div class="lg:col-span-4 grid grid-cols-2 gap-2 items-end">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                                Label Awalan
                                            </label>
                                            <x-filament::input.wrapper>
                                                <x-filament::input
                                                    type="text"
                                                    wire:model.defer="items.{{ $id }}.custom_label"
                                                    placeholder="Contoh: Start from"
                                                />
                                            </x-filament::input.wrapper>
                                        </div>

                                        <div>
                                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                                NB Cabang (Opsional)
                                            </label>
                                            <x-filament::input.wrapper>
                                                <x-filament::input
                                                    type="text"
                                                    wire:model.defer="items.{{ $id }}.custom_catatan_nb"
                                                    placeholder="Catatan..."
                                                />
                                            </x-filament::input.wrapper>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
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
