<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CabangResource\Pages;
use App\Models\Cabang;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class CabangResource extends Resource
{
    protected static ?string $model = Cabang::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Manajemen Cabang';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'manager_cabang']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'manager_cabang']) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->hasRole('manager_cabang') && ! $user->hasRole('super_admin')) {
            $query->where(function ($q) use ($user) {
                if ($user->cabang_id) {
                    $q->where('id', $user->cabang_id);
                }
                $q->orWhere('manager_id', $user->id);
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $isSuperAdmin = auth()->user()?->hasRole('super_admin') ?? false;

        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama Kota / Cabang')
                            ->placeholder('Contoh: Cabang Banyuwangi')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->disabled(! $isSuperAdmin)
                            ->columnSpanFull(),

                        TextInput::make('no_wa')
                            ->label('Nomor WhatsApp Operasional Cabang')
                            ->placeholder('Contoh: 085695908981 atau 6285695908981')
                            ->tel()
                            ->prefixIcon('heroicon-o-phone')
                            ->minLength(10)
                            ->maxLength(16)
                            ->rules(['regex:/^[0-9+]+$/'])
                            ->validationMessages([
                                'regex' => 'Nomor WhatsApp hanya boleh berisi angka.',
                                'min' => 'Nomor WhatsApp minimal 10 digit.',
                                'max' => 'Nomor WhatsApp maksimal 16 digit.',
                            ])
                            ->helperText('Format: 08xxxxxxxxxx atau 628xxxxxxxxxx (10–15 digit angka).')
                            ->required()
                            ->default('6285695908981')
                            ->columnSpanFull(),

                        TextInput::make('lat')
                            ->label('Latitude Basecamp')
                            ->placeholder('-8.2192')
                            ->numeric()
                            ->required(),

                        TextInput::make('lng')
                            ->label('Longitude Basecamp')
                            ->placeholder('114.3692')
                            ->numeric()
                            ->required(),

                        TextInput::make('free_distance_km')
                            ->label('Kuota Free Jemput (KM)')
                            ->placeholder('3.00')
                            ->numeric()
                            ->default(3.00)
                            ->suffix('KM')
                            ->helperText('Batas jarak penjemputan dari basecamp yang bebas biaya dasar tambahan.')
                            ->required(),

                        Select::make('manager_id')
                            ->label('Manager Cabang')
                            ->placeholder('-- Tidak Ada Manager (None) --')
                            ->searchable()
                            ->preload()
                            ->visible($isSuperAdmin)
                            ->options(function (?Cabang $record) {
                                $users = User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'))
                                    ->whereNot('email', 'admin@gmail.com')
                                    ->whereNot('name', 'Admin')
                                    ->with(['managedCabang', 'cabang'])
                                    ->orderBy('name')
                                    ->get();

                                $options = [];
                                foreach ($users as $u) {
                                    $identifier = $u->username ? "@{$u->username}" : $u->email;
                                    if ($record && $record->manager_id === $u->id) {
                                        $status = "👑 [Manager Cabang Ini]";
                                    } elseif ($u->managedCabang && (! $record || $u->managedCabang->id !== $record->id)) {
                                        $status = "⚠️ [Manager Cabang {$u->managedCabang->nama}]";
                                    } elseif ($u->cabang) {
                                        $status = "👤 [Helpman Cabang {$u->cabang->nama}]";
                                    } else {
                                        $status = "👤 [Helpman]";
                                    }
                                    $options[$u->id] = "{$u->name} ({$identifier}) - {$status}";
                                }
                                return $options;
                            })
                            ->helperText(function ($get, ?Cabang $record) {
                                $selectedUserId = $get('manager_id');
                                if (! $selectedUserId) {
                                    return 'Pilih user untuk dijadikan Manager Cabang ini, atau kosongkan (None) jika belum ada manager.';
                                }
                                $selectedUser = User::with('managedCabang')->find($selectedUserId);
                                if ($selectedUser && $selectedUser->managedCabang && (! $record || $selectedUser->managedCabang->id !== $record->id)) {
                                    return new HtmlString(
                                        "<span class='text-amber-500 dark:text-amber-400 font-semibold'>⚠️ Perhatian: {$selectedUser->name} saat ini menjabat sebagai Manager di Cabang {$selectedUser->managedCabang->nama}. Menyimpan form ini akan otomatis mencopot jabatannya di Cabang {$selectedUser->managedCabang->nama}, memindahkannya menjadi Manager di cabang ini, dan memperbarui basecamp kerjanya.</span>"
                                    );
                                }
                                return 'User yang dipilih akan otomatis mendapatkan hak akses Manager Cabang dan lokasi basecamp kerjanya disinkronkan ke cabang ini.';
                            })
                            ->live()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $isSuperAdmin = auth()->user()?->hasRole('super_admin') ?? false;

        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama Cabang / Kota')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('no_wa')
                    ->label('No. WhatsApp')
                    ->icon('heroicon-o-phone')
                    ->copyable()
                    ->searchable(),

                TextColumn::make('manager.name')
                    ->label('Manager Cabang')
                    ->getStateUsing(function (Cabang $record) {
                        if ($record->manager) {
                            $user = $record->manager;
                            $ident = $user->username ? "@{$user->username}" : $user->email;
                            return "{$user->name} ({$ident})";
                        }
                        return 'Belum Ada Manager (None)';
                    })
                    ->badge()
                    ->color(fn (Cabang $record) => $record->manager_id ? 'warning' : 'gray')
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('manager', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                              ->orWhere('username', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('lat')
                    ->label('Latitude')
                    ->sortable(),

                TextColumn::make('lng')
                    ->label('Longitude')
                    ->sortable(),

                TextColumn::make('free_distance_km')
                    ->label('Free Jemput')
                    ->formatStateUsing(fn ($state) => number_format($state ?? 3.0, 1) . ' KM')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Total Personil')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('aturLayanan')
                    ->label('Layanan & Tarif')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('info')
                    ->url(fn (Cabang $record) => \App\Filament\Admin\Pages\KelolaLayananCabangPage::getUrl(['cabang' => $record->id])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => $isSuperAdmin),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => $isSuperAdmin),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCabangs::route('/'),
            'create' => Pages\CreateCabang::route('/create'),
            'edit' => Pages\EditCabang::route('/{record}/edit'),
        ];
    }
}
