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

        // Card 3: Waktu Operasional Saat Ini (WIB) - Real-time running clock
        $nowFormatted = $now->format('H:i:s') . ' WIB';
        $serverTimeMs = round(microtime(true) * 1000);
        $jamMasuk = $times['jam_masuk'];
        $jamKeluar = $times['jam_keluar'];

        $waktuDesc = match ($timeStatus['status']) {
            'early' => 'Menunggu jadwal presensi dibuka',
            'open' => '🟢 Sesi presensi sedang berlangsung',
            default => '🔴 Sesi presensi telah berakhir',
        };

        $clockHtml = new \Illuminate\Support\HtmlString("
            <span
                wire:ignore
                x-data=\"{
                    serverOffset: {$serverTimeMs} - Date.now(),
                    timeStr: '{$nowFormatted}',
                    updateClock() {
                        const now = new Date(Date.now() + this.serverOffset);
                        const jakarta = new Date(now.getTime() + (7 * 3600000));
                        const hh = String(jakarta.getUTCHours()).padStart(2, '0');
                        const mm = String(jakarta.getUTCMinutes()).padStart(2, '0');
                        const ss = String(jakarta.getUTCSeconds()).padStart(2, '0');
                        this.timeStr = hh + ':' + mm + ':' + ss + ' WIB';
                    },
                    init() {
                        this.updateClock();
                        setInterval(() => this.updateClock(), 1000);
                    }
                }\"
                x-text=\"timeStr\"
                class=\"tabular-nums font-mono tracking-tight font-bold\"
            >
                {$nowFormatted}
            </span>
        ");

        $descHtml = new \Illuminate\Support\HtmlString("
            <span
                wire:ignore
                x-data=\"{
                    serverOffset: {$serverTimeMs} - Date.now(),
                    descStr: '{$waktuDesc}',
                    updateDesc() {
                        const now = new Date(Date.now() + this.serverOffset);
                        const jakarta = new Date(now.getTime() + (7 * 3600000));
                        const hh = String(jakarta.getUTCHours()).padStart(2, '0');
                        const mm = String(jakarta.getUTCMinutes()).padStart(2, '0');
                        const ss = String(jakarta.getUTCSeconds()).padStart(2, '0');
                        const cur = hh + ':' + mm + ':' + ss;
                        if (cur < '{$jamMasuk}') {
                            this.descStr = 'Menunggu jadwal presensi dibuka';
                        } else if (cur <= '{$jamKeluar}') {
                            this.descStr = '🟢 Sesi presensi sedang berlangsung';
                        } else {
                            this.descStr = '🔴 Sesi presensi telah berakhir';
                        }
                    },
                    init() {
                        this.updateDesc();
                        setInterval(() => this.updateDesc(), 1000);
                    }
                }\"
                x-text=\"descStr\"
            >
                {$waktuDesc}
            </span>
        ");

        $statWaktu = Stat::make('Waktu Saat Ini (WIB)', $clockHtml)
            ->description($descHtml)
            ->descriptionIcon('heroicon-m-clock')
            ->color($timeStatus['badge'] ?? 'gray');

        return [
            $statUser,
            $statJadwal,
            $statWaktu,
        ];
    }
}
