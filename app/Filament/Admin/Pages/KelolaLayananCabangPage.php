<?php

namespace App\Filament\Admin\Pages;

use App\Models\Cabang;
use App\Models\CabangLayanan;
use App\Models\Layanan;
use App\Models\SubLayanan;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class KelolaLayananCabangPage extends Page implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Layanan & Tarif Cabang';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.admin.pages.kelola-layanan-cabang-page';

    public ?int $selectedCabangId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'manager_cabang']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'manager_cabang']) ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        $cabang = $this->selectedCabangId ? Cabang::find($this->selectedCabangId) : null;
        if ($cabang) {
            return 'Layanan & Tarif Cabang: ' . $cabang->nama;
        }
        return 'Pengaturan Layanan & Tarif Cabang';
    }

    public function getSelectedCabangNamaProperty(): string
    {
        $cabang = $this->selectedCabangId ? Cabang::find($this->selectedCabangId) : null;
        return $cabang ? $cabang->nama : 'Cabang Utama';
    }

    public function mount(): void
    {
        $user = auth()->user();
        $queryCabangId = request()->query('cabang');

        if ($queryCabangId && ($user->hasRole('super_admin') || $user->cabang_id == $queryCabangId || $user->managedCabang?->id == $queryCabangId)) {
            $this->selectedCabangId = (int) $queryCabangId;
        } elseif ($user && $user->hasRole('manager_cabang') && !$user->hasRole('super_admin')) {
            $this->selectedCabangId = $user->cabang_id ?: ($user->managedCabang?->id ?: Cabang::first()?->id);
        } else {
            $this->selectedCabangId = Cabang::first()?->id;
        }
    }

    public function updatedSelectedCabangId(): void
    {
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SubLayanan::query()
                    ->with(['layanan', 'cabangLayanans'])
                    ->where('is_active', true)
                    ->whereHas('layanan', fn ($q) => $q->where('is_active', true))
                    ->orderBy('layanan_id')
                    ->orderBy('urutan')
            )
            ->defaultGroup('layanan.nama')
            ->columns([
                TextColumn::make('nama')
                    ->label('Sub-Layanan / Paket')
                    ->weight('bold')
                    ->description(fn (SubLayanan $record) => $record->deskripsi)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('default_harga')
                    ->label('Tarif Default Superadmin')
                    ->getStateUsing(fn (SubLayanan $record) => $record->getFormattedDisplayPrice())
                    ->badge()
                    ->color('gray'),

                IconColumn::make('is_tersedia')
                    ->label('Ketersediaan')
                    ->boolean()
                    ->getStateUsing(fn (SubLayanan $record) => $record->isTersediaForCabang($this->selectedCabangId)),

                TextColumn::make('tarif_efektif')
                    ->label('Tarif Berlaku di Cabang Ini')
                    ->getStateUsing(function (SubLayanan $record) {
                        $pivot = $record->cabangLayanans()->where('cabang_id', $this->selectedCabangId)->first();
                        $isCustom = $pivot && ($pivot->custom_harga !== null || !empty($pivot->custom_satuan) || !empty($pivot->custom_label));
                        $isTersedia = $record->isTersediaForCabang($this->selectedCabangId);

                        if (!$isTersedia) {
                            return '❌ Dinonaktifkan di Cabang Ini';
                        }

                        $formatted = $record->getFormattedDisplayPrice($this->selectedCabangId);
                        return $isCustom ? "{$formatted} (Tarif Khusus)" : "{$formatted} (Default)";
                    })
                    ->badge()
                    ->color(function (string $state) {
                        if (str_contains($state, 'Dinonaktifkan')) {
                            return 'danger';
                        }
                        if (str_contains($state, 'Tarif Khusus')) {
                            return 'warning';
                        }
                        return 'success';
                    }),

                TextColumn::make('catatan_cabang')
                    ->label('Catatan/NB Cabang')
                    ->getStateUsing(function (SubLayanan $record) {
                        $pivot = $record->cabangLayanans()->where('cabang_id', $this->selectedCabangId)->first();
                        return $pivot?->custom_catatan_nb ?: ($record->catatan_nb ?: '-');
                    })
                    ->limit(35)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('layanan')
                    ->relationship('layanan', 'nama')
                    ->label('Kategori Layanan')
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('editTarif')
                    ->label('Atur Tarif & Status')
                    ->icon('heroicon-o-pencil-square')
                    ->button()
                    ->color('primary')
                    ->modalHeading(fn (SubLayanan $record) => "Pengaturan Cabang: {$record->nama} ({$this->selectedCabangNama})")
                    ->fillForm(function (SubLayanan $record): array {
                        $pivot = CabangLayanan::where('cabang_id', $this->selectedCabangId)
                            ->where('sub_layanan_id', $record->id)
                            ->first();

                        return [
                            'is_tersedia' => $pivot ? (bool) $pivot->is_tersedia : true,
                            'custom_harga' => $pivot?->custom_harga,
                            'custom_satuan' => $pivot?->custom_satuan,
                            'custom_label' => $pivot?->custom_label,
                            'custom_catatan_nb' => $pivot?->custom_catatan_nb,
                        ];
                    })
                    ->form([
                        Toggle::make('is_tersedia')
                            ->label('Tersedia di Cabang Ini')
                            ->helperText('Jika dinonaktifkan, paket/layanan ini tidak akan muncul di website saat pengunjung memilih cabang ini.')
                            ->default(true),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('custom_harga')
                                    ->label('Harga Khusus Cabang (Rp)')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->placeholder(fn (SubLayanan $record) => 'Default: Rp ' . number_format($record->default_harga, 0, ',', '.'))
                                    ->helperText('Kosongkan jika ingin menggunakan harga default Superadmin.'),

                                TextInput::make('custom_satuan')
                                    ->label('Satuan Khusus')
                                    ->placeholder(fn (SubLayanan $record) => 'Default: ' . ($record->default_satuan ?: '-'))
                                    ->helperText('Contoh: / jam, / paket. Kosongkan untuk pakai default.'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('custom_label')
                                    ->label('Label Awalan Khusus')
                                    ->placeholder(fn (SubLayanan $record) => 'Default: ' . ($record->label_harga_custom ?: 'Kosong'))
                                    ->helperText('Contoh: Start from, Mulai dari.'),

                                Textarea::make('custom_catatan_nb')
                                    ->label('Catatan Khusus Cabang')
                                    ->rows(2)
                                    ->placeholder('Catatan khusus untuk cabang ini...'),
                            ]),
                    ])
                    ->action(function (array $data, SubLayanan $record): void {
                        CabangLayanan::updateOrCreate(
                            [
                                'cabang_id' => $this->selectedCabangId,
                                'sub_layanan_id' => $record->id,
                            ],
                            [
                                'is_tersedia' => (bool) $data['is_tersedia'],
                                'custom_harga' => ($data['custom_harga'] !== '' && $data['custom_harga'] !== null) ? (float) $data['custom_harga'] : null,
                                'custom_satuan' => !empty($data['custom_satuan']) ? trim($data['custom_satuan']) : null,
                                'custom_label' => !empty($data['custom_label']) ? trim($data['custom_label']) : null,
                                'custom_catatan_nb' => !empty($data['custom_catatan_nb']) ? trim($data['custom_catatan_nb']) : null,
                            ]
                        );

                        Notification::make()
                            ->title('Berhasil Disimpan')
                            ->body("Pengaturan untuk {$record->nama} di Cabang {$this->selectedCabangNama} berhasil diperbarui.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('resetDefault')
                    ->label('Reset')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Reset ke Tarif Default?')
                    ->modalDescription('Harga dan satuan khusus cabang ini akan dihapus dan kembali mengikuti nilai default dari Superadmin.')
                    ->action(function (SubLayanan $record): void {
                        CabangLayanan::where('cabang_id', $this->selectedCabangId)
                            ->where('sub_layanan_id', $record->id)
                            ->update([
                                'custom_harga' => null,
                                'custom_satuan' => null,
                                'custom_label' => null,
                                'custom_catatan_nb' => null,
                            ]);

                        Notification::make()
                            ->title('Direset')
                            ->body("Tarif {$record->nama} dikembalikan ke default Superadmin.")
                            ->info()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('aktifkanSemua')
                    ->label('Aktifkan yang Dipilih')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Collection $records): void {
                        foreach ($records as $record) {
                            CabangLayanan::updateOrCreate(
                                [
                                    'cabang_id' => $this->selectedCabangId,
                                    'sub_layanan_id' => $record->id,
                                ],
                                ['is_tersedia' => true]
                            );
                        }

                        Notification::make()
                            ->title('Berhasil Diaktifkan')
                            ->body('Layanan yang dipilih telah diaktifkan untuk cabang ini.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\BulkAction::make('nonaktifkanSemua')
                    ->label('Nonaktifkan yang Dipilih')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(function (Collection $records): void {
                        foreach ($records as $record) {
                            CabangLayanan::updateOrCreate(
                                [
                                    'cabang_id' => $this->selectedCabangId,
                                    'sub_layanan_id' => $record->id,
                                ],
                                ['is_tersedia' => false]
                            );
                        }

                        Notification::make()
                            ->title('Berhasil Dinonaktifkan')
                            ->body('Layanan yang dipilih telah dinonaktifkan untuk cabang ini.')
                            ->danger()
                            ->send();
                    }),
            ]);
    }

    public function getCabangOptionsProperty(): array
    {
        $user = auth()->user();
        if ($user && $user->hasRole('manager_cabang') && !$user->hasRole('super_admin')) {
            $cabangId = $user->cabang_id ?: $user->managedCabang?->id;
            return Cabang::where('id', $cabangId)->pluck('nama', 'id')->toArray();
        }

        return Cabang::orderBy('nama')->pluck('nama', 'id')->toArray();
    }
}
