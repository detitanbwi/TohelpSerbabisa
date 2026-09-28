<?php

namespace App\Filament\Karyawan\Resources\AbsensiResource\Pages;

use App\Filament\Karyawan\Resources\AbsensiResource;
use App\Models\AbsensiBase;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Support\Htmlable;

class ManageAbsensis extends ManageRecords
{
    protected static string $resource = AbsensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Catat Presensi')
                ->closeModalByClickingAway(false)
                ->mutateFormDataUsing(function(array $data)
                {
                    $absensiBase = AbsensiBase::first();
                    // jika absen sebelum jam masuk atau setelah jam keluar
                    $jamMasuk = $absensiBase->jam_masuk;
                    $jamKeluar = $absensiBase->jam_keluar;
                    $jamSekarang = now()->format('H:i:s');
                    if ($jamSekarang < $jamMasuk) {
                        $data['status'] = 'Belum Absen';
                    } elseif ($jamSekarang > $jamKeluar) {
                        $data['status'] = 'Alfa';
                    } else {
                        $data['status'] = 'Hadir';
                    }
                    $data['karyawan_id'] = auth()->user()->id;
                    return $data;
                }),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Riwayat Kehadiran & Presensi';
    }
}
