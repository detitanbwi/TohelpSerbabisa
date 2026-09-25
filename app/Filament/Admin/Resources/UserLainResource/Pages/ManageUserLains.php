<?php

namespace App\Filament\Admin\Resources\UserLainResource\Pages;

use App\Filament\Admin\Resources\UserLainResource;
use App\Models\Cabang;
use App\Models\User;
use Exception;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ManageUserLains extends ManageRecords
{
    protected static string $resource = UserLainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah User Lain')
                ->icon('heroicon-o-plus')
                ->modalHeading('Tambah Akun User Baru (Non-Karyawan)')
                ->using(function (array $data): User {
                    DB::beginTransaction();
                    try {
                        $selectedRole = $data['role'] ?? 'super_admin';
                        $cabangId = ($selectedRole === 'manager_cabang') ? ($data['cabang_id'] ?? null) : null;

                        $user = User::create([
                            'name' => $data['name'],
                            'username' => $data['username'],
                            'email' => !empty($data['email']) ? $data['email'] : null,
                            'password' => $data['password'],
                            'cabang_id' => $cabangId,
                            'tipe_karyawan' => null,
                            'is_visible' => $data['is_visible'] ?? true,
                            'avatar_url' => $data['avatar_url'] ?? null,
                            'custom_fields' => [
                                'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
                            ],
                        ]);

                        $user->syncRoles([$selectedRole]);

                        if ($selectedRole === 'manager_cabang' && $cabangId) {
                            $oldBranchManager = User::where('id', '!=', $user->id)
                                ->whereHas('managedCabang', fn ($q) => $q->where('id', $cabangId))
                                ->first();
                            if ($oldBranchManager && ! $oldBranchManager->hasRole('super_admin')) {
                                $oldBranchManager->syncRoles(['karyawan']);
                                if (empty($oldBranchManager->tipe_karyawan)) {
                                    $oldBranchManager->update(['tipe_karyawan' => 'helpman']);
                                }
                            }
                            Cabang::where('manager_id', $user->id)->where('id', '!=', $cabangId)->update(['manager_id' => null]);
                            Cabang::where('id', $cabangId)->update(['manager_id' => $user->id]);
                        }

                        DB::commit();

                        Notification::make()
                            ->title('Sukses!')
                            ->body('Akun user berhasil ditambahkan.')
                            ->success()
                            ->send();

                        return $user;
                    } catch (Exception $e) {
                        DB::rollBack();

                        Notification::make()
                            ->title('Gagal!')
                            ->body('Tambah user gagal: ' . $e->getMessage())
                            ->danger()
                            ->send();

                        throw $e;
                    }
                }),
        ];
    }

    public function getTabs(): array
    {
        $superAdminCount = User::where(function ($q) {
            $q->whereHas('roles', fn ($r) => $r->where('name', 'super_admin'))
              ->orWhere('email', 'admin@gmail.com');
        })->count();

        $managerCount = User::whereHas('roles', fn ($q) => $q->where('name', 'manager_cabang'))->count();

        $allCount = User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'karyawan'))->count();

        $tabs = [
            'semua' => Tab::make('Semua User Lain')
                ->icon('heroicon-o-user-group')
                ->badge($allCount)
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDoesntHave('roles', fn ($q) => $q->where('name', 'karyawan'))),

            'super_admin' => Tab::make('Super Admin & Owner')
                ->icon('heroicon-o-shield-check')
                ->badge($superAdminCount)
                ->modifyQueryUsing(fn (Builder $query) => $query->where(fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', 'super_admin'))->orWhere('email', 'admin@gmail.com'))),

            'manager' => Tab::make('Manager Cabang')
                ->icon('heroicon-o-briefcase')
                ->badge($managerCount)
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('roles', fn ($q) => $q->where('name', 'manager_cabang'))),
        ];

        $otherCount = User::whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['karyawan', 'super_admin', 'manager_cabang']))->count();
        if ($otherCount > 0) {
            $tabs['lainnya'] = Tab::make('Lainnya')
                ->icon('heroicon-o-ellipsis-horizontal-circle')
                ->badge($otherCount)
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['karyawan', 'super_admin', 'manager_cabang'])));
        }

        return $tabs;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Tampilkan User Lain (Non-Karyawan)';
    }
}
