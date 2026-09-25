<?php

namespace App\Filament\Admin\Resources\RoleResource\Pages;

use App\Filament\Admin\Resources\RoleResource;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    public Collection $permissions;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action, Model $record) {
                    $protectedRoles = ['super_admin', 'karyawan', 'panel_user'];
                    if (in_array(strtolower($record->name), $protectedRoles)) {
                        Notification::make()
                            ->title('Tidak Dapat Dihapus')
                            ->body("Role '{$record->name}' adalah role sistem utama dan tidak dapat dihapus.")
                            ->danger()
                            ->send();

                        $action->halt();
                    }

                    if (method_exists($record, 'users') && $record->users()->count() > 0) {
                        $count = $record->users()->count();
                        Notification::make()
                            ->title('Role Sedang Digunakan')
                            ->body("Role '{$record->name}' sedang digunakan oleh {$count} pengguna. Harap pindahkan pengguna ke role lain terlebih dahulu.")
                            ->warning()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->permissions = collect($data)
            ->filter(function ($permission, $key) {
                return ! in_array($key, ['name', 'guard_name', 'select_all', Utils::getTenantModelForeignKey()]);
            })
            ->values()
            ->flatten()
            ->unique();

        if (Arr::has($data, Utils::getTenantModelForeignKey())) {
            return Arr::only($data, ['name', 'guard_name', Utils::getTenantModelForeignKey()]);
        }

        return Arr::only($data, ['name', 'guard_name']);
    }

    protected function afterSave(): void
    {
        $permissionModels = collect();
        $this->permissions->each(function ($permission) use ($permissionModels) {
            $permissionModels->push(Utils::getPermissionModel()::firstOrCreate([
                'name' => $permission,
                'guard_name' => $this->data['guard_name'],
            ]));
        });

        $this->record->syncPermissions($permissionModels);
    }
}
