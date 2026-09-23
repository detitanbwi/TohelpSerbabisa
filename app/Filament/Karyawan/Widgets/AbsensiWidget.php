<?php

namespace App\Filament\Karyawan\Widgets;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Absensi;
use Filament\Tables\Table;
use App\Models\AbsensiBase;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

use Illuminate\Support\Facades\Storage;

class AbsensiWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->whereId(auth()->user()->id)
                    ->orWhereHas('absensi', function($query)
                    {
                        $query->whereDate('tanggal', now());
                    })
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Karyawan'),
                TextColumn::make('absensi.jam_masuk')
                    ->label('Jam Masuk')
                    ->getStateUsing(function(User $user)
                    {
                        $latestAbsensi = $user->absensi()
                            ->whereDate('tanggal', now())
                            ->latest()
                            ->first();
                        if(!$latestAbsensi)
                        {
                            return '-';
                        }

                        return Carbon::parse($latestAbsensi->jam_masuk)->format('H:i:s');
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(function(User $user)
                    {
                        $latestAbsensi = $user->absensi()
                            ->whereDate('tanggal', now())
                            ->latest()
                            ->first();
                        if(!$latestAbsensi)
                        {
                            return 'warning';
                        }

                        return 'success';
                    })
                    ->getStateUsing(function(User $user)
                    {
                        $latestAbsensi = $user->absensi()
                            ->whereDate('tanggal', now())
                            ->latest()
                            ->first();
                        if(!$latestAbsensi)
                        {
                            return 'Belum Stand By';
                        }

                        return 'Stand By';
                    }),
            ])
            ->actions([
                Action::make('uploadBuktiAbsen')
                    ->closeModalByClickingAway(false)
                    ->label('Upload Bukti Absen')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->visible(function(User $user)
                    {
                        return !$user->absensi()
                            ->whereDate('tanggal', now())
                            ->exists();
                    })
                    ->button()
                    ->form([
                        FileUpload::make('bukti_absen')
                            ->label('Bukti Absen')
                            ->image()
                            ->maxFiles(1)
                            ->disk('public')
                            ->directory('bukti-absensi')
                            ->required(),
                    ])
                    ->action(function(array $data, User $record)
                    {
                        // cek waktu yang ditetapkan untuk bisa absen
                        $masterAbsensi = AbsensiBase::first();

                        if ($masterAbsensi) {
                            $nowTime = now()->timezone('Asia/Jakarta')->format('H:i:s');
                            $jamMasukSetting = \Carbon\Carbon::parse($masterAbsensi->jam_masuk)->format('H:i');
                            $jamKeluarSetting = \Carbon\Carbon::parse($masterAbsensi->jam_keluar)->format('H:i');

                            if ($nowTime < $masterAbsensi->jam_masuk) {
                                Notification::make()
                                    ->title('Presensi Belum Dibuka')
                                    ->body("Jadwal presensi dimulai pukul {$jamMasukSetting} hingga {$jamKeluarSetting} WIB.")
                                    ->warning()
                                    ->send();
                                return;
                            }

                            if ($nowTime > $masterAbsensi->jam_keluar) {
                                Notification::make()
                                    ->title('Waktu Presensi Berakhir')
                                    ->body("Waktu presensi telah berakhir pada pukul {$jamKeluarSetting} WIB. Anda tidak dapat melakukan presensi di luar jadwal.")
                                    ->danger()
                                    ->send();
                                return;
                            }
                        }
                        DB::beginTransaction();
                        try
                        {
                            $absensi = Absensi::create([
                                'karyawan_id' => $record->id,
                                'tanggal' => now(),
                                'jam_masuk' => now(),
                            ]);

                            $filePath = Storage::disk('public')->path($data['bukti_absen']);
                            if (file_exists($filePath)) {
                                $absensi->addMedia($filePath)
                                    ->toMediaCollection('bukti-absensi');
                            } elseif (file_exists(storage_path('app/public/' . $data['bukti_absen']))) {
                                $absensi->addMedia(storage_path('app/public/' . $data['bukti_absen']))
                                    ->toMediaCollection('bukti-absensi');
                            }

                            DB::commit();

                            Notification::make()
                                ->title('Berhasil')
                                ->body('Bukti absen berhasil diunggah')
                                ->success()
                                ->send();
                        }catch(Exception $e)
                        {
                            DB::rollBack();
                            Notification::make()
                                ->title('Gagal')
                                ->body('Bukti absen gagal diunggah. ' . $e->getMessage())
                                ->danger()
                                ->send();
                            return;
                        }
                        
                    })
            ]);
    }
}
