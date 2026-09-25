<?php

namespace App\Filament\Admin\Widgets;

use Exception;
use App\Models\User;
use Filament\Tables;
use App\Models\Cabang;
use App\Models\Transaksi;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Widgets\TableWidget as BaseWidget;
use CodeWithDennis\SimpleMap\Components\Tables\SimpleMap;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class TransaksiWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->hasAnyRole(['super_admin', 'manager_cabang']) || ($user?->can('view_any_transaksi') ?? false);
    }

    public function table(Table $table): Table
    {
        $query = Transaksi::query()->with(['voucher', 'cabang', 'tugas'])->orderBy('created_at', 'desc');

        $user = auth()->user();
        if ($user && $user->hasRole('manager_cabang') && ! $user->hasRole('super_admin')) {
            $query->whereIn('cabang_id', $user->getCabangIds());
        }

        return $table
            ->query($query)
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25])
            ->columns([
                Tables\Columns\TextColumn::make('order_id')
                    ->label('ID Order')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('voucher.nama')
                    ->label('Voucher')
                    ->getStateUsing(fn(Transaksi $transaksi) => $transaksi->voucher->nama ?? 'Tidak Ada Voucher')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jenis')
                    ->label('Jenis Layanan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jasa')
                    ->label('Jasa')
                    ->getStateUsing(fn(Transaksi $transaksi) => $transaksi->jasa ?? '-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_harga')
                    ->label('Total Harga')
                    ->weight(FontWeight::Bold)
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cabang.nama')
                    ->label('Cabang')
                    ->getStateUsing(fn(Transaksi $transaksi) => $transaksi->cabang->nama ?? '-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tugas.name')
                    ->label('Helpman')
                    ->badge()
                    ->searchable()
                    ->placeholder('Belum Ditugaskan')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status_transaksi')
                    ->label('Status Transaksi')
                    ->badge()
                    ->color(fn (Transaksi $transaksi) => match ($transaksi->status_transaksi) {
                        'belum' => 'warning',
                        'sukses' => 'success',
                        'batal' => 'danger',
                        default => 'secondary',
                    })
                    ->getStateUsing(function(Transaksi $transaksi) {
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
                    ->label('Cabang')
                    ->options(Cabang::all()->pluck('nama', 'id'))
                    ->visible(fn () => auth()->user()?->hasRole('super_admin')),
                Tables\Filters\SelectFilter::make('status_transaksi')
                    ->options([
                        'belum' => 'Belum Selesai',
                        'sukses' => 'Sukses Bayar',
                        'batal' => 'Dibatalkan',
                    ]),
                DateRangeFilter::make('created_at')->timezone('Asia/Jakarta')
                    ->label('Tanggal'),
                ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(['sm' => 1, 'md' => 2, 'lg' => 3])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    SimpleMap::make('showMap')
                        ->icon('heroicon-o-map')
                        ->label('Lihat Peta')
                        ->color('info')
                        ->viewing()
                        ->directions()
                        ->origin(fn (Transaksi $transaksi) => $transaksi->titik_jemput)
                        ->destination(fn (Transaksi $transaksi) => $transaksi->titik_tujuan)
                        ->zoom(13)
                        ->language('id')
                        ->region('id')
                        ->visible(fn (Transaksi $transaksi) => ($transaksi->titik_jemput && $transaksi->titik_tujuan) && $transaksi->status_transaksi !== 'batal'),
                    Tables\Actions\DeleteAction::make()
                        ->label('Batalkan Transaksi')
                        ->action(function(Transaksi $transaksi)
                        {
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
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }
}
