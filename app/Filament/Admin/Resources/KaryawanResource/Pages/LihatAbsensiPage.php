<?php

namespace App\Filament\Admin\Resources\KaryawanResource\Pages;

use App\Filament\Admin\Resources\KaryawanResource;
use App\Models\Absensi;
use Carbon\Carbon;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class LihatAbsensiPage extends Page implements HasTable
{
    use InteractsWithTable, InteractsWithRecord;

    protected static string $resource = KaryawanResource::class;

    protected static string $view = 'filament.admin.resources.karyawan-resource.pages.lihat-absensi-page';

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Lihat Absensi ' . ucwords($this->record->name);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Absensi::with(['karyawan', 'media'])->where('karyawan_id', $this->record->id)->latest('tanggal'))
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('l, d F Y'),
                TextColumn::make('jam_masuk')
                    ->label('Waktu Stand By')
                    ->dateTime('H:i:s'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color('success')
                    ->getStateUsing(fn() => 'Stand By')
            ])
            ->actions([
                Action::make('lihatBuktiAbsensi')
                    ->button()
                    ->label('Lihat Bukti Absensi')
                    ->color('info')
                    ->icon('heroicon-o-photo')
                    ->modalHeading(fn (Absensi $record) => 'Foto Bukti Absensi - ' . ($record->karyawan?->name ?? 'Karyawan'))
                    ->infolist([
                        Grid::make()
                            ->columns(1)
                            ->schema([
                                ImageEntry::make('bukti_foto')
                                    ->label('Foto Bukti Kehadiran')
                                    ->state(function (Absensi $record) {
                                        return $record->getFirstMediaUrl('bukti-absensi') ?: null;
                                    })
                                    ->extraImgAttributes([
                                        'style' => 'max-height: 480px; width: auto; object-fit: contain; border-radius: 8px; margin: 0 auto; display: block;',
                                    ])
                                    ->columnSpanFull(),
                                TextEntry::make('info_waktu')
                                    ->label('Waktu Check-In')
                                    ->state(fn (Absensi $record) => Carbon::parse($record->tanggal)->format('d/m/Y') . ' - Pukul ' . Carbon::parse($record->jam_masuk)->format('H:i:s') . ' WIB')
                                    ->columnSpanFull(),
                            ])
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ]);
    }
}
