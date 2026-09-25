<?php

namespace App\Filament\Karyawan\Widgets;

use App\Services\AbsensiService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AbsensiOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $user = auth()->user();
        if (!$user) {
            return [];
        }

        $employeeStatus = AbsensiService::getEmployeeStatusToday($user);
        $timeStatus = AbsensiService::getTimeStatus();
        $times = $timeStatus['times'];
        $now = Carbon::now('Asia/Jakarta');

        // Card 1: Status Keaktifan & Presensi Pengguna
        $statusValue = match (true) {
            $employeeStatus['has_checked_in'] => 'Aktif (Siaga Tugas)',
            $employeeStatus['time_status'] === 'early' => 'Belum Dibuka',
            $employeeStatus['time_status'] === 'open' => 'Belum Presensi',
            default => 'Tidak Aktif Hari Ini',
        };

        $statusDesc = match (true) {
            $employeeStatus['has_checked_in'] => "Check-In: {$employeeStatus['jam_masuk_formatted']} (Siap bertugas)",
            $employeeStatus['time_status'] === 'early' => "Presensi dibuka pukul {$times['jam_masuk_formatted']} WIB",
            $employeeStatus['time_status'] === 'open' => "Batas presensi pukul {$times['jam_keluar_formatted']} WIB. Segera presensi!",
            default => "Lewat batas presensi ({$times['jam_keluar_formatted']} WIB). Anda tidak aktif hari ini.",
        };

        $statUser = Stat::make('Status Anda Hari Ini', $statusValue)
            ->description($statusDesc)
            ->descriptionIcon($employeeStatus['status_keaktifan_icon'] ?? 'heroicon-m-information-circle')
            ->color($employeeStatus['status_keaktifan_badge'] ?? 'gray');

        // Card 2: Ketentuan Jadwal Presensi Harian
        $statJadwal = Stat::make('Ketentuan Jadwal Presensi', "{$times['jam_masuk_formatted']} - {$times['jam_keluar_formatted']} WIB")
            ->description('Wajib presensi di rentang waktu ini agar berstatus Aktif')
            ->descriptionIcon('heroicon-m-calendar-days')
            ->color('primary');

        // Card 3: Waktu Operasional Saat Ini (WIB)
        $nowFormatted = $now->format('H:i:s') . ' WIB';
        $waktuDesc = match ($timeStatus['status']) {
            'early' => 'Menunggu jadwal presensi dibuka',
            'open' => '🟢 Sesi presensi sedang berlangsung',
            default => '🔴 Sesi presensi telah berakhir',
        };

        $statWaktu = Stat::make('Waktu Saat Ini (WIB)', $nowFormatted)
            ->description($waktuDesc)
            ->descriptionIcon('heroicon-m-clock')
            ->color($timeStatus['badge'] ?? 'gray');

        return [
            $statUser,
            $statJadwal,
            $statWaktu,
        ];
    }
}
