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
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama Kota / Cabang')
                            ->placeholder('Contoh: Cabang Banyuwangi')
                            ->required()
                            ->unique(ignoreRecord: true)
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

                        Select::make('manager_id')
                            ->label('Manager Cabang')
                            ->placeholder('-- Tidak Ada Manager (None) --')
                            ->searchable()
                            ->preload()
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
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama Cabang / Kota')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
