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
    public string $search = '';

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
            return 'Layanan & Tarif: ' . $cabang->nama;
        }
        return 'Pengaturan Layanan & Tarif Cabang';
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

    public function toggleAll(bool $status): void
    {
        foreach ($this->items as $id => $item) {
            $this->items[$id]['is_tersedia'] = $status;
        }

        Notification::make()
            ->title('Status Diubah')
            ->body($status ? 'Semua layanan telah diaktifkan untuk cabang ini.' : 'Semua layanan telah dinonaktifkan.')
            ->info()
            ->send();
    }

    public function resetBranchCustom(int $subLayananId): void
    {
        if (isset($this->items[$subLayananId])) {
            $this->items[$subLayananId]['custom_harga'] = null;
            $this->items[$subLayananId]['custom_satuan'] = null;
            $this->items[$subLayananId]['custom_label'] = null;
            $this->items[$subLayananId]['custom_catatan_nb'] = null;

            Notification::make()
                ->title('Kembali ke Default')
                ->body('Harga dan satuan sub-layanan ini dikembalikan ke default Superadmin.')
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
                CabangLayanan::updateOrCreate(
                    [
                        'cabang_id' => $this->selectedCabangId,
                        'sub_layanan_id' => $subId,
                    ],
                    [
                        'is_tersedia' => (bool) ($data['is_tersedia'] ?? true),
                        'custom_harga' => ($data['custom_harga'] !== '' && $data['custom_harga'] !== null) ? (float) $data['custom_harga'] : null,
                        'custom_satuan' => !empty($data['custom_satuan']) ? trim($data['custom_satuan']) : null,
                        'custom_label' => !empty($data['custom_label']) ? trim($data['custom_label']) : null,
                        'custom_catatan_nb' => !empty($data['custom_catatan_nb']) ? trim($data['custom_catatan_nb']) : null,
                    ]
                );
            }

            DB::commit();

            $cabang = Cabang::find($this->selectedCabangId);
            Notification::make()
                ->title('Berhasil Disimpan')
                ->body('Pengaturan ketersediaan layanan dan tarif khusus untuk Cabang ' . ($cabang?->nama ?? '') . ' berhasil diperbarui.')
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
