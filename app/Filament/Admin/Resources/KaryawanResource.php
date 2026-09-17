<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\KaryawanResource\Pages;
use App\Filament\Admin\Resources\KaryawanResource\RelationManagers;
use App\Models\Cabang;
use App\Models\User;
use Exception;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
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

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Manajemen Pengguna';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                        Select::make('cabang_id')
                            ->label('Cabang')
                            ->options(Cabang::all()->pluck('nama', 'id'))
                            ->default(fn () => auth()->user()?->hasRole('manager_cabang') ? auth()->user()->cabang_id : null)
                            ->disabled(fn () => auth()->user()?->hasRole('manager_cabang'))
                            ->dehydrated()
                            ->required(),
                        Select::make('tipe_karyawan')
                            ->label('Tipe Personil')
                            ->options([
                                'helpman' => 'Helpman (Lapangan)',
                                'joki' => 'Joki (Tugas / Digital)',
                            ])
                            ->default('helpman')
                            ->required(),
                        TextInput::make('password')
                            ->password()
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
                            ->label('Foto')
                            ->maxFiles(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['media', 'roles', 'cabang'])
                    ->whereDoesntHave('roles', fn (Builder $q) => $q->where('name', 'super_admin'))
                    ->where(function (Builder $q) {
                        $q->where('email', '!=', 'admin@gmail.com')
                          ->orWhereNull('email');
                    });

                if (auth()->user()?->hasRole('manager_cabang')) {
                    $query->where('cabang_id', auth()->user()->cabang_id)
                          ->whereHas('roles', fn (Builder $q) => $q->where('name', 'karyawan'));
                }
            })
            ->columns([
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
                Tables\Columns\TextColumn::make('tipe_karyawan')
                    ->label('Tipe Personil')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'joki' => 'success',
                        default => 'info',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'joki' => 'Joki',
                        default => 'Helpman',
                    }),
                Tables\Columns\TextColumn::make('cabang.nama')
                    ->label('Cabang')
                    ->badge()
                    ->color('primary')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                ToggleColumn::make('is_visible')
                    ->label('Siaga (Aktif)')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_lahir')
                    ->label('Tanggal Lahir')
                    ->getStateUsing(function (User $user)
                    {
                        // ambil usia dari tanggal lahir
                        $tanggal_lahir = $user?->custom_fields['tanggal_lahir'] ?? null;
                        if (!$tanggal_lahir) {
                            return '-';
                        }
                        try {
                            $usia = date_diff(date_create($tanggal_lahir), date_create('now'))->y;
                            return $usia . ' tahun';
                        } catch (\Throwable $e) {
                            return '-';
                        }
                    }),
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('Foto'),
            ])
            ->filters([
                SelectFilter::make('cabang_id')
                    ->label('Filter Cabang')
                    ->options(Cabang::all()->pluck('nama', 'id'))
                    ->visible(fn() => auth()->user()?->hasRole('super_admin') ?? false),
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
                Tables\Actions\Action::make('lihatAbsensi')
                    ->label('Lihat Absensi')
                    ->color('info')
                    ->icon('heroicon-o-document')
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
                                ->password()
                                ->autocomplete('new-password'),
                            DatePicker::make('tanggal_lahir')
                                ->label('Tanggal Lahir')
                                ->required(fn(string $operation): bool => $operation === 'create')
                                ->locale('id')
                                ->formatStateUsing(fn(User $user) => $user?->custom_fields['tanggal_lahir'] ?? null),
                            Select::make('cabang_id')
                                ->label('Cabang')
                                ->options(Cabang::all()->pluck('nama', 'id'))
                                ->default(fn () => auth()->user()?->hasRole('manager_cabang') ? auth()->user()->cabang_id : null)
                                ->disabled(fn () => auth()->user()?->hasRole('manager_cabang'))
                                ->dehydrated()
                                ->required(),
                            Select::make('tipe_karyawan')
                                ->label('Tipe Personil')
                                ->options([
                                    'helpman' => 'Helpman (Lapangan)',
                                    'joki' => 'Joki (Tugas / Digital)',
                                ])
                                ->default('helpman')
                                ->required(),
                            Toggle::make('is_visible')
                                ->label('Status Siaga / Aktif')
                                ->default(true),
                            FileUpload::make('avatar_url')
                                ->label('Foto')
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

                            $user->update([
                                'name' => $data['name'],
                                'username' => $data['username'],
                                'email' => !empty($data['email']) ? $data['email'] : null,
                                'avatar_url' => $data['avatar_url'] ?? $user->avatar_url,
                                'cabang_id' => $cabangId,
                                'is_visible' => $data['is_visible'] ?? true,
                                'tipe_karyawan' => $data['tipe_karyawan'] ?? 'helpman',
                            ]);

                            if(isset($data['password']))
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

                            $user->syncRoles(['karyawan']);
        
                            DB::commit();

                            Notification::make()
                                ->title('Sukses!')
                                ->body('Edit karyawan berhasil!')
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
