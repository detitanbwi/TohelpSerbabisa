<?php

namespace App\Filament\Admin\Resources\TransaksiResource\Pages;

use App\Filament\Admin\Resources\TransaksiResource;
use App\Models\Absensi;
use App\Models\KaryawanTugas;
use App\Models\Transaksi;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;

class DetailTransaksi extends Page
{
    use InteractsWithRecord;

    protected static string $resource = TransaksiResource::class;

    protected static string $view = 'filament.admin.resources.transaksi-resource.pages.detail-transaksi';

    public ?int $total_harga = null;
    public ?int $tip = null;
    public ?string $status_transaksi = null;
    public ?string $status_tugas = null;
    public ?int $selectedHelpmanId = null;

    public static function canAccess(array $parameters = []): bool
    {
        $user = auth()->user();
        return $user?->hasAnyRole(['super_admin', 'manager_cabang']) 
            || ($user?->can('view_transaksi') ?? false) 
            || ($user?->can('view_any_transaksi') ?? false);
    }

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->record->load(['voucher', 'cabang', 'tugas.cabang', 'karyawanTugas.karyawan']);

        $user = auth()->user();
        if ($user && $user->hasRole('manager_cabang') && ! $user->hasRole('super_admin')) {
            if (! $user->managesCabang($this->record->cabang_id)) {
                abort(403, 'Anda tidak memiliki akses ke transaksi cabang ini.');
            }
        }

