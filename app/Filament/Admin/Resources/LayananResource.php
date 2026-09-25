<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LayananResource\Pages;
use App\Models\Layanan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Grid;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LayananResource extends Resource
{
    protected static ?string $model = Layanan::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Master Layanan';
    protected static ?int $navigationSort = 2;

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

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Layanan')
                    ->description('Pengaturan umum data master layanan utama.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('nama')
                                    ->label('Nama Layanan')
                                    ->placeholder('Contoh: Jasa Editing & Fotografer')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => 
                                        $operation === 'create' ? $set('slug', Str::slug($state)) : null
                                    ),

                                TextInput::make('slug')
                                    ->label('Slug / URL Path')
                                    ->placeholder('editing')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Digunakan untuk routing URL halaman website (misal: /editing).'),

                                TextInput::make('kode_layanan')
                                    ->label('Kode Layanan (Prefix ID Order)')
                                    ->placeholder('EDT-')
                                    ->default('ORD-')
                                    ->required()
                                    ->helperText('Awalan ID Transaksi unik saat pemesanan (misal: EDT-260917001).'),

                                TextInput::make('urutan')
                                    ->label('Urutan Tampilan')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Semakin kecil angka, semakin awal ditampilkan.'),

                                Toggle::make('is_transportasi')
                                    ->label('Layanan Transportasi (Jarak / KM)')
                                    ->helperText('Aktifkan jika layanan ini berbasis rute perjalanan Google Maps (seperti Ojek atau Taxi).')
                                    ->reactive()
                                    ->default(false),

                                Toggle::make('is_active')
                                    ->label('Status Aktif')
                                    ->default(true)
                                    ->inline(false)
                                    ->helperText('Jika non-aktif, layanan ini disembunyikan dari website.'),
                            ]),
                    ]),

                Section::make('Konfigurasi Tarif Transportasi Standar (Global / Nasional)')
                    ->description('Atur formula perhitungan tarif perjalanan Google Maps default untuk layanan ini. Pengaturan ini menjadi standar acuan global jika suatu cabang belum menetapkan tarif khususnya sendiri.')
                    ->visible(fn (Forms\Get $get, ?Model $record) => 
                        (bool) $get('is_transportasi') || 
                        in_array(trim((string) ($get('slug') ?? $record?->slug), '/'), ['ojek', 'mobil', 'taxi'])
                    )
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('tarif_minimum')
                                    ->label('Tarif Minimum Standar')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->placeholder('Contoh: 7000')
                                    ->required(fn (Forms\Get $get, ?Model $record) => 
                                        (bool) $get('is_transportasi') || in_array(trim((string) ($get('slug') ?? $record?->slug), '/'), ['ojek', 'mobil', 'taxi'])
                                    )
                                    ->helperText('Tarif pembuka perjalanan terendah.'),

                                TextInput::make('tarif_per_km')
                                    ->label(fn (Forms\Get $get, ?Model $record) => 
                                        in_array(trim((string) ($get('slug') ?? $record?->slug), '/'), ['mobil', 'taxi'])
                                            ? 'Tarif Per KM (1 - 10 km)'
                                            : 'Tarif Per KM'
                                    )
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->placeholder('Contoh: 2000')
                                    ->required(fn (Forms\Get $get, ?Model $record) => 
                                        (bool) $get('is_transportasi') || in_array(trim((string) ($get('slug') ?? $record?->slug), '/'), ['ojek', 'mobil', 'taxi'])
                                    )
                                    ->helperText('Biaya per kilometer perjalanan.'),

                                TextInput::make('tarif_per_km_lanjutan')
                                    ->label('Tarif Per KM Lanjutan (> 10 km)')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->placeholder('Contoh: 4000')
                                    ->visible(fn (Forms\Get $get, ?Model $record) => 
                                        in_array(trim((string) ($get('slug') ?? $record?->slug), '/'), ['mobil', 'taxi'])
                                    )
                                    ->helperText('Tarif per kilometer untuk jarak jauh lebih dari 10 km.'),

                                TextInput::make('surcharge_per_km')
                                    ->label('Surcharge Penjemputan / KM')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->placeholder('Contoh: 1000')
                                    ->default(1000)
                                    ->helperText('Biaya per km jika penjemputan dari basecamp melebihi batas gratis.'),

                                TextInput::make('free_distance_km')
                                    ->label('Batas Penjemputan Gratis')
                                    ->numeric()
                                    ->suffix('KM')
                                    ->default(3.0)
                                    ->helperText('Batas jarak gratis dari basecamp driver ke titik penjemputan.'),
                            ]),
                    ]),

                Section::make('Template Pesan WhatsApp (wa.me)')
                    ->description('Atur format teks pesan pemesanan yang otomatis dikirim pelanggan ke WhatsApp cabang.')
                    ->schema([
                        Textarea::make('wa_template')
                            ->label('Format Teks Template')
                            ->rows(9)
                            ->placeholder("Hii kak, saya ingin meminta bantuan To Help\n\nID Order : {order_id}\nJenis Jasa : {layanan}\nTipe Jasa : {sub_layanan}\nHari/Tanggal : \nLokasi : \nNama : \nNomor WhatsApp : ")
                            ->helperText(
                                "Placeholder yang dapat digunakan:\n" .
                                "• {order_id} : ID Transaksi otomatis\n" .
                                "• {layanan} : Nama Layanan Utama\n" .
                                "• {sub_layanan} : Sub-Layanan / Paket yang dipilih\n" .
                                "• {harga} : Nominal Harga\n" .
                                "• {satuan} : Satuan Layanan\n" .
                                "• {cabang} : Nama Cabang yang aktif\n" .
                                "(Jika dikosongkan, format default standar otomatis digunakan)."
                            ),
                    ]),

                Section::make('Deskripsi & Catatan / NB')
                    ->description('Informasi tambahan dan syarat ketentuan opsional di bawah layanan.')
                    ->schema([
                        Textarea::make('deskripsi')
                            ->label('Deskripsi Layanan (Opsional)')
                            ->rows(3)
                            ->placeholder('Jelaskan secara singkat mengenai layanan ini...'),

                        Textarea::make('catatan_nb')
                            ->label('Catatan / NB Khusus (Opsional)')
                            ->rows(2)
                            ->placeholder('Contoh: *Harga belum termasuk biaya operasional / struk belanjaan.'),
                    ]),

                Section::make('Daftar Sub-Layanan & Paket Harga')
                    ->description('Kelola varian paket, sub-layanan, default harga, dan satuannya.')
                    ->schema([
                        Repeater::make('subLayanans')
                            ->relationship('subLayanans')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('nama')
                                            ->label('Nama Sub-Layanan / Paket')
                                            ->placeholder('Contoh: Paket 30 Menit / Rumah Subsidi')
                                            ->required()
                                            ->columnSpan(2),

                                        TextInput::make('urutan')
                                            ->label('Urutan')
                                            ->numeric()
                                            ->default(0),

                                        TextInput::make('default_harga')
                                            ->label('Default Harga (Rp)')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->required()
                                            ->default(0),

                                        TextInput::make('default_satuan')
                                            ->label('Default Satuan')
                                            ->placeholder('Contoh: / jam, / paket, / foto'),

                                        TextInput::make('label_harga_custom')
                                            ->label('Label Awalan (Opsional)')
                                            ->placeholder('Contoh: Start from, Mulai dari'),

                                        Textarea::make('deskripsi')
                                            ->label('Deskripsi / Item Paket (Opsional)')
                                            ->rows(3)
                                            ->placeholder("Rincian paket:\n- 2 Kamar Tidur\n- 1 Dapur")
                                            ->columnSpan(2),

                                        Textarea::make('catatan_nb')
                                            ->label('Catatan Khusus (Opsional)')
                                            ->rows(3)
                                            ->placeholder('Catatan khusus untuk paket ini...')
                                            ->columnSpan(1),

                                        Toggle::make('is_active')
                                            ->label('Status Aktif')
                                            ->default(true)
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->itemLabel(fn (array $state): ?string => $state['nama'] ?? 'Sub-Layanan')
                            ->collapsible()
                            ->collapsed(false)
                            ->defaultItems(1)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('urutan')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('nama')
                    ->label('Nama Layanan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('slug')
                    ->label('Slug / Path')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('kode_layanan')
                    ->label('Kode ID')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('sub_layanans_count')
                    ->counts('subLayanans')
                    ->label('Total Sub-Paket')
                    ->badge()
                    ->color('success'),

                IconColumn::make('is_transportasi')
                    ->label('Transportasi')
                    ->boolean()
                    ->trueIcon('heroicon-o-truck')
                    ->falseIcon('heroicon-o-minus')
                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('urutan')
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
            'index' => Pages\ListLayanans::route('/'),
            'create' => Pages\CreateLayanan::route('/create'),
            'edit' => Pages\EditLayanan::route('/{record}/edit'),
        ];
    }
}
