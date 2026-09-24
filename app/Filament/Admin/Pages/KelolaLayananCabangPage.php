<?php

namespace App\Filament\Admin\Pages;

use App\Models\Cabang;
use App\Models\CabangLayanan;
use App\Models\Layanan;
use App\Models\SubLayanan;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;

class KelolaLayananCabangPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Layanan & Tarif Cabang';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.admin.pages.kelola-layanan-cabang-page';

    public ?int $selectedCabangId = null;
    public array $items = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'manager_cabang']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'manager_cabang']) ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        $cabang = $this->selectedCabangId ? Cabang::find($this->selectedCabangId) : null;
        if ($cabang) {
            return 'Layanan & Tarif Cabang: ' . $cabang->nama;
        }
        return 'Pengaturan Layanan & Tarif Cabang';
    }

    public function getSelectedCabangNamaProperty(): string
    {
        $cabang = $this->selectedCabangId ? Cabang::find($this->selectedCabangId) : null;
        return $cabang ? $cabang->nama : 'Cabang Utama';
    }

    public function mount(): void
    {
        $user = auth()->user();
        $queryCabangId = request()->query('cabang');

        if ($queryCabangId && ($user->hasRole('super_admin') || $user->cabang_id == $queryCabangId || $user->managedCabang?->id == $queryCabangId)) {
            $this->selectedCabangId = (int) $queryCabangId;
        } elseif ($user && $user->hasRole('manager_cabang') && !$user->hasRole('super_admin')) {
            $this->selectedCabangId = $user->cabang_id ?: ($user->managedCabang?->id ?: Cabang::first()?->id);
        } else {
            $this->selectedCabangId = Cabang::first()?->id;
        }

        $this->loadBranchData();
    }

    public function updatedSelectedCabangId(): void
    {
        $this->loadBranchData();
    }

    public function loadBranchData(): void
    {
        if (!$this->selectedCabangId) {
            $this->items = [];
            return;
        }

        $existingPivots = CabangLayanan::where('cabang_id', $this->selectedCabangId)
            ->get()
            ->keyBy('sub_layanan_id');

        $allSubLayanans = SubLayanan::with('layanan')
            ->whereHas('layanan', fn ($q) => $q->where('is_active', true))
            ->where('is_active', true)
            ->orderBy('layanan_id')
            ->orderBy('urutan')
            ->get();

        $loaded = [];
        foreach ($allSubLayanans as $sub) {
            $pivot = $existingPivots->get($sub->id);
            $loaded[$sub->id] = [
                'sub_layanan_id' => $sub->id,
                'layanan_id' => $sub->layanan_id,
                'layanan_nama' => $sub->layanan->nama,
                'sub_nama' => $sub->nama,
                'default_harga' => $sub->default_harga,
                'default_satuan' => $sub->default_satuan,
                'default_label' => $sub->label_harga_custom,
                'default_catatan_nb' => $sub->catatan_nb,
                'is_tersedia' => $pivot ? (bool) $pivot->is_tersedia : true,
                'custom_harga' => $pivot && $pivot->custom_harga !== null ? (float) $pivot->custom_harga : null,
                'custom_satuan' => $pivot ? $pivot->custom_satuan : null,
                'custom_label' => $pivot ? $pivot->custom_label : null,
                'custom_catatan_nb' => $pivot ? $pivot->custom_catatan_nb : null,
            ];
        }

        $this->items = $loaded;
    }

    public function getLayanansProperty()
    {
        return Layanan::with(['subLayanans' => function ($q) {
            $q->where('is_active', true)->orderBy('urutan');
        }])
        ->where('is_active', true)
        ->orderBy('urutan')
        ->get();
    }

    public function toggleAll(bool $status): void
    {
        foreach ($this->items as $id => $item) {
            $this->items[$id]['is_tersedia'] = $status;
        }

        Notification::make()
            ->title('Status Diperbarui')
            ->body($status 
                ? 'Semua sub-layanan telah diaktifkan. Silakan klik tombol "Simpan Pengaturan" di bagian bawah untuk menyimpan perubahan.' 
                : 'Semua sub-layanan telah dinonaktifkan. Silakan klik tombol "Simpan Pengaturan" di bagian bawah untuk menyimpan perubahan.')
            ->info()
            ->send();
    }

    public function toggleLayananGroup(int $layananId, bool $status): void
    {
        $layanan = Layanan::with('subLayanans')->find($layananId);
        if ($layanan) {
            foreach ($layanan->subLayanans as $sub) {
                if (isset($this->items[$sub->id])) {
                    $this->items[$sub->id]['is_tersedia'] = $status;
                }
            }

            Notification::make()
                ->title("Grup {$layanan->nama}")
                ->body($status 
                    ? "Semua paket pada {$layanan->nama} diaktifkan. Silakan klik 'Simpan Pengaturan' di bagian bawah." 
                    : "Semua paket pada {$layanan->nama} dinonaktifkan. Silakan klik 'Simpan Pengaturan' di bagian bawah.")
                ->info()
                ->send();
        }
    }

    public function resetSubLayanan(int $subId): void
    {
        if (isset($this->items[$subId])) {
            $this->items[$subId]['custom_harga'] = null;
            $this->items[$subId]['custom_satuan'] = null;
            $this->items[$subId]['custom_label'] = null;
            $this->items[$subId]['custom_catatan_nb'] = null;

            Notification::make()
                ->title('Direset')
                ->body('Tarif dikembalikan ke default Superadmin. Jangan lupa klik "Simpan Pengaturan" di bawah.')
                ->success()
                ->send();
        }
    }

    public function save(): void
    {
        if (!$this->selectedCabangId) {
            Notification::make()
                ->title('Error')
                ->body('Pilih cabang terlebih dahulu.')
                ->danger()
                ->send();
            return;
        }

        DB::beginTransaction();
        try {
            foreach ($this->items as $subId => $data) {
                $rawHarga = $data['custom_harga'] ?? null;
                $parsedHarga = null;
                if ($rawHarga !== '' && $rawHarga !== null) {
                    if (is_string($rawHarga)) {
                        // Strip 'Rp', spaces, dots, and convert to integer
                        $clean = preg_replace('/[^0-9]/', '', $rawHarga);
                        $parsedHarga = $clean !== '' ? (float) $clean : null;
                    } else {
                        $parsedHarga = (float) $rawHarga;
                    }
                }

                CabangLayanan::updateOrCreate(
                    [
                        'cabang_id' => $this->selectedCabangId,
                        'sub_layanan_id' => $subId,
                    ],
                    [
                        'is_tersedia' => (bool) ($data['is_tersedia'] ?? true),
                        'custom_harga' => $parsedHarga,
                        'custom_satuan' => !empty($data['custom_satuan']) ? trim($data['custom_satuan']) : null,
                        'custom_label' => !empty($data['custom_label']) ? trim($data['custom_label']) : null,
                        'custom_catatan_nb' => !empty($data['custom_catatan_nb']) ? trim($data['custom_catatan_nb']) : null,
                    ]
                );
            }

            DB::commit();

            Notification::make()
                ->title('Berhasil Disimpan')
                ->body("Seluruh pengaturan layanan dan tarif Cabang {$this->selectedCabangNama} berhasil diperbarui.")
                ->success()
                ->send();
        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()
                ->title('Gagal Menyimpan')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getCabangOptionsProperty(): array
    {
        $user = auth()->user();
        if ($user && $user->hasRole('manager_cabang') && !$user->hasRole('super_admin')) {
            $cabangId = $user->cabang_id ?: $user->managedCabang?->id;
            return Cabang::where('id', $cabangId)->pluck('nama', 'id')->toArray();
        }

        return Cabang::orderBy('nama')->pluck('nama', 'id')->toArray();
    }
}
