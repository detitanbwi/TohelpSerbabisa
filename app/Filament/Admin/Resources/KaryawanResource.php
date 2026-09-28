<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\KaryawanResource\Pages;
use App\Filament\Admin\Resources\KaryawanResource\RelationManagers;
use App\Models\Cabang;
use App\Models\User;
use App\Services\AbsensiService;
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
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class KaryawanResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'Karyawan';

    protected static ?string $pluralModelLabel = 'Karyawan';

    protected static ?string $navigationLabel = 'Karyawan';

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Manajemen Pengguna';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['media', 'roles', 'cabang'])
            ->whereHas('roles', fn (Builder $q) => $q->where('name', 'karyawan'));

        if (auth()->user()?->hasRole('manager_cabang') && ! auth()->user()?->hasRole('super_admin')) {
            $query->where('cabang_id', auth()->user()->cabang_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->columns(1)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Karyawan')
                            ->placeholder('Masukkan nama lengkap karyawan')
                            ->required()
                            ->autocomplete(false),
                        TextInput::make('username')
                            ->label('Username Unik')
                            ->placeholder('Contoh: helpman01 atau 001_ANDI')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Username ini sudah digunakan/terdaftar.',
                                'required' => 'Username wajib diisi.',
                            ])
                            ->autocomplete(false),
                        TextInput::make('email')
                            ->label('Email (Opsional)')
                            ->placeholder('Contoh: karyawan@tohelp.com')
                            ->nullable()
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Email ini sudah digunakan.',
                            ])
                            ->autocomplete(false),
                        Select::make('cabang_id')
                            ->label('Cabang Penempatan')
                            ->options(Cabang::all()->pluck('nama', 'id'))
                            ->placeholder('Pilih Cabang Penempatan')
                            ->selectablePlaceholder(false)
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->user()?->hasRole('manager_cabang') ? auth()->user()->cabang_id : null)
                            ->disabled(fn () => auth()->user()?->hasRole('manager_cabang'))
                            ->dehydrated()
                            ->required(),
                        Select::make('role')
                            ->label('Role / Jabatan')
                            ->options(function (?User $record) {
                                $options = [
                                    'karyawan' => '👤 Karyawan (Helpman / Joki)',
                                    'manager_cabang' => '🏢 Manager Cabang',
                                ];
                                if ($record && ($record->hasRole('super_admin') || $record->email === 'admin@gmail.com')) {
                                    $options['super_admin'] = '👑 Super Admin';
                                }
                                return $options;
                            })
                            ->default(fn (?User $record) => $record?->roles->first()?->name ?? 'karyawan')
                            ->visible(fn () => auth()->user()?->hasRole('super_admin') ?? false)
                            ->helperText('Pilih "Manager Cabang" jika user ini ditunjuk sebagai kepala cabang, atau "Karyawan" untuk personil lapangan/digital.')
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state !== 'karyawan') {
                                    $set('tipe_karyawan', null);
                                } else {
                                    $set('tipe_karyawan', 'helpman');
                                }
                            })
                            ->required(),
                        Select::make('tipe_karyawan')
                            ->label('Tipe Personil')
                            ->options([
                                'helpman' => 'Helpman (Lapangan)',
                                'joki' => 'Joki (Tugas / Digital)',
                            ])
                            ->placeholder('Pilih Tipe Personil (Helpman / Joki)')
                            ->selectablePlaceholder(false)
                            ->visible(function (Forms\Get $get, ?User $record) {
                                if (! auth()->user()?->hasRole('super_admin')) {
                                    return true;
                                }
                                $role = $get('role');
                                return $role === 'karyawan' || (empty($role) && (! $record || $record->hasRole('karyawan')));
                            })
                            ->required(function (Forms\Get $get) {
                                if (! auth()->user()?->hasRole('super_admin')) {
                                    return true;
                                }
                                return $get('role') === 'karyawan';
                            })
                            ->dehydrated()
                            ->dehydrateStateUsing(function ($state, Forms\Get $get) {
                                $role = $get('role');
                                if (auth()->user()?->hasRole('super_admin') && $role && $role !== 'karyawan') {
                                    return null;
                                }
                                return $state;
                            }),
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
                            ->label('Tanggal Lahir')
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->locale('id'),
                        Toggle::make('is_visible')
                            ->label('Status Siaga / Aktif')
                            ->helperText('Jika dinonaktifkan (cuti/libur), personil tidak akan muncul dalam daftar siaga tugas.')
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
                      ->whereHas('roles', fn (Builder $q) => $q->where('name', 'karyawan'));

                if (auth()->user()?->hasRole('manager_cabang') && ! auth()->user()?->hasRole('super_admin')) {
                    $query->where('cabang_id', auth()->user()->cabang_id);
                }
            })
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('Foto')
                    ->circular()
                    ->disk('public')
                    ->defaultImageUrl(fn (User $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=FFFFFF&background=0284c7'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Karyawan')
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
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role / Jabatan')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'manager_cabang' => 'warning',
                        'karyawan' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'super_admin' => '👑 Super Admin',
                        'manager_cabang' => '🏢 Manager Cabang',
                        'karyawan' => '👤 Karyawan',
                        default => $state ? ucfirst(str_replace('_', ' ', $state)) : 'Customer (Non-Personil)',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipe_karyawan')
                    ->label('Tipe Personil')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'joki' => 'success',
                        'helpman' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'joki' => 'Joki',
                        'helpman' => 'Helpman',
                        default => '-',
                    })
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('cabang.nama')
                    ->label('Cabang')
                    ->badge()
                    ->color('primary')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('presensi_today')
                    ->label('Presensi Hari Ini')
                    ->badge()
                    ->color(fn (User $record) => AbsensiService::getEmployeeStatusToday($record)['status_presensi_badge'])
                    ->icon(fn (User $record) => AbsensiService::getEmployeeStatusToday($record)['status_presensi_icon'])
                    ->getStateUsing(function (User $record) {
                        $status = AbsensiService::getEmployeeStatusToday($record);
                        if ($status['has_checked_in']) {
                            return 'Sudah Presensi (' . $status['jam_masuk_formatted'] . ')';
                        }
                        return $status['status_presensi'];
                    })
                    ->tooltip(fn (User $record) => AbsensiService::getEmployeeStatusToday($record)['keterangan']),
                ToggleColumn::make('is_visible')
                    ->label('Siaga (Aktif)')
                    ->sortable()
                    ->afterStateUpdated(function (User $record, $state) {
                        $statusText = $state ? 'Aktif (Siaga Tugas)' : 'Nonaktif (Cuti / Libur)';
                        Notification::make()
                            ->title('Status Keaktifan Diperbarui')
                            ->body("Status karyawan {$record->name} berhasil diubah menjadi {$statusText}.")
                            ->success()
                            ->send();
                    }),
                Tables\Columns\TextColumn::make('tanggal_lahir')
                    ->label('Tanggal Lahir')
                    ->getStateUsing(function (User $user)
                    {
                        $tanggal_lahir = $user?->custom_fields['tanggal_lahir'] ?? null;
                        if (!$tanggal_lahir) {
                            return '-';
                        }
                        try {
                            return Carbon::parse($tanggal_lahir)->locale('id')->translatedFormat('d F Y');
                        } catch (\Throwable $e) {
                            return $tanggal_lahir;
                        }
                    }),
            ])
            ->filters([
                SelectFilter::make('presensi_status')
                    ->label('Filter Presensi Hari Ini')
                    ->options([
                        'hadir' => '🟢 Sudah Presensi',
                        'belum_hadir' => '🔴 Belum / Tidak Presensi',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $today = Carbon::today('Asia/Jakarta')->toDateString();
                        if ($data['value'] === 'hadir') {
                            return $query->whereHas('absensi', fn ($q) => $q->whereDate('tanggal', $today));
                        }
                        if ($data['value'] === 'belum_hadir') {
                            return $query->whereDoesntHave('absensi', fn ($q) => $q->whereDate('tanggal', $today));
                        }
                        return $query;
                    }),
                SelectFilter::make('cabang_id')
                    ->label('Filter Cabang')
                    ->options(Cabang::all()->pluck('nama', 'id'))
                    ->visible(fn() => auth()->user()?->hasRole('super_admin') ?? false),
                SelectFilter::make('role')
                    ->label('Filter Role / Jabatan')
                    ->relationship('roles', 'name')
                    ->options([
                        'super_admin' => '👑 Super Admin',
                        'manager_cabang' => '🏢 Manager Cabang',
                        'karyawan' => '👤 Karyawan',
                    ])
                    ->visible(fn () => auth()->user()?->hasRole('super_admin') ?? false),
                SelectFilter::make('tipe_karyawan')
                    ->label('Filter Tipe Personil')
                    ->options([
                        'helpman' => 'Helpman',
                        'joki' => 'Joki',
                    ]),
                TernaryFilter::make('is_visible')
                    ->label('Status Siaga')
                    ->placeholder('Semua Status')
                    ->trueLabel('Hanya yang Siaga (Aktif)')
                    ->falseLabel('Hanya yang Nonaktif (Cuti)'),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('secondary')
                    ->modalHeading(fn (User $record) => 'Detail Data Karyawan - ' . $record->name)
                    ->infolist([
                        InfolistSection::make('Informasi Profil Karyawan')
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
                                                TextEntry::make('roles.name')
                                                    ->label('Role / Jabatan')
                                                    ->badge()
                                                    ->color(fn (?string $state): string => match ($state) {
                                                        'super_admin' => 'danger',
                                                        'manager_cabang' => 'warning',
                                                        'karyawan' => 'info',
                                                        default => 'gray',
                                                    })
                                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                                        'super_admin' => '👑 Super Admin',
                                                        'manager_cabang' => '🏢 Manager Cabang',
                                                        'karyawan' => '👤 Karyawan',
                                                        default => $state ? ucfirst(str_replace('_', ' ', $state)) : 'Customer (Non-Personil)',
                                                    }),
                                                TextEntry::make('email')->label('Email')->placeholder('-'),
                                                TextEntry::make('cabang.nama')->label('Cabang')->badge()->color('primary')->placeholder('-'),
                                                TextEntry::make('tipe_karyawan')
                                                    ->label('Tipe Personil')
                                                    ->badge()
                                                    ->color(fn (?string $state): string => match ($state) {
                                                        'joki' => 'success',
                                                        'helpman' => 'info',
                                                        default => 'gray',
                                                    })
                                                    ->formatStateUsing(fn (?string $state) => match ($state) {
                                                        'joki' => 'Joki (Tugas)',
                                                        'helpman' => 'Helpman (Lapangan)',
                                                        default => '-',
                                                    })
                                                    ->placeholder('-'),
                                                TextEntry::make('status_presensi')
                                                    ->label('Status Presensi Hari Ini')
                                                    ->badge()
                                                    ->color(fn (User $record) => AbsensiService::getEmployeeStatusToday($record)['status_presensi_badge'])
                                                    ->icon(fn (User $record) => AbsensiService::getEmployeeStatusToday($record)['status_presensi_icon'])
                                                    ->state(function (User $record) {
                                                        $status = AbsensiService::getEmployeeStatusToday($record);
                                                        if ($status['has_checked_in']) {
                                                            return 'Sudah Presensi (' . $status['jam_masuk_formatted'] . ')';
                                                        }
                                                        return $status['status_presensi'];
                                                    }),
                                                TextEntry::make('is_visible')
                                                    ->label('Status Siaga / Keaktifan')
                                                    ->badge()
                                                    ->color(fn ($state) => $state ? 'success' : 'danger')
                                                    ->formatStateUsing(fn ($state) => $state ? 'Aktif (Siaga Tugas)' : 'Nonaktif (Belum/Tidak Presensi)'),
                                                TextEntry::make('tanggal_lahir')
                                                    ->label('Tanggal Lahir')
                                                    ->state(function (User $record) {
                                                        $tgl = $record->custom_fields['tanggal_lahir'] ?? null;
                                                        if (!$tgl) return '-';
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
                Tables\Actions\Action::make('lihatAbsensi')
                    ->label('Lihat Absensi')
                    ->color('info')
                    ->icon('heroicon-o-document-text')
                    ->url(fn(User $karyawan) => Pages\LihatAbsensiPage::getUrl(['record' => $karyawan])),
                Tables\Actions\EditAction::make()
                    ->form([
                        Grid::make()
                        ->columns(1)
                        ->schema([
                            TextInput::make('name')
                                ->label('Nama Karyawan')
                                ->required()
                                ->autocomplete(false),
                            TextInput::make('username')
                                ->label('Username Unik')
                                ->placeholder('Contoh: helpman01 atau 001_ANDI')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->validationMessages([
                                    'unique' => 'Username ini sudah digunakan/terdaftar.',
                                    'required' => 'Username wajib diisi.',
                                ])
                                ->autocomplete(false),
                            TextInput::make('email')
                                ->label('Email (Opsional)')
                                ->nullable()
                                ->email()
                                ->unique(ignoreRecord: true)
                                ->validationMessages([
                                    'unique' => 'Email ini sudah digunakan.',
                                ])
                                ->autocomplete(false),
                            TextInput::make('password')
                                ->label('Password')
                                ->password()
                                ->revealable()
                                ->placeholder('Masukkan password baru jika ingin mengubah')
                                ->helperText('Kosongkan jika tidak ingin mengubah password. Klik ikon mata untuk melihat password baru yang diketik.')
                                ->autocomplete('new-password'),
                            DatePicker::make('tanggal_lahir')
                                ->label('Tanggal Lahir')
                                ->required(fn(string $operation): bool => $operation === 'create')
                                ->locale('id')
                                ->formatStateUsing(fn(User $user) => $user?->custom_fields['tanggal_lahir'] ?? null),
                            Select::make('cabang_id')
                                ->label('Cabang Penempatan')
                                ->options(Cabang::all()->pluck('nama', 'id'))
                                ->placeholder('Pilih Cabang Penempatan')
                                ->selectablePlaceholder(false)
                                ->searchable()
                                ->preload()
                                ->default(fn () => auth()->user()?->hasRole('manager_cabang') ? auth()->user()->cabang_id : null)
                                ->disabled(fn () => auth()->user()?->hasRole('manager_cabang'))
                                ->dehydrated()
                                ->required(),
                            Select::make('role')
                                ->label('Role / Jabatan')
                                ->options(function (?User $record) {
                                    $options = [
                                        'karyawan' => '👤 Karyawan (Helpman / Joki)',
                                        'manager_cabang' => '🏢 Manager Cabang',
                                    ];
                                    if ($record && ($record->hasRole('super_admin') || $record->email === 'admin@gmail.com')) {
                                        $options['super_admin'] = '👑 Super Admin';
                                    }
                                    return $options;
                                })
                                ->default(fn (?User $record) => $record?->roles->first()?->name ?? 'karyawan')
                                ->visible(fn () => auth()->user()?->hasRole('super_admin') ?? false)
                                ->helperText('Pilih "Manager Cabang" jika user ini ditunjuk sebagai kepala cabang, atau "Karyawan" untuk personil lapangan/digital.')
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($state !== 'karyawan') {
                                        $set('tipe_karyawan', null);
                                    } else {
                                        $set('tipe_karyawan', 'helpman');
                                    }
                                })
                                ->required(),
                            Select::make('tipe_karyawan')
                                ->label('Tipe Personil')
                                ->options([
                                    'helpman' => 'Helpman (Lapangan)',
                                    'joki' => 'Joki (Tugas / Digital)',
                                ])
                                ->placeholder('Pilih Tipe Personil (Helpman / Joki)')
                                ->selectablePlaceholder(false)
                                ->visible(function (Forms\Get $get, ?User $record) {
                                    if (! auth()->user()?->hasRole('super_admin')) {
                                        return true;
                                    }
                                    $role = $get('role');
                                    return $role === 'karyawan' || (empty($role) && (! $record || $record->hasRole('karyawan')));
                                })
                                ->required(function (Forms\Get $get) {
                                    if (! auth()->user()?->hasRole('super_admin')) {
                                        return true;
                                    }
                                    return $get('role') === 'karyawan';
                                })
                                ->dehydrated()
                                ->dehydrateStateUsing(function ($state, Forms\Get $get) {
                                    $role = $get('role');
                                    if (auth()->user()?->hasRole('super_admin') && $role && $role !== 'karyawan') {
                                        return null;
                                    }
                                    return $state;
                                }),
                            Toggle::make('is_visible')
                                ->label('Status Siaga / Aktif')
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
                    ->using(function(User $user, array $data): User
                    {
                        DB::beginTransaction();
                        try
                        {
                            $cabangId = auth()->user()?->hasRole('manager_cabang') 
                                ? auth()->user()->cabang_id 
                                : ($data['cabang_id'] ?? $user->cabang_id);

                            $selectedRole = (auth()->user()?->hasRole('super_admin') && isset($data['role'])) 
                                ? $data['role'] 
                                : ($user->roles->first()?->name ?? 'karyawan');

                            $tipeKaryawan = ($selectedRole === 'karyawan') 
                                ? ($data['tipe_karyawan'] ?? 'helpman') 
                                : null;

                            $user->update([
                                'name' => $data['name'],
                                'username' => $data['username'],
                                'email' => !empty($data['email']) ? $data['email'] : null,
                                'avatar_url' => $data['avatar_url'] ?? $user->avatar_url,
                                'cabang_id' => $cabangId,
                                'is_visible' => $data['is_visible'] ?? true,
                                'tipe_karyawan' => $tipeKaryawan,
                            ]);

                            if(!empty($data['password']))
                            {
                                $user->update([
                                    'password' => Hash::make($data['password']),
                                ]);
                            }

                            if(isset($data['tanggal_lahir']))
                            {
                                $user->update([
                                    'custom_fields' => [
                                        'tanggal_lahir' => $data['tanggal_lahir'],
                                    ],
                                ]);
                            }

                            if (auth()->user()?->hasRole('super_admin') && isset($data['role'])) {
                                if ($selectedRole === 'manager_cabang') {
                                    $user->syncRoles(['manager_cabang']);
                                    $user->update(['tipe_karyawan' => null]);
                                    if ($cabangId) {
                                        $oldBranchManager = User::where('id', '!=', $user->id)
                                            ->whereHas('managedCabang', fn ($q) => $q->where('id', $cabangId))
                                            ->first();
                                        if ($oldBranchManager && ! $oldBranchManager->hasRole('super_admin')) {
                                            $oldBranchManager->syncRoles(['karyawan']);
                                            if (empty($oldBranchManager->tipe_karyawan)) {
                                                $oldBranchManager->update(['tipe_karyawan' => 'helpman']);
                                            }
                                        }
                                        Cabang::where('manager_id', $user->id)->where('id', '!=', $cabangId)->update(['manager_id' => null]);
                                        Cabang::where('id', $cabangId)->update(['manager_id' => $user->id]);
                                    }
                                } elseif ($selectedRole === 'karyawan') {
                                    $user->syncRoles(['karyawan']);
                                    $user->update([
                                        'tipe_karyawan' => $data['tipe_karyawan'] ?? 'helpman',
                                    ]);
                                    Cabang::where('manager_id', $user->id)->update(['manager_id' => null]);
                                } elseif ($selectedRole === 'super_admin') {
                                    $user->syncRoles(['super_admin']);
                                    $user->update(['tipe_karyawan' => null]);
                                }
                            } elseif (! auth()->user()?->hasRole('super_admin') && $user->hasRole('karyawan')) {
                                $user->syncRoles(['karyawan']);
                            }
        
                            DB::commit();

                            Notification::make()
                                ->title('Sukses!')
                                ->body('Data karyawan berhasil diperbarui.')
                                ->success()
                                ->send();

                            return $user;
                        } catch(Exception $e)
                        {
                            DB::rollBack();

                            Notification::make()
                                ->title('Gagal!')
                                ->body('Edit karyawan gagal! ' . $e->getMessage())
                                ->danger()
                                ->send();

                            return $user;
                        }
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageKaryawans::route('/'),
            'absen' => Pages\LihatAbsensiPage::route('/{record}/absen'),
        ];
    }
}
