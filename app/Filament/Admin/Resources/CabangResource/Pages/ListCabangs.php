<?php

namespace App\Filament\Admin\Resources\CabangResource\Pages;

use App\Filament\Admin\Resources\CabangResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCabangs extends ListRecords
{
    protected static string $resource = CabangResource::class;

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Manajemen Cabang';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Cabang'),
        ];
    }
}
