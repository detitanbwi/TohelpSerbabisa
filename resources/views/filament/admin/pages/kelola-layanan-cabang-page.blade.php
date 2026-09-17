<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Controls: Cabang Selector & Bulk Actions --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex-1 max-w-md">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">
                        Pilih Cabang / Kota
                    </label>
                    <select 
                        wire:model.live="selectedCabangId"
                        class="w-full text-sm font-medium rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        @if(!auth()->user()->hasRole('super_admin')) disabled @endif
                    >
                        @foreach($this->cabangOptions as $id => $nama)
                            <option value="{{ $id }}">{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button 
                        type="button" 
                        wire:click="toggleAll(true)"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 transition"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Aktifkan Semua
                    </button>

                    <button 
                        type="button" 
                        wire:click="toggleAll(false)"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-red-50 dark:bg-red-950 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800 hover:bg-red-100 transition"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Nonaktifkan Semua
                    </button>

                    <button 
                        type="button" 
                        wire:click="save"
                        class="inline-flex items-center px-4 py-2 text-sm font-bold rounded-lg bg-primary-600 text-white shadow hover:bg-primary-500 transition"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                        Simpan Pengaturan
                    </button>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 flex flex-wrap items-center gap-4">
                <span>💡 <strong>Petunjuk:</strong> Centang switch untuk menentukan apakah layanan tersedia di cabang ini.</span>
                <span>• Jika field <em>Harga Khusus</em> atau <em>Satuan Khusus</em> dikosongkan, sistem otomatis memakai tarif default Superadmin.</span>
            </div>
        </div>

        {{-- Group by Layanan Utama --}}
        @php
            $grouped = collect($items)->groupBy('layanan_nama');
        @endphp

        @if($grouped->isEmpty())
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-8 text-center text-gray-500">
                Belum ada data master layanan yang aktif. Silakan tambahkan layanan di menu Master Layanan.
            </div>
        @else
            @foreach($grouped as $layananNama => $subItems)
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <div class="bg-gray-50/80 dark:bg-gray-800/60 px-5 py-3.5 border-b border-gray-200 dark:border-gray-700/60 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-primary-500"></span>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                {{ $layananNama }}
                            </h3>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold">
                                {{ count($subItems) }} Sub-Paket
                            </span>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($subItems as $sub)
                            @php $id = $sub['sub_layanan_id']; @endphp
                            <div class="p-5 transition hover:bg-gray-50/50 dark:hover:bg-gray-800/30 {{ !($items[$id]['is_tersedia'] ?? true) ? 'opacity-60 bg-gray-50/70 dark:bg-gray-900/50' : '' }}">
                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-center">
                                    {{-- Kolom 1: Toggle & Info Sub-Layanan --}}
                                    <div class="lg:col-span-4 space-y-1">
                                        <div class="flex items-center space-x-3">
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input 
                                                    type="checkbox" 
                                                    wire:model.live="items.{{ $id }}.is_tersedia" 
                                                    class="sr-only peer"
                                                >
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-500"></div>
                                            </label>

                                            <div>
                                                <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                                    {{ $sub['sub_nama'] }}
                                                </h4>
                                                <span class="text-xs font-semibold {{ ($items[$id]['is_tersedia'] ?? true) ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400' }}">
                                                    {{ ($items[$id]['is_tersedia'] ?? true) ? '✓ Tersedia di Cabang Ini' : '✗ Tidak Tersedia' }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="text-xs text-gray-500 dark:text-gray-400 pl-14">
                                            Default: <strong>Rp {{ number_format($sub['default_harga'], 0, ',', '.') }}</strong> {{ $sub['default_satuan'] }}
                                            @if($sub['default_label']) <span class="italic">({{ $sub['default_label'] }})</span> @endif
                                        </div>
                                    </div>

                                    {{-- Kolom 2: Custom Harga & Satuan --}}
                                    <div class="lg:col-span-4 grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                                Harga Cabang Ini (Rp)
                                            </label>
                                            <input 
                                                type="number" 
                                                wire:model.defer="items.{{ $id }}.custom_harga"
                                                placeholder="Default ({{ number_format($sub['default_harga'], 0, ',', '.') }})"
                                                class="w-full text-xs font-medium rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                            >
                                        </div>

                                        <div>
                                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                                Satuan Khusus
                                            </label>
                                            <input 
                                                type="text" 
                                                wire:model.defer="items.{{ $id }}.custom_satuan"
                                                placeholder="Default ({{ $sub['default_satuan'] ?: '-' }})"
                                                class="w-full text-xs font-medium rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                            >
                                        </div>
                                    </div>

                                    {{-- Kolom 3: Custom Label & Catatan/NB --}}
                                    <div class="lg:col-span-4 grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                                Label Awalan
                                            </label>
                                            <input 
                                                type="text" 
                                                wire:model.defer="items.{{ $id }}.custom_label"
                                                placeholder="Contoh: Start from"
                                                class="w-full text-xs font-medium rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                            >
                                        </div>

                                        <div>
                                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                                NB Cabang (Opsional)
                                            </label>
                                            <input 
                                                type="text" 
                                                wire:model.defer="items.{{ $id }}.custom_catatan_nb"
                                                placeholder="Catatan khusus cabang..."
                                                class="w-full text-xs font-medium rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            {{-- Bottom Sticky / Floating Save Bar --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm flex items-center justify-between">
                <div class="text-xs text-gray-500">
                    Pastikan menekan tombol <strong>Simpan Pengaturan</strong> setelah melakukan perubahan status ketersediaan atau harga.
                </div>
                <button 
                    type="button" 
                    wire:click="save"
                    class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-lg bg-primary-600 text-white shadow-md hover:bg-primary-500 transition"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                    Simpan Pengaturan Layanan Cabang
                </button>
            </div>
        @endif
    </div>
</x-filament-panels::page>
