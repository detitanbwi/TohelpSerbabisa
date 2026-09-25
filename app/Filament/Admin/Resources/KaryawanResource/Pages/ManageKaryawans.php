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
use Filament\Notifications\Notification;

class ManageKaryawans extends ManageRecords
{
    protected static string $resource = KaryawanResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
        if (! $user || ! $user->hasRole('super_admin')) {
            return [];
        }

        return [
            'personil' => Tab::make('Personil Operasional')
                ->icon('heroicon-o-users')
                ->badge(User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'))->where('email', '!=', 'admin@gmail.com')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'))->where('email', '!=', 'admin@gmail.com')),

            'manager' => Tab::make('Manager Cabang')
                ->icon('heroicon-o-briefcase')
                ->badge(User::whereHas('roles', fn ($q) => $q->where('name', 'manager_cabang'))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('roles', fn ($q) => $q->where('name', 'manager_cabang'))),

            'karyawan' => Tab::make('Helpman & Joki')
                ->icon('heroicon-o-wrench-screwdriver')
                ->badge(User::whereHas('roles', fn ($q) => $q->where('name', 'karyawan'))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('roles', fn ($q) => $q->where('name', 'karyawan'))),

            'super_admin' => Tab::make('Super Admin & Owner')
                ->icon('heroicon-o-shield-check')
                ->badge(User::where(fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', 'super_admin'))->orWhere('email', 'admin@gmail.com'))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where(fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', 'super_admin'))->orWhere('email', 'admin@gmail.com'))),

            'semua' => Tab::make('Semua Pengguna')
                ->icon('heroicon-o-user-group')
                ->badge(User::count())
                ->modifyQueryUsing(fn (Builder $query) => $query),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Karyawan & Personil';
    }
}