        $this->total_harga = (int) ($this->record->total_harga ?? 0);
        $this->tip = (int) ($this->record->tip ?? 0);
        $this->status_transaksi = $this->record->status_transaksi ?? 'sukses';
        $this->status_tugas = $this->record->status_tugas ?? 'belum';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Detail Transaksi ' . ($this->record->order_id ?? ('#' . $this->record->id));
    }

    public function getBreadcrumbs(): array
    {
        return [
            TransaksiResource::getUrl('index') => 'Transaksi',
            '#' => $this->record->order_id ?? 'Detail',
        ];
    }

    protected function getViewData(): array
    {
        return [
            'record' => $this->record,
            'availableHelpmans' => $this->availableHelpmans,
        ];
    }

    public function updatedTotalHarga($value): void
    {
        if ($value !== null && $value !== '' && (int) $value < 0) {
            $this->total_harga = 0;
        }
    }

    public function updatedTip($value): void
    {
        if ($value !== null && $value !== '' && (int) $value < 0) {
            $this->tip = 0;
        }
    }

    public function updateHarga(): void
    {
        $this->validate([
            'total_harga' => 'required|numeric|min:0',
            'tip' => 'nullable|numeric|min:0',
        ], [
            'total_harga.required' => 'Total harga wajib diisi.',
            'total_harga.numeric' => 'Total harga harus berupa angka.',
            'total_harga.min' => 'Total harga tidak boleh bernilai negatif.',
            'tip.numeric' => 'Tip harus berupa angka.',
            'tip.min' => 'Tip tidak boleh bernilai negatif.',
        ]);

        $this->record->update([
            'total_harga' => max(0, (int) $this->total_harga),
            'tip' => max(0, (int) ($this->tip ?? 0)),
        ]);

        $this->record->refresh();

        Notification::make()
            ->title('Harga Berhasil Diperbarui')
            ->body('Total harga dan nominal tip transaksi telah berhasil disimpan.')
            ->success()
            ->send();
    }

    public function setStatusTransaksi(string $status): void
    {
        if (! in_array($status, ['belum', 'sukses', 'batal'])) {
            return;
        }

        $this->record->update(['status_transaksi' => $status]);
        $this->status_transaksi = $status;
        $this->record->refresh();

        $label = match ($status) {
            'belum' => 'Belum Selesai',
            'sukses' => 'Sukses Bayar',
            'batal' => 'Dibatalkan',
            default => $status,
        };

        Notification::make()
            ->title('Status Transaksi Diperbarui')
            ->body("Status transaksi berhasil diubah menjadi: {$label}.")
            ->success()
            ->send();
    }

    public function setStatusTugas(string $status): void
    {
        if (! in_array($status, ['belum', 'proses', 'selesai'])) {
            return;
        }

        $this->record->update(['status_tugas' => $status]);
        $this->status_tugas = $status;

        if ($status === 'selesai') {
            KaryawanTugas::where('tugas_id', $this->record->id)->update(['is_selesai' => true]);
        }

        $this->record->load(['tugas.cabang', 'karyawanTugas.karyawan']);

        $label = match ($status) {
            'belum' => 'Belum Mulai',
            'proses' => 'Sedang Diproses',
            'selesai' => 'Tugas Selesai',
            default => $status,
        };

        Notification::make()
            ->title('Status Tugas Diperbarui')
            ->body("Status pengerjaan tugas berhasil diubah menjadi: {$label}.")
            ->success()
            ->send();
    }

    public function assignHelpman(?int $karyawanId = null): void
    {
        $karyawanId = $karyawanId ?? $this->selectedHelpmanId;

        if (! $karyawanId) {
            Notification::make()
                ->title('Pilih Helpman')
                ->body('Silakan pilih personil Helpman terlebih dahulu.')
                ->warning()
                ->send();
            return;
        }

        if ($this->record->tugas()->where('users.id', $karyawanId)->exists()) {
            Notification::make()
                ->title('Sudah Ditugaskan')
                ->body('Helpman ini sudah ditugaskan pada transaksi ini.')
                ->warning()
                ->send();
            return;
        }

        $this->record->tugas()->attach($karyawanId, [
            'is_selesai' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($this->record->status_tugas === 'belum') {
            $this->record->update(['status_tugas' => 'proses']);
            $this->status_tugas = 'proses';
        }

        $this->selectedHelpmanId = null;
        $this->record->load(['tugas.cabang', 'karyawanTugas.karyawan']);

        Notification::make()
            ->title('Helpman Ditugaskan')
            ->body('Helpman berhasil ditugaskan ke pesanan ini.')
            ->success()
            ->send();
    }

    public function removeHelpman(int $karyawanId): void
    {
        $this->record->tugas()->detach($karyawanId);

        if ($this->record->tugas()->count() === 0 && $this->record->status_tugas === 'proses') {
            $this->record->update(['status_tugas' => 'belum']);
            $this->status_tugas = 'belum';
        }

        $this->record->load(['tugas.cabang', 'karyawanTugas.karyawan']);

        Notification::make()
            ->title('Helpman Dihapus')
            ->body('Penugasan helpman untuk transaksi ini telah dibatalkan.')
            ->info()
            ->send();
    }

    public function toggleHelpmanStatus(int $karyawanId): void
    {
        $pivot = KaryawanTugas::where('tugas_id', $this->record->id)
            ->where('karyawan_id', $karyawanId)
            ->first();

        if ($pivot) {
            $newStatus = ! $pivot->is_selesai;
            $pivot->update(['is_selesai' => $newStatus]);

            $allDone = ! KaryawanTugas::where('tugas_id', $this->record->id)
                ->where('is_selesai', false)
                ->exists();

            if ($allDone && $this->record->status_tugas !== 'selesai') {
                $this->record->update(['status_tugas' => 'selesai']);
                $this->status_tugas = 'selesai';
            }

            $this->record->load(['tugas.cabang', 'karyawanTugas.karyawan']);

            Notification::make()
                ->title('Status Helpman Diperbarui')
                ->body($newStatus ? 'Tugas helpman ditandai selesai.' : 'Status tugas helpman dikembalikan ke sedang bertugas.')
                ->success()
                ->send();
        }
    }

    public function getAvailableHelpmansProperty()
    {
        $query = User::query()
            ->whereDoesntHave('roles', fn ($r) => $r->whereIn('name', ['super_admin', 'manager_cabang']))
            ->where(function ($q) {
                $q->whereHas('roles', fn ($r) => $r->where('name', 'karyawan'))
                  ->orWhereIn('tipe_karyawan', ['helpman', 'joki']);
            });

        $user = auth()->user();
        if ($user && $user->hasRole('manager_cabang') && ! $user->hasRole('super_admin')) {
            $query->whereIn('cabang_id', $user->getCabangIds());
        } elseif ($this->record->cabang_id) {
            // Prioritaskan cabang yang sama dengan transaksi
            $query->where(function ($q) {
                $q->where('cabang_id', $this->record->cabang_id)
                  ->orWhereNull('cabang_id');
            });
        }

        $assignedIds = $this->record->tugas->pluck('id')->toArray();
        if (! empty($assignedIds)) {
            $query->whereNotIn('id', $assignedIds);
        }

        $today = today()->toDateString();
        $checkedInKaryawanIds = Absensi::whereDate('tanggal', $today)
            ->pluck('karyawan_id')
            ->flip()
            ->toArray();

        return $query->with(['cabang'])
            ->orderBy('name')
            ->get()
            ->map(function ($helpman) use ($checkedInKaryawanIds) {
                $isStandBy = isset($checkedInKaryawanIds[$helpman->id]);

                return [
                    'id' => $helpman->id,
                    'name' => $helpman->name,
                    'username' => $helpman->username,
                    'email' => $helpman->email,
                    'cabang' => $helpman->cabang?->nama ?? 'Semua Cabang',
                    'is_stand_by' => $isStandBy,
                    'is_visible' => (bool) $helpman->is_visible,
                    'tipe_karyawan' => $helpman->tipe_karyawan ?? 'helpman',
                ];
            });
    }
}
