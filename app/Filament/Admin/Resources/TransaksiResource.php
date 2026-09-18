<?php

namespace App\Filament\Admin\Resources;

use Exception;
use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Cabang;
use Filament\Forms\Form;
use App\Models\Transaksi;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Admin\Resources\TransaksiResource\Pages;
use CodeWithDennis\SimpleMap\Components\Tables\SimpleMap;
use App\Filament\Admin\Resources\TransaksiResource\RelationManagers;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class TransaksiResource extends Resource
{
    protected static ?string $model = Transaksi::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Transaksi';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $model): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(Transaksi::query()->with('voucher')->orderBy('created_at', 'desc'))
            ->columns([
                Tables\Columns\TextColumn::make('order_id')
                    ->label('ID Order')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('voucher.nama')
                    ->label('Voucher')
                    ->getStateUsing(fn(Transaksi $transaksi) => $transaksi->voucher->nama ?? 'Tidak Ada Voucher')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jenis')
                    ->label('Jenis Layanan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jasa')
                    ->label('Jasa')
                    ->getStateUsing(fn(Transaksi $transaksi) => $transaksi->jasa ?? '-'),
                Tables\Columns\TextColumn::make('total_harga')
                    ->label('Total Harga')
                    ->weight(FontWeight::Bold)
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tip')
                    ->label('Tip Driver')
                    ->money('IDR')
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cabang.nama')
                    ->label('Cabang')
                    ->getStateUsing(fn(Transaksi $transaksi) => $transaksi->cabang->nama ?? '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_transaksi')
                    ->label('Status Transaksi')
                    ->badge()
                    ->color(fn(Transaksi $transaksi) => match ($transaksi->status_transaksi) {
                        'belum' => 'warning',
                        'sukses' => 'success',
                        'batal' => 'danger',
                        default => 'secondary',
                    })
                    ->getStateUsing(function (Transaksi $transaksi) {
                        return match ($transaksi->status_transaksi) {
                            'belum' => 'Belum Selesai',
                            'sukses' => 'Sukses Bayar',
                            'batal' => 'Dibatalkan',
                            default => $transaksi->status_transaksi ?? '-',
                        };
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Order')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('cabang_id')
                    ->options(Cabang::all()->pluck('nama', 'id'))
                    ->label('Cabang'),
                Tables\Filters\SelectFilter::make('status_transaksi')
                    ->options([
                        'belum' => 'Belum Selesai',
                        'sukses' => 'Sukses Bayar',
                        'batal' => 'Dibatalkan',
                    ]),
                Tables\Filters\SelectFilter::make('jenis')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->options(Transaksi::query()->distinct('jenis')->pluck('jenis', 'jenis')->mapWithKeys(fn($item) => [$item => ucwords($item)])),
                DateRangeFilter::make('created_at')->timezone('Asia/Jakarta')
                    ->label('Tanggal')
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    SimpleMap::make('showMap')
                        ->icon('heroicon-o-map')
                        ->label('Lihat Peta')
                        ->color('info')
                        ->viewing()
                        ->directions()
                        ->origin(fn(Transaksi $transaksi) => $transaksi->titik_jemput)
                        ->destination(fn(Transaksi $transaksi) => $transaksi->titik_tujuan)
                        ->zoom(13)
                        ->language('id')
                        ->region('id')
                        ->visible(fn(Transaksi $transaksi) => ($transaksi->titik_jemput && $transaksi->titik_tujuan) && $transaksi->status_transaksi !== 'batal'),
                    Tables\Actions\Action::make('ubahHarga')
                        ->label('Ubah Harga')
                        ->modalHeading('Ubah Harga & Tip Transaksi')
                        ->modalDescription('Sesuaikan total harga atau nominal tip transaksi.')
                        ->modalSubmitActionLabel('Simpan Perubahan')
                        ->modalWidth('md')
                        ->color('success')
                        ->icon('heroicon-o-currency-dollar')
                        ->fillForm(fn (Transaksi $record): array => [
                            'total_harga' => $record->total_harga,
                            'tip' => $record->tip ?? 0,
                        ])
                        ->form([
                            Forms\Components\TextInput::make('total_harga')
                                ->label('Total Harga')
                                ->prefix('Rp')
                                ->numeric()
                                ->required()
                                ->minValue(0),
                            Forms\Components\TextInput::make('tip')
                                ->label('Nominal Tip Driver/Petugas')
                                ->prefix('Rp')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),
                        ])
                        ->action(function (Transaksi $transaksi, array $data) {
                            $totalHarga = (int) ($data['total_harga'] ?? 0);
                            $tip = (int) ($data['tip'] ?? 0);

                            $transaksi->update([
                                'total_harga' => $totalHarga,
                                'tip' => $tip,
                            ]);

                            Notification::make()
                                ->title('Sukses')
                                ->body('Harga dan tip transaksi berhasil diperbarui.')
                                ->success()
                                ->send();
                        })
                        ->hidden(fn(Transaksi $transaksi) => $transaksi->status_transaksi === 'batal'),
                    Tables\Actions\DeleteAction::make()
                        ->label('Batalkan Transaksi')
                        ->action(function (Transaksi $transaksi) {
                            $transaksi->update([
                                'status_transaksi' => 'batal',
                            ]);

                            Notification::make()
                                ->title('Sukses')
                                ->body('Transaksi dibatalkan')
                                ->success()
                                ->send();
                        })
                        ->hidden(fn(Transaksi $transaksi) => $transaksi->status_transaksi === 'batal'),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTransaksis::route('/'),
        ];
    }
}
