<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Top Bar / Quick Navigation --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="flex items-center gap-3">
                <x-filament::icon-button
                    tag="a"
                    href="{{ \App\Filament\Admin\Resources\TransaksiResource::getUrl('index') }}"
                    icon="heroicon-o-arrow-left"
                    color="gray"
                    size="md"
                    label="Kembali ke Daftar Transaksi"
                    class="border border-gray-200 dark:border-gray-700 shadow-xs"
                />
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Order ID</span>
                        <h2 class="text-lg font-bold text-gray-950 dark:text-white tracking-tight">{{ $record->order_id ?? '#' . $record->id }}</h2>
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                        Dipesan pada {{ $record->created_at ? $record->created_at->locale('id')->translatedFormat('d F Y, H:i') . ' WIB' : '-' }}
                        &bull; Cabang: <strong>{{ $record->cabang->nama ?? 'Pusat' }}</strong>
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Badge Status Transaksi --}}
                @php
                    $statusTransaksiColor = match ($record->status_transaksi) {
                        'sukses' => 'success',
                        'belum' => 'warning',
                        'batal' => 'danger',
                        default => 'gray',
                    };
                    $statusTransaksiLabel = match ($record->status_transaksi) {
                        'sukses' => 'Sukses Bayar',
                        'belum' => 'Belum Selesai',
                        'batal' => 'Dibatalkan',
                        default => $record->status_transaksi ?? '-',
                    };

                    $statusTugasColor = match ($record->status_tugas) {
                        'selesai' => 'success',
                        'proses' => 'info',
                        'belum' => 'warning',
                        default => 'gray',
                    };
                    $statusTugasLabel = match ($record->status_tugas) {
                        'selesai' => 'Tugas Selesai',
                        'proses' => 'Sedang Diproses',
                        'belum' => 'Belum Dikerjakan',
                        default => $record->status_tugas ?? '-',
                    };
                @endphp

                <x-filament::badge :color="$statusTransaksiColor" size="md">
                    {{ $statusTransaksiLabel }}
                </x-filament::badge>

                <x-filament::badge :color="$statusTugasColor" size="md">
                    {{ $statusTugasLabel }}
                </x-filament::badge>

                @if($record->titik_jemput && $record->titik_tujuan)
                    <x-filament::button
                        tag="a"
                        href="https://www.google.com/maps/dir/?api=1&origin={{ urlencode($record->titik_jemput) }}&destination={{ urlencode($record->titik_tujuan) }}" 
                        target="_blank"
                        color="info"
                        size="xs"
                        icon="heroicon-o-map"
                        outlined>
                        Peta Rute
                    </x-filament::button>
                @endif
            </div>
        </div>

        {{-- Main Layout Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left / Primary Content (2 Columns) --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- 1. Section: Perubahan & Rincian Harga --}}
                <x-filament::section icon="heroicon-o-currency-dollar">
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <span class="text-base font-semibold text-gray-950 dark:text-white">Rincian & Ubah Harga Transaksi</span>
                            <span class="text-xs font-normal text-gray-500 dark:text-gray-400">Kelola tarif & tip driver</span>
                        </div>
                    </x-slot>

                    <form wire:submit.prevent="updateHarga" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-800 dark:text-gray-200 mb-1">
                                    Total Harga Layanan <span class="text-rose-500 dark:text-rose-400">*</span>
                                </label>
                                <x-filament::input.wrapper prefix="Rp" class="bg-white dark:!bg-gray-900 dark:border-gray-700">
                                    <x-filament::input
                                        type="number"
                                        min="0"
                                        wire:model.live="total_harga"
                                        placeholder="0"
                                        required
                                        class="bg-transparent dark:!bg-gray-900 dark:text-white"
                                    />
                                </x-filament::input.wrapper>
                                @error('total_harga')
                                    <span class="text-xs text-rose-600 dark:text-rose-400 mt-1 block font-medium">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-800 dark:text-gray-200 mb-1">
                                    Tip Driver / Petugas
                                </label>
                                <x-filament::input.wrapper prefix="Rp" class="bg-white dark:!bg-gray-900 dark:border-gray-700">
                                    <x-filament::input
                                        type="number"
                                        min="0"
                                        wire:model.live="tip"
                                        placeholder="0"
                                        class="bg-transparent dark:!bg-gray-900 dark:text-white"
                                    />
                                </x-filament::input.wrapper>
                                @error('tip')
                                    <span class="text-xs text-rose-600 dark:text-rose-400 mt-1 block font-medium">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Financial Calculation Card --}}
                        <div class="p-4 rounded-xl bg-gray-50 dark:!bg-gray-900 border border-gray-200 dark:border-gray-800 space-y-2.5">
                            <div class="flex items-center justify-between text-xs text-gray-700 dark:text-gray-300">
                                <span>Harga Layanan / Jasa</span>
                                <span class="font-semibold text-gray-950 dark:text-gray-100">
                                    Rp {{ number_format((int) ($total_harga ?? 0), 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-gray-700 dark:text-gray-300">
                                <span>Tip Driver / Helpman</span>
                                <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                                    + Rp {{ number_format((int) ($tip ?? 0), 0, ',', '.') }}
                                </span>
                            </div>
                            @if($record->komisi_admin)
                                <div class="flex items-center justify-between text-xs text-gray-700 dark:text-gray-300">
                                    <span>Komisi Admin (Termasuk)</span>
                                    <span class="font-semibold text-gray-950 dark:text-gray-100">
                                        Rp {{ number_format((int) $record->komisi_admin, 0, ',', '.') }}
                                    </span>
                                </div>
                            @endif
                            <div class="pt-2.5 border-t border-gray-200 dark:border-gray-700/80 flex items-center justify-between">
                                <span class="text-sm font-bold text-gray-950 dark:text-white">Total Tagihan / Pembayaran</span>
                                <span class="text-base font-extrabold text-amber-600 dark:text-amber-400">
                                    Rp {{ number_format(((int) ($total_harga ?? 0)) + ((int) ($tip ?? 0)), 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Perubahan harga akan langsung memperbarui nominal tagihan transaksi ini.
                            </p>
                            <x-filament::button type="submit" color="primary" icon="heroicon-o-check">
                                <span wire:loading.remove wire:target="updateHarga">Simpan Perubahan Harga</span>
                                <span wire:loading wire:target="updateHarga">Menyimpan...</span>
                            </x-filament::button>
                        </div>
                    </form>
                </x-filament::section>

                {{-- 2. Section: Informasi Mengenai Helpman & Penugasan --}}
                <x-filament::section icon="heroicon-o-user-group">
                    <x-slot name="heading">
                        <div class="flex items-center justify-between w-full">
                            <div class="flex items-center gap-2">
                                <span class="text-base font-semibold text-gray-950 dark:text-white">Informasi Helpman (Petugas Lapangan)</span>
                                <x-filament::badge color="info" size="xs">
                                    {{ $record->tugas->count() }} Ditugaskan
                                </x-filament::badge>
                            </div>
                        </div>
                    </x-slot>

                    <div class="space-y-4">
                        {{-- Daftar Helpman yang Ditugaskan --}}
                        @if($record->tugas && $record->tugas->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                @foreach($record->tugas as $helpman)
                                    @php
                                        $pivotIsSelesai = (bool) ($helpman->pivot->is_selesai ?? false);
                                        $today = today()->toDateString();
                                        $isStandByToday = \App\Models\Absensi::where('karyawan_id', $helpman->id)
                                            ->whereDate('tanggal', $today)
                                            ->exists();
                                    @endphp
                                    <div class="relative p-4 rounded-xl border {{ $pivotIsSelesai ? 'border-emerald-300 bg-emerald-50/50 dark:border-emerald-800 dark:bg-gray-900' : 'border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900' }} shadow-xs transition-all">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex items-center gap-3">
                                                <img src="{{ $helpman->avatar_photo_url }}" alt="{{ $helpman->name }}" class="w-11 h-11 rounded-full object-cover border border-gray-200 dark:border-gray-700" />
                                                <div>
                                                    <h4 class="text-sm font-bold text-gray-950 dark:text-white leading-tight">
                                                        {{ $helpman->name }}
                                                    </h4>
                                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                                        &#64;{{ $helpman->username ?? '-' }} &bull; {{ $helpman->email ?? '-' }}
                                                    </p>
                                                    <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700">
                                                            {{ ucwords($helpman->tipe_karyawan ?? 'Helpman') }}
                                                        </span>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700">
                                                            {{ $helpman->cabang->nama ?? 'Cabang ' . ($record->cabang->nama ?? '-') }}
                                                        </span>
                                                        @if($isStandByToday)
                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                                Stand By
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                                                                Belum Presensi
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Task Status & Action Buttons --}}
                                        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between gap-2">
                                            <div>
                                                @if($pivotIsSelesai)
                                                    <x-filament::badge color="success" size="sm">
                                                        ✓ Selesai Bertugas
                                                    </x-filament::badge>
                                                @else
                                                    <x-filament::badge color="warning" size="sm">
                                                        Sedang Bertugas
                                                    </x-filament::badge>
                                                @endif
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <x-filament::button 
                                                    size="xs" 
                                                    :color="$pivotIsSelesai ? 'gray' : 'success'"
                                                    :outlined="$pivotIsSelesai"
                                                    wire:click="toggleHelpmanStatus({{ $helpman->id }})">
                                                    {{ $pivotIsSelesai ? 'Batal Selesai' : 'Tandai Selesai' }}
                                                </x-filament::button>

                                                <x-filament::button 
                                                    size="xs" 
                                                    color="danger" 
                                                    outlined
                                                    icon="heroicon-o-trash"
                                                    wire:click="removeHelpman({{ $helpman->id }})"
                                                    wire:confirm="Yakin ingin membatalkan penugasan Helpman {{ $helpman->name }} untuk transaksi ini?">
                                                    Lepas
                                                </x-filament::button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            {{-- Empty State: Belum ada helpman --}}
                            <div class="p-6 rounded-xl border border-dashed border-gray-300 dark:border-gray-800 text-center bg-gray-50 dark:!bg-gray-900/50">
                                <div class="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 mx-auto flex items-center justify-center mb-3 border border-amber-200 dark:border-amber-800">
                                    <x-heroicon-o-user-plus class="w-6 h-6" />
                                </div>
                                <h4 class="text-sm font-bold text-gray-950 dark:text-white">Belum Ada Helpman Ditugaskan</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400 max-w-sm mx-auto mt-1 mb-2">
                                    Pesanan ini belum memiliki helpman atau petugas lapangan yang ditugaskan. Pilih helpman di bawah untuk menugaskan.
                                </p>
                            </div>
                        @endif

                        {{-- Form Penugasan Helpman Baru --}}
                        <div class="mt-4 p-4 rounded-xl bg-gray-50 dark:!bg-gray-900 border border-gray-200 dark:border-gray-800">
                            <span class="block text-xs font-semibold text-gray-800 dark:text-gray-200 mb-2">
                                + Tugaskan Personil Helpman Baru:
                            </span>
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                <div class="flex-1">
                                    <x-filament::input.wrapper class="bg-white dark:!bg-gray-900 dark:border-gray-700">
                                        <x-filament::input.select wire:model.defer="selectedHelpmanId" class="bg-white dark:!bg-gray-900 dark:text-white">
                                            <option value="" class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">-- Pilih Personil Helpman --</option>
                                            @foreach($availableHelpmans as $helpmanOption)
                                                <option value="{{ $helpmanOption['id'] }}" class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
                                                    {{ $helpmanOption['name'] }} (&#64;{{ $helpmanOption['username'] }}) - {{ $helpmanOption['cabang'] }} {{ $helpmanOption['is_stand_by'] ? '⚡ [Stand By]' : '' }}
                                                </option>
                                            @endforeach
                                        </x-filament::input.select>
                                    </x-filament::input.wrapper>
                                </div>
                                <x-filament::button color="info" icon="heroicon-o-user-plus" wire:click="assignHelpman">
                                    Tugaskan Helpman
                                </x-filament::button>
                            </div>
                            <span class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 block">
                                💡 Tip: Personil dengan status <strong>⚡ [Stand By]</strong> menandakan telah melakukan presensi aktif pada hari ini.
                            </span>
                        </div>
                    </div>
                </x-filament::section>

                {{-- 3. Section: Rute & Lokasi (Jika Tersedia) --}}
                @if($record->titik_jemput || $record->titik_tujuan || $record->jarak)
                    <x-filament::section icon="heroicon-o-map-pin">
                        <x-slot name="heading">
                            <span class="text-base font-semibold text-gray-950 dark:text-white">Rute & Lokasi Penjemputan / Tujuan</span>
                        </x-slot>

                        <div class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="p-3.5 rounded-lg bg-gray-50 dark:!bg-gray-900 border border-gray-200 dark:border-gray-800">
                                    <div class="flex items-center gap-2 text-xs font-bold text-emerald-700 dark:text-emerald-400 mb-1">
                                        <x-heroicon-o-map-pin class="w-4 h-4" />
                                        Titik Penjemputan
                                    </div>
                                    <p class="text-xs text-gray-900 dark:text-gray-100 font-medium">
                                        {{ $record->titik_jemput ?? 'Tidak ditentukan' }}
                                    </p>
                                </div>

                                <div class="p-3.5 rounded-lg bg-gray-50 dark:!bg-gray-900 border border-gray-200 dark:border-gray-800">
                                    <div class="flex items-center gap-2 text-xs font-bold text-rose-700 dark:text-rose-400 mb-1">
                                        <x-heroicon-o-map-pin class="w-4 h-4" />
                                        Titik Tujuan
                                    </div>
                                    <p class="text-xs text-gray-900 dark:text-gray-100 font-medium">
                                        {{ $record->titik_tujuan ?? 'Tidak ditentukan' }}
                                    </p>
                                </div>
                            </div>

                            @if($record->jarak)
                                <div class="flex items-center justify-between text-xs p-3 rounded-lg bg-amber-50 text-amber-900 border border-amber-200 dark:!bg-gray-900 dark:text-amber-300 dark:border-amber-700/50">
                                    <span class="font-medium">Perkiraan Jarak Tempuh:</span>
                                    <span class="font-bold text-sm">{{ $record->jarak }} km</span>
                                </div>
                            @endif

                            @if($record->titik_jemput && $record->titik_tujuan)
                                <div class="pt-1">
                                    <x-filament::button
                                        tag="a"
                                        href="https://www.google.com/maps/dir/?api=1&origin={{ urlencode($record->titik_jemput) }}&destination={{ urlencode($record->titik_tujuan) }}" 
                                        target="_blank" 
                                        color="gray"
                                        icon="heroicon-o-arrow-top-right-on-square"
                                        size="sm">
                                        Buka Navigasi Rute di Google Maps
                                    </x-filament::button>
                                </div>
                            @endif
                        </div>
                    </x-filament::section>
                @endif
            </div>

            {{-- Right / Sidebar Column (1 Column) --}}
            <div class="space-y-6">

                {{-- Status & Quick Control Card --}}
                <x-filament::section icon="heroicon-o-cog-6-tooth">
                    <x-slot name="heading">
                        <span class="text-base font-semibold text-gray-950 dark:text-white">Status Transaksi & Tugas</span>
                    </x-slot>

                    <div class="space-y-4">
                        {{-- Status Transaksi Controller --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-800 dark:text-gray-200 mb-2">
                                Status Transaksi
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <x-filament::button 
                                    type="button" 
                                    size="sm"
                                    :color="$record->status_transaksi === 'sukses' ? 'success' : 'gray'"
                                    :outlined="$record->status_transaksi !== 'sukses'"
                                    wire:click="setStatusTransaksi('sukses')"
                                    class="w-full justify-center">
                                    Sukses
                                </x-filament::button>

                                <x-filament::button 
                                    type="button" 
                                    size="sm"
                                    :color="$record->status_transaksi === 'belum' ? 'warning' : 'gray'"
                                    :outlined="$record->status_transaksi !== 'belum'"
                                    wire:click="setStatusTransaksi('belum')"
                                    class="w-full justify-center">
                                    Belum
                                </x-filament::button>

                                <x-filament::button 
                                    type="button" 
                                    size="sm"
                                    :color="$record->status_transaksi === 'batal' ? 'danger' : 'gray'"
                                    :outlined="$record->status_transaksi !== 'batal'"
                                    wire:click="setStatusTransaksi('batal')"
                                    wire:confirm="Yakin ingin membatalkan transaksi ini?"
                                    class="w-full justify-center">
                                    Batal
                                </x-filament::button>
                            </div>
                        </div>

                        {{-- Status Pengerjaan Tugas Controller --}}
                        <div class="pt-3 border-t border-gray-100 dark:border-gray-800">
                            <label class="block text-xs font-semibold text-gray-800 dark:text-gray-200 mb-2">
                                Status Pengerjaan (Helpman)
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <x-filament::button 
                                    type="button" 
                                    size="sm"
                                    :color="$record->status_tugas === 'belum' ? 'warning' : 'gray'"
                                    :outlined="$record->status_tugas !== 'belum'"
                                    wire:click="setStatusTugas('belum')"
                                    class="w-full justify-center">
                                    Belum
                                </x-filament::button>

                                <x-filament::button 
                                    type="button" 
                                    size="sm"
                                    :color="$record->status_tugas === 'proses' ? 'info' : 'gray'"
                                    :outlined="$record->status_tugas !== 'proses'"
                                    wire:click="setStatusTugas('proses')"
                                    class="w-full justify-center">
                                    Proses
                                </x-filament::button>

                                <x-filament::button 
                                    type="button" 
                                    size="sm"
                                    :color="$record->status_tugas === 'selesai' ? 'success' : 'gray'"
                                    :outlined="$record->status_tugas !== 'selesai'"
                                    wire:click="setStatusTugas('selesai')"
                                    class="w-full justify-center">
                                    Selesai
                                </x-filament::button>
                            </div>
                        </div>
                    </div>
                </x-filament::section>

                {{-- Informasi Detail Pesanan & Layanan --}}
                <x-filament::section icon="heroicon-o-information-circle">
                    <x-slot name="heading">
                        <span class="text-base font-semibold text-gray-950 dark:text-white">Informasi Pesanan</span>
                    </x-slot>

                    <dl class="divide-y divide-gray-100 dark:divide-gray-800 text-xs">
                        <div class="py-2.5 flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Jenis Layanan</dt>
                            <dd class="font-bold text-gray-950 dark:text-white capitalize">
                                {{ $record->jenis ?? '-' }}
                            </dd>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Jasa / Paket</dt>
                            <dd class="font-medium text-gray-950 dark:text-white text-right max-w-[60%]">
                                {{ $record->jasa ?? '-' }}
                            </dd>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Cabang</dt>
                            <dd class="font-medium text-gray-950 dark:text-white">
                                {{ $record->cabang->nama ?? 'Pusat' }}
                            </dd>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Voucher</dt>
                            <dd class="font-medium text-gray-950 dark:text-white">
                                {{ $record->voucher->nama ?? 'Tidak Ada' }}
                            </dd>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Waktu Order</dt>
                            <dd class="text-gray-800 dark:text-gray-300 font-medium">
                                {{ $record->created_at ? $record->created_at->locale('id')->translatedFormat('d M Y H:i') : '-' }}
                            </dd>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Terakhir Update</dt>
                            <dd class="text-gray-800 dark:text-gray-300 font-medium">
                                {{ $record->updated_at ? $record->updated_at->locale('id')->translatedFormat('d M Y H:i') : '-' }}
                            </dd>
                        </div>
                    </dl>
                </x-filament::section>

            </div>
        </div>
    </div>
</x-filament-panels::page>
