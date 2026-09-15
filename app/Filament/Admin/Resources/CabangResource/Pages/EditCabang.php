<?php

namespace App\Filament\Admin\Resources\CabangResource\Pages;

use App\Filament\Admin\Resources\CabangResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCabang extends EditRecord
{
    protected static string $resource = CabangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
