<?php

namespace App\Filament\Admin\Resources\RoleResource\Pages;

use App\Filament\Admin\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Peran & Hak Akses';
    }

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Peran Baru'),
        ];
    }
}
