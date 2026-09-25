<?php

namespace App\Filament\Karyawan\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Absensi;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Karyawan\Resources\AbsensiResource\Pages;
use App\Filament\Karyawan\Resources\AbsensiResource\RelationManagers;

class AbsensiResource extends Resource
{
    protected static ?string $model = Absensi::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Riwayat Kehadiran';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('karyawan_id', auth()->id())
            ->latest('tanggal');
    }

    public static function form(Form $form): Form
    {
        return $form([
            // 
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('karyawan_id', auth()->id())->latest('tanggal'))
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('l, d F Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jam_masuk')
                    ->label('Jam Masuk')
                    ->dateTime('H:i:s'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color('success')
                    ->getStateUsing(fn () => 'Stand By (Hadir)'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Dicatat')
                    ->dateTime('d M Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // 
            ])
            ->actions([
                Tables\Actions\Action::make('lihatBukti')
                    ->label('Bukti Foto')
                    ->icon('heroicon-o-photo')
                    ->color('info')
                    ->modalHeading('Foto Bukti Presensi')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->infolist([
                        \Filament\Infolists\Components\ImageEntry::make('bukti_foto')
                            ->label('Foto Bukti Kehadiran')
                            ->state(fn (Absensi $record) => $record->getFirstMediaUrl('bukti-absensi') ?: null)
                            ->extraImgAttributes([
                                'style' => 'max-height: 480px; width: auto; object-fit: contain; border-radius: 8px; margin: 0 auto; display: block;',
                            ]),
                    ])
                    ->visible(fn (Absensi $record) => $record->hasMedia('bukti-absensi')),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAbsensis::route('/'),
        ];
    }
}
