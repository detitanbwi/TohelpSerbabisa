<?php

namespace App\Filament\Admin\Resources\CabangResource\Pages;

use App\Filament\Admin\Resources\CabangResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCabang extends CreateRecord
{
    protected static string $resource = CabangResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
