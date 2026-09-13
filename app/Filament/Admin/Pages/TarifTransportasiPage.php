<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class TarifTransportasiPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Tarif Transportasi';

    protected static string $view = 'filament.admin.pages.tarif-transportasi-page';

    public function getTitle(): string|Htmlable
    {
        return __('Tarif Transportasi');
    }
}
