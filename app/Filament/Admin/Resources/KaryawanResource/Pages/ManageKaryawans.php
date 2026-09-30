<?php

namespace App\Filament\Admin\Resources\KaryawanResource\Pages;

use Exception;
use App\Models\User;
use App\Models\Cabang;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Admin\Resources\KaryawanResource;
use App\Services\AbsensiService;
use Filament\Notifications\Notification;

class ManageKaryawans extends ManageRecords
{
    protected static string $resource = KaryawanResource::class;

    public function mount(): void
    {
        parent::mount();
        // Sinkronkan status keaktifan presensi karyawan secara otomatis saat halaman dibuka
        AbsensiService::syncDailyEmployeeActiveStatus();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('syncPresensi')
                ->label('Sinkron Presensi Hari Ini')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->tooltip('Sinkronkan status keaktifan personil berdasarkan data presensi hari ini')
                ->action(function () {
                    $result = AbsensiService::syncDailyEmployeeActiveStatus();
                    Notification::make()
                        ->title('Sinkronisasi Status Berhasil')
                        ->body("Status berhasil diperbarui: {$result['activated']} personil aktif (hadir), {$result['deactivated']} nonaktif (belum/tidak presensi).")
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make()
                ->using(function(array $data): User
                {
                    DB::beginTransaction();
                    try
                    {
                        $cabangId = auth()->user()?->hasRole('manager_cabang') 
                            ? auth()->user()->cabang_id 
                            : ($data['cabang_id'] ?? null);

                        $selectedRole = (!empty($data['role']) && auth()->user()?->hasRole('super_admin')) ? $data['role'] : 'karyawan';
                        $tipeKaryawan = ($selectedRole === 'karyawan') ? ($data['tipe_karyawan'] ?? 'helpman') : null;

                        $karyawan = User::create([
                            'name' => $data['name'],
                            'username' => $data['username'],
                            'email' => !empty($data['email']) ? $data['email'] : null,
                            'password' => $data['password'],
                            'custom_fields' => [
                                'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
                            ],
                            'avatar_url' => $data['avatar_url'] ?? null,
                            'cabang_id' => $cabangId,
                            'is_visible' => $data['is_visible'] ?? true,
                            'tipe_karyawan' => $tipeKaryawan,
                        ]);
    
                        $karyawan->syncRoles([$selectedRole]);

                        if ($selectedRole === 'manager_cabang' && $cabangId) {
                            $oldBranchManager = User::where('id', '!=', $karyawan->id)
                                ->whereHas('managedCabang', fn ($q) => $q->where('id', $cabangId))
                                ->first();
                            if ($oldBranchManager && ! $oldBranchManager->hasRole('super_admin')) {
                                $oldBranchManager->syncRoles(['karyawan']);
                                if (empty($oldBranchManager->tipe_karyawan)) {
                                    $oldBranchManager->update(['tipe_karyawan' => 'helpman']);
                                }
                            }
                            Cabang::where('manager_id', $karyawan->id)->where('id', '!=', $cabangId)->update(['manager_id' => null]);
                            Cabang::where('id', $cabangId)->update(['manager_id' => $karyawan->id]);
                        }

                        DB::commit();

                        Notification::make()
                            ->title('Sukses!')
                            ->body('Tambah karyawan berhasil!')
                            ->success()
                            ->send();

                        return $karyawan;
                    } catch(Exception $e)
                    {
                        DB::rollBack();

                        Notification::make()
                            ->title('Gagal!')
                            ->body('Tambah karyawan gagal! ' . $e->getMessage())
                            ->danger()
                            ->send();

                        throw $e;
                    }
                }),
        ];
    }

    public function getTabs(): array
    {
        $user = auth()->user();
        if (! $user || ! $user->hasAnyRole(['super_admin', 'owner'])) {
            return [];
        }

        $baseFilter = function (Builder $query) {
            $query->whereHas('roles', fn ($q) => $q->where('name', 'karyawan'))
                  ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['super_admin', 'owner']))
                  ->where('email', '!=', 'admin@gmail.com')
                  ->where('email', '!=', 'admin@tohelp.com')
                  ->where('username', '!=', 'owner')
                  ->where('email', '!=', 'owner@tohelp.com');
        };

        $allKaryawanCount = User::where($baseFilter)->count();
        $helpmanCount = User::where($baseFilter)->where('tipe_karyawan', 'helpman')->count();
        $jokiCount = User::where($baseFilter)->where('tipe_karyawan', 'joki')->count();

        return [
            'semua' => Tab::make('Semua Karyawan')
                ->icon('heroicon-o-users')
                ->badge($allKaryawanCount)
                ->modifyQueryUsing($baseFilter),

            'helpman' => Tab::make('Helpman (Lapangan)')
                ->icon('heroicon-o-wrench-screwdriver')
                ->badge($helpmanCount)
                ->modifyQueryUsing(fn (Builder $query) => $query->tap($baseFilter)->where('tipe_karyawan', 'helpman')),

            'joki' => Tab::make('Joki (Tugas / Digital)')
                ->icon('heroicon-o-academic-cap')
                ->badge($jokiCount)
                ->modifyQueryUsing(fn (Builder $query) => $query->tap($baseFilter)->where('tipe_karyawan', 'joki')),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Data Karyawan & Personil';
    }
}
