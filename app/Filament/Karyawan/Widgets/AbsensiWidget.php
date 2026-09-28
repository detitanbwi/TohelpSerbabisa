<?php

namespace App\Filament\Karyawan\Widgets;

use App\Models\Absensi;
use App\Models\User;
use App\Services\AbsensiService;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AbsensiWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = '30s';

    public function table(Table $table): Table
    {
        // Jalankan sinkronisasi harian agar status seluruh personil akurat
        AbsensiService::syncDailyEmployeeActiveStatus();

        $user = auth()->user();

        return $table
            ->heading('Presensi & Status Siaga Saya Hari Ini')
            ->description('Jadwal Presensi: 07:30 - 09:30 WIB. Silakan lakukan presensi pada rentang waktu ini agar status Anda aktif dan dapat menerima tugas hari ini.')
            ->query(
                User::query()->where('id', $user?->id ?? 0)
            )
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('Foto')
                    ->circular()
                    ->disk('public')
                    ->defaultImageUrl(fn (User $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=FFFFFF&background=0284c7'),

                TextColumn::make('name')
                    ->label('Nama Karyawan')
                    ->description(fn (User $record) => $record->cabang?->nama ? 'Cabang: ' . $record->cabang->nama : null)
                    ->weight('font-bold'),

                TextColumn::make('tipe_karyawan')
                    ->label('Tipe Personil')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'joki' => 'success',
                        'helpman' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'joki' => 'Joki',
                        'helpman' => 'Helpman',
                        default => '-',
                    }),

                TextColumn::make('jam_masuk')
                    ->label('Jam Masuk')
                    ->getStateUsing(fn (User $record) => AbsensiService::getEmployeeStatusToday($record)['jam_masuk_formatted']),

                TextColumn::make('status_presensi')
                    ->label('Status Presensi Hari Ini')
                    ->badge()
                    ->color(fn (User $record) => AbsensiService::getEmployeeStatusToday($record)['status_presensi_badge'])
                    ->icon(fn (User $record) => AbsensiService::getEmployeeStatusToday($record)['status_presensi_icon'])
                    ->getStateUsing(fn (User $record) => AbsensiService::getEmployeeStatusToday($record)['status_presensi'])
                    ->tooltip(fn (User $record) => AbsensiService::getEmployeeStatusToday($record)['keterangan']),

                TextColumn::make('status_keaktifan')
                    ->label('Status Keaktifan')
                    ->badge()
                    ->color(fn (User $record) => AbsensiService::getEmployeeStatusToday($record)['status_keaktifan_badge'])
                    ->icon(fn (User $record) => AbsensiService::getEmployeeStatusToday($record)['status_keaktifan_icon'])
                    ->getStateUsing(fn (User $record) => AbsensiService::getEmployeeStatusToday($record)['status_keaktifan']),
            ])
            ->actions([
                Action::make('uploadBuktiAbsen')
                    ->closeModalByClickingAway(false)
                    ->label('Upload Bukti Presensi')
                    ->icon('heroicon-o-camera')
                    ->color('warning')
                    ->button()
                    ->visible(function (User $record) {
                        if (auth()->id() !== $record->id) {
                            return false;
                        }
                        $status = AbsensiService::getEmployeeStatusToday($record);
                        return !$status['has_checked_in'];
                    })
                    ->form([
                        FileUpload::make('bukti_absen')
                            ->label('Foto Bukti Kehadiran / Selfie Siap Tugas')
                            ->helperText('Unggah foto bukti kehadiran atau foto selfie Anda yang menunjukkan siap menjalankan tugas.')
                            ->image()
                            ->maxFiles(1)
                            ->disk('public')
                            ->directory('bukti-absensi')
                            ->required(),
                    ])
                    ->action(function (array $data, User $record) {
                        // Validasi jadwal presensi terhadap master waktu
                        $timeStatus = AbsensiService::getTimeStatus();

                        if ($timeStatus['status'] === 'early') {
                            Notification::make()
                                ->title('Presensi Belum Dibuka')
                                ->body($timeStatus['message'])
                                ->warning()
                                ->send();
                            return;
                        }

                        if ($timeStatus['status'] === 'closed') {
                            Notification::make()
                                ->title('Waktu Presensi Berakhir')
                                ->body($timeStatus['message'])
                                ->danger()
                                ->send();
                            return;
                        }

                        DB::beginTransaction();
                        try {
                            $today = Carbon::today('Asia/Jakarta')->toDateString();
                            $nowTime = Carbon::now('Asia/Jakarta')->format('H:i:s');

                            $absensi = Absensi::create([
                                'karyawan_id' => $record->id,
                                'tanggal' => $today,
                                'jam_masuk' => $nowTime,
                            ]);

                            $filePath = Storage::disk('public')->path($data['bukti_absen']);
                            if (file_exists($filePath)) {
                                $absensi->addMedia($filePath)->toMediaCollection('bukti-absensi');
                            } elseif (file_exists(storage_path('app/public/' . $data['bukti_absen']))) {
                                $absensi->addMedia(storage_path('app/public/' . $data['bukti_absen']))->toMediaCollection('bukti-absensi');
                            }

                            // Langsung aktifkan status siaga personil di database
                            $record->update(['is_visible' => true]);

                            // Sinkronisasi status
                            AbsensiService::syncDailyEmployeeActiveStatus();

                            DB::commit();

                            Notification::make()
                                ->title('Presensi Berhasil!')
                                ->body('Bukti kehadiran berhasil diunggah. Status Anda kini AKTIF (Siaga Tugas) untuk hari ini.')
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            DB::rollBack();
                            Notification::make()
                                ->title('Gagal Mengunggah Presensi')
                                ->body('Terjadi kendala saat menyimpan data: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }
}
