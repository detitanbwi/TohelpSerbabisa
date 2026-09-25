<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserLainResource\Pages;
use App\Models\Cabang;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserLainResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'user-lain';

    protected static ?string $modelLabel = 'User Lain';

    protected static ?string $pluralModelLabel = 'User Lain';

    protected static ?string $navigationLabel = 'Tampilkan User Lain';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        if (! auth()->user()?->hasRole('super_admin')) {
            return false;
        }

        if ($record->id === auth()->id() || $record->email === 'admin@gmail.com') {
            return false;
        }

        return true;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['media', 'roles', 'cabang'])
            ->whereDoesntHave('roles', fn (Builder $q) => $q->where('name', 'karyawan'));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->columns(1)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->placeholder('Masukkan nama lengkap user')
                            ->required()
                            ->autocomplete(false),
                        TextInput::make('username')
                            ->label('Username Unik')
                            ->placeholder('Contoh: admin01 atau manager_bwi')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Username ini sudah digunakan.',
                                'required' => 'Username wajib diisi.',
                            ])
                            ->autocomplete(false),
                        TextInput::make('email')
                            ->label('Email')
                            ->placeholder('Contoh: admin@tohelp.com')
                            ->required()
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Email ini sudah digunakan.',
                                'required' => 'Email wajib diisi.',
                            ])
                            ->autocomplete(false),
                        Select::make('role')
                            ->label('Role / Hak Akses')
                            ->options([
                                'super_admin' => '👑 Super Admin',
                                'manager_cabang' => '🏢 Manager Cabang',
                            ])
                            ->default('super_admin')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state !== 'manager_cabang') {
                                    $set('cabang_id', null);
                                }
                            })
                            ->helperText('Pilih "Super Admin" untuk hak akses penuh sistem, atau "Manager Cabang" untuk pengelola cabang.'),
                        Select::make('cabang_id')
                            ->label('Cabang yang Dikelola')
                            ->options(Cabang::all()->pluck('nama', 'id'))
                            ->placeholder('Pilih Cabang Penempatan')
                            ->selectablePlaceholder(false)
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('role') === 'manager_cabang')
                            ->required(fn (Forms\Get $get) => $get('role') === 'manager_cabang')
                            ->helperText('Pilih cabang penempatan untuk Manager Cabang ini.'),
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->placeholder('Masukkan password login')
                            ->helperText(fn (string $operation): string => $operation === 'create' 
                                ? 'Klik ikon mata untuk melihat password yang diketik.' 
                                : 'Kosongkan jika tidak ingin mengubah password.')
                            ->autocomplete('new-password')
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create'),
                        DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir (Opsional)')
                            ->nullable()
                            ->locale('id'),
                        Toggle::make('is_visible')
                            ->label('Status Akun Aktif')
                            ->default(true),
                        FileUpload::make('avatar_url')
                            ->label('Foto Profil')
                            ->image()
                            ->directory('avatars')
                            ->disk('public')
                            ->avatar()
                            ->maxFiles(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['media', 'roles', 'cabang'])
                      ->whereDoesntHave('roles', fn (Builder $q) => $q->where('name', 'karyawan'));
            })
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('Foto')
                    ->circular()
                    ->disk('public')
                    ->defaultImageUrl(fn (User $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=FFFFFF&background=0284c7'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama User')
                    ->weight('font-semibold')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->badge()
                    ->color('warning')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role / Hak Akses')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'manager_cabang' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'super_admin' => '👑 Super Admin',
                        'manager_cabang' => '🏢 Manager Cabang',
                        default => $state ? ucfirst(str_replace('_', ' ', $state)) : 'Customer / Lainnya',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('cabang.nama')
                    ->label('Cabang Penempatan')
                    ->badge()
                    ->color('primary')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                ToggleColumn::make('is_visible')
                    ->label('Status Aktif')
                    ->sortable()
                    ->afterStateUpdated(function (User $record, $state) {
                        $statusText = $state ? 'Aktif' : 'Nonaktif';
                        Notification::make()
                            ->title('Status User Diperbarui')
                            ->body("Status {$record->name} berhasil diubah menjadi {$statusText}.")
                            ->success()
                            ->send();
                    }),
                Tables\Columns\TextColumn::make('tanggal_lahir')
                    ->label('Tanggal Lahir')
                    ->getStateUsing(function (User $user) {
                        $tanggal_lahir = $user?->custom_fields['tanggal_lahir'] ?? null;
                        if (! $tanggal_lahir) {
                            return '-';
                        }
                        try {
                            return Carbon::parse($tanggal_lahir)->locale('id')->translatedFormat('d F Y');
                        } catch (\Throwable $e) {
                            return $tanggal_lahir;
                        }
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Filter Role')
                    ->relationship('roles', 'name')
                    ->options([
                        'super_admin' => '👑 Super Admin',
                        'manager_cabang' => '🏢 Manager Cabang',
                    ]),
                SelectFilter::make('cabang_id')
                    ->label('Filter Cabang')
                    ->options(Cabang::all()->pluck('nama', 'id')),
                TernaryFilter::make('is_visible')
                    ->label('Status Aktif')
                    ->placeholder('Semua Status')
                    ->trueLabel('Hanya yang Aktif')
                    ->falseLabel('Hanya yang Nonaktif'),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('secondary')
                    ->modalHeading(fn (User $record) => 'Detail User - ' . $record->name)
                    ->infolist([
                        InfolistSection::make('Informasi Akun Pengguna')
                            ->schema([
                                InfolistGrid::make(3)
                                    ->schema([
                                        ImageEntry::make('avatar_url')
                                            ->label('Foto Profil')
                                            ->circular()
                                            ->disk('public')
                                            ->defaultImageUrl(fn (User $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=FFFFFF&background=0284c7')
                                            ->columnSpan(1),
                                        InfolistGrid::make(2)
                                            ->schema([
                                                TextEntry::make('name')->label('Nama Lengkap')->weight('font-bold'),
                                                TextEntry::make('username')->label('Username')->badge()->color('warning'),
                                                TextEntry::make('email')->label('Email')->placeholder('-'),
                                                TextEntry::make('roles.name')
                                                    ->label('Role / Hak Akses')
                                                    ->badge()
                                                    ->color(fn (?string $state): string => match ($state) {
                                                        'super_admin' => 'danger',
                                                        'manager_cabang' => 'warning',
                                                        default => 'gray',
                                                    })
                                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                                        'super_admin' => '👑 Super Admin',
                                                        'manager_cabang' => '🏢 Manager Cabang',
                                                        default => $state ? ucfirst(str_replace('_', ' ', $state)) : 'Customer / Lainnya',
                                                    }),
                                                TextEntry::make('cabang.nama')->label('Cabang Penempatan / Kelola')->badge()->color('primary')->placeholder('-'),
                                                TextEntry::make('is_visible')
                                                    ->label('Status Akun')
                                                    ->badge()
                                                    ->color(fn ($state) => $state ? 'success' : 'danger')
                                                    ->formatStateUsing(fn ($state) => $state ? 'Aktif' : 'Nonaktif'),
                                                TextEntry::make('tanggal_lahir')
                                                    ->label('Tanggal Lahir')
                                                    ->state(function (User $record) {
                                                        $tgl = $record->custom_fields['tanggal_lahir'] ?? null;
                                                        if (! $tgl) return '-';
                                                        try {
                                                            $carbon = Carbon::parse($tgl)->locale('id');
                                                            return $carbon->translatedFormat('d F Y') . ' (' . $carbon->age . ' tahun)';
                                                        } catch (\Throwable $e) {
                                                            return $tgl;
                                                        }
                                                    }),
                                                TextEntry::make('created_at')
                                                    ->label('Terdaftar Sejak')
                                                    ->state(fn (User $record) => $record->created_at ? Carbon::parse($record->created_at)->locale('id')->translatedFormat('d F Y H:i') : '-'),
                                            ])
                                            ->columnSpan(2),
                                    ]),
                            ]),
                    ]),
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading(fn (User $record) => 'Edit Data User - ' . $record->name)
                    ->mutateRecordDataUsing(function (array $data, User $record): array {
                        $data['role'] = $record->roles->first()?->name ?? 'super_admin';
                        $data['tanggal_lahir'] = $record->custom_fields['tanggal_lahir'] ?? null;
                        return $data;
                    })
                    ->form([
                        Grid::make()
                            ->columns(1)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama Lengkap')
                                    ->required()
                                    ->autocomplete(false),
                                TextInput::make('username')
                                    ->label('Username Unik')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->validationMessages([
                                        'unique' => 'Username ini sudah digunakan.',
                                        'required' => 'Username wajib diisi.',
                                    ])
                                    ->autocomplete(false),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->required()
                                    ->email()
                                    ->unique(ignoreRecord: true)
                                    ->validationMessages([
                                        'unique' => 'Email ini sudah digunakan.',
                                        'required' => 'Email wajib diisi.',
                                    ])
                                    ->autocomplete(false),
                                Select::make('role')
                                    ->label('Role / Hak Akses')
                                    ->options([
                                        'super_admin' => '👑 Super Admin',
                                        'manager_cabang' => '🏢 Manager Cabang',
                                        'karyawan' => '👤 Karyawan (Pindahkan ke Menu Karyawan)',
                                    ])
                                    ->default(fn (User $record) => $record->roles->first()?->name ?? 'super_admin')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state !== 'manager_cabang') {
                                            $set('cabang_id', null);
                                        }
                                    }),
                                Select::make('cabang_id')
                                    ->label('Cabang yang Dikelola')
                                    ->options(Cabang::all()->pluck('nama', 'id'))
                                    ->placeholder('Pilih Cabang Penempatan')
                                    ->selectablePlaceholder(false)
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn (Forms\Get $get) => $get('role') === 'manager_cabang')
                                    ->required(fn (Forms\Get $get) => $get('role') === 'manager_cabang')
                                    ->helperText('Pilih cabang penempatan untuk Manager Cabang ini.'),
                                TextInput::make('password')
                                    ->label('Password Baru')
                                    ->password()
                                    ->revealable()
                                    ->placeholder('Masukkan password baru jika ingin mengubah')
                                    ->helperText('Kosongkan jika tidak ingin mengubah password. Klik ikon mata untuk melihat password.')
                                    ->autocomplete('new-password'),
                                DatePicker::make('tanggal_lahir')
                                    ->label('Tanggal Lahir')
                                    ->nullable()
                                    ->locale('id')
                                    ->formatStateUsing(fn (User $user) => $user?->custom_fields['tanggal_lahir'] ?? null),
                                Toggle::make('is_visible')
                                    ->label('Status Akun Aktif')
                                    ->default(true),
                                FileUpload::make('avatar_url')
                                    ->label('Foto Profil')
                                    ->image()
                                    ->directory('avatars')
                                    ->disk('public')
                                    ->avatar()
                                    ->maxFiles(1),
                            ]),
                    ])
                    ->using(function (User $record, array $data): User {
                        DB::beginTransaction();
                        try {
                            $selectedRole = $data['role'] ?? ($record->roles->first()?->name ?? 'super_admin');
                            $cabangId = ($selectedRole === 'manager_cabang') ? ($data['cabang_id'] ?? null) : null;
                            $tipeKaryawan = ($selectedRole === 'karyawan') ? 'helpman' : null;

                            $record->update([
                                'name' => $data['name'],
                                'username' => $data['username'],
                                'email' => !empty($data['email']) ? $data['email'] : null,
                                'cabang_id' => $cabangId,
                                'tipe_karyawan' => $tipeKaryawan,
                                'is_visible' => $data['is_visible'] ?? true,
                                'avatar_url' => $data['avatar_url'] ?? $record->avatar_url,
                            ]);

                            if (! empty($data['password'])) {
                                $record->update([
                                    'password' => Hash::make($data['password']),
                                ]);
                            }

                            if (isset($data['tanggal_lahir'])) {
                                $record->update([
                                    'custom_fields' => [
                                        'tanggal_lahir' => $data['tanggal_lahir'],
                                    ],
                                ]);
                            }

                            if ($selectedRole === 'manager_cabang') {
                                $record->syncRoles(['manager_cabang']);
                                if ($cabangId) {
                                    $oldBranchManager = User::where('id', '!=', $record->id)
                                        ->whereHas('managedCabang', fn ($q) => $q->where('id', $cabangId))
                                        ->first();
                                    if ($oldBranchManager && ! $oldBranchManager->hasRole('super_admin')) {
                                        $oldBranchManager->syncRoles(['karyawan']);
                                        if (empty($oldBranchManager->tipe_karyawan)) {
                                            $oldBranchManager->update(['tipe_karyawan' => 'helpman']);
                                        }
                                    }
                                    Cabang::where('manager_id', $record->id)->where('id', '!=', $cabangId)->update(['manager_id' => null]);
                                    Cabang::where('id', $cabangId)->update(['manager_id' => $record->id]);
                                }
                            } elseif ($selectedRole === 'super_admin') {
                                $record->syncRoles(['super_admin']);
                                Cabang::where('manager_id', $record->id)->update(['manager_id' => null]);
                            } elseif ($selectedRole === 'karyawan') {
                                $record->syncRoles(['karyawan']);
                                Cabang::where('manager_id', $record->id)->update(['manager_id' => null]);
                            }

                            DB::commit();

                            Notification::make()
                                ->title('Sukses!')
                                ->body('Data user berhasil diperbarui.')
                                ->success()
                                ->send();

                            return $record;
                        } catch (Exception $e) {
                            DB::rollBack();

                            Notification::make()
                                ->title('Gagal!')
                                ->body('Edit user gagal: ' . $e->getMessage())
                                ->danger()
                                ->send();

                            return $record;
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->disabled(fn (User $record) => $record->id === auth()->id() || $record->email === 'admin@gmail.com')
                    ->tooltip(fn (User $record) => ($record->id === auth()->id() || $record->email === 'admin@gmail.com') ? 'Akun utama atau akun login saat ini tidak dapat dihapus.' : null)
                    ->before(function (User $record, Tables\Actions\DeleteAction $action) {
                        if ($record->id === auth()->id() || $record->email === 'admin@gmail.com') {
                            Notification::make()
                                ->title('Aksi Ditolak')
                                ->body('Akun utama atau akun login saat ini tidak dapat dihapus.')
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    })
                    ->after(function (User $record) {
                        Cabang::where('manager_id', $record->id)->update(['manager_id' => null]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            $filteredRecords = $records->reject(fn (User $user) => $user->id === auth()->id() || $user->email === 'admin@gmail.com');
                            $filteredRecords->each->delete();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUserLains::route('/'),
        ];
    }
}
