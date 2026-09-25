<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\AbsensiBase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AbsensiService
{
    /**
     * Get configured attendance window from AbsensiBase or fallback defaults.
     */
    public static function getMasterAbsensiTimes(): array
    {
        $master = AbsensiBase::first();

        $jamMasuk = $master && $master->jam_masuk ? Carbon::parse($master->jam_masuk)->format('H:i:s') : '07:30:00';
        $jamKeluar = $master && $master->jam_keluar ? Carbon::parse($master->jam_keluar)->format('H:i:s') : '09:30:00';

        return [
            'jam_masuk' => $jamMasuk,
            'jam_keluar' => $jamKeluar,
            'jam_masuk_formatted' => Carbon::parse($jamMasuk)->format('H:i'),
            'jam_keluar_formatted' => Carbon::parse($jamKeluar)->format('H:i'),
        ];
    }

    /**
     * Get current status of attendance window for today.
     * Statuses: 'early' (before 07:30), 'open' (07:30 - 09:30), 'closed' (after 09:30)
     */
    public static function getTimeStatus(): array
    {
        $times = self::getMasterAbsensiTimes();
        $now = Carbon::now('Asia/Jakarta');
        $nowTime = $now->format('H:i:s');

        if ($nowTime < $times['jam_masuk']) {
            return [
                'status' => 'early',
                'badge' => 'gray',
                'label' => 'Presensi Belum Dibuka',
                'message' => "Jadwal presensi belum dibuka. Presensi dimulai pukul {$times['jam_masuk_formatted']} hingga {$times['jam_keluar_formatted']} WIB.",
                'is_open' => false,
                'times' => $times,
                'now' => $now,
            ];
        }

        if ($nowTime > $times['jam_keluar']) {
            return [
                'status' => 'closed',
                'badge' => 'danger',
                'label' => 'Waktu Presensi Berakhir',
                'message' => "Waktu presensi telah berakhir pada pukul {$times['jam_keluar_formatted']} WIB. Presensi di luar jadwal tidak dapat diterima.",
                'is_open' => false,
                'times' => $times,
                'now' => $now,
            ];
        }

        return [
            'status' => 'open',
            'badge' => 'warning',
            'label' => 'Presensi Sedang Dibuka',
            'message' => "Jadwal presensi sedang berlangsung ({$times['jam_masuk_formatted']} - {$times['jam_keluar_formatted']} WIB). Silakan segera lakukan presensi.",
            'is_open' => true,
            'times' => $times,
            'now' => $now,
        ];
    }

    /**
     * Get detailed daily status for an employee (today).
     */
    public static function getEmployeeStatusToday(User $user): array
    {
        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $timeStatus = self::getTimeStatus();
        $times = $timeStatus['times'];

        // Cek apakah user memiliki catatan absensi hari ini
        $absensi = $user->absensi()
            ->whereDate('tanggal', $today)
            ->latest()
            ->first();

        if ($absensi) {
            $jamMasukAbsen = Carbon::parse($absensi->jam_masuk)->format('H:i:s');
            $jamMasukShort = Carbon::parse($absensi->jam_masuk)->format('H:i');

            return [
                'has_checked_in' => true,
                'absensi_id' => $absensi->id,
                'jam_masuk' => $jamMasukAbsen,
                'jam_masuk_formatted' => $jamMasukShort . ' WIB',
                'status_presensi' => 'Sudah Presensi',
                'status_presensi_badge' => 'success',
                'status_presensi_icon' => 'heroicon-m-check-circle',
                'status_keaktifan' => 'Aktif (Siaga Tugas)',
                'status_keaktifan_badge' => 'success',
                'status_keaktifan_icon' => 'heroicon-m-shield-check',
                'is_active' => true,
                'keterangan' => "Telah presensi masuk pukul {$jamMasukShort} WIB. Personil berstatus AKTIF dan siap menerima order tugas.",
                'can_check_in' => false,
                'time_status' => $timeStatus['status'],
            ];
        }

        // Jika BELUM presensi hari ini
        if ($timeStatus['status'] === 'early') {
            return [
                'has_checked_in' => false,
                'absensi_id' => null,
                'jam_masuk' => null,
                'jam_masuk_formatted' => '-',
                'status_presensi' => 'Belum Dibuka',
                'status_presensi_badge' => 'gray',
                'status_presensi_icon' => 'heroicon-m-clock',
                'status_keaktifan' => 'Belum Aktif (Menunggu Jadwal)',
                'status_keaktifan_badge' => 'gray',
                'status_keaktifan_icon' => 'heroicon-m-pause-circle',
                'is_active' => false,
                'keterangan' => "Presensi dibuka pukul {$times['jam_masuk_formatted']} WIB. Personil belum aktif.",
                'can_check_in' => false,
                'time_status' => 'early',
            ];
        }

        if ($timeStatus['status'] === 'open') {
            return [
                'has_checked_in' => false,
                'absensi_id' => null,
                'jam_masuk' => null,
                'jam_masuk_formatted' => '-',
                'status_presensi' => 'Belum Presensi',
                'status_presensi_badge' => 'warning',
                'status_presensi_icon' => 'heroicon-m-exclamation-triangle',
                'status_keaktifan' => 'Belum Aktif (Silakan Presensi)',
                'status_keaktifan_badge' => 'warning',
                'status_keaktifan_icon' => 'heroicon-m-clock',
                'is_active' => false,
                'keterangan' => "Jadwal presensi dibuka sampai pukul {$times['jam_keluar_formatted']} WIB. Segera presensi agar status menjadi AKTIF.",
                'can_check_in' => true,
                'time_status' => 'open',
            ];
        }

        // closed: waktu presensi telah berakhir dan belum presensi
        return [
            'has_checked_in' => false,
            'absensi_id' => null,
            'jam_masuk' => null,
            'jam_masuk_formatted' => '-',
            'status_presensi' => 'Tidak Melakukan Presensi',
            'status_presensi_badge' => 'danger',
            'status_presensi_icon' => 'heroicon-m-x-circle',
            'status_keaktifan' => 'Tidak Aktif Hari Ini',
            'status_keaktifan_badge' => 'danger',
            'status_keaktifan_icon' => 'heroicon-m-no-symbol',
            'is_active' => false,
            'keterangan' => "Batas presensi ({$times['jam_keluar_formatted']} WIB) telah berakhir. Personil TIDAK AKTIF dan tidak bertugas hari ini.",
            'can_check_in' => false,
            'time_status' => 'closed',
        ];
    }

    /**
     * Synchronize employee active status (is_visible) based on today's attendance.
     * - Helpman/Karyawan who checked in today within the window -> is_visible = true
     * - Helpman/Karyawan who have NOT checked in today -> is_visible = false
     */
    public static function syncDailyEmployeeActiveStatus(): array
    {
        $today = Carbon::today('Asia/Jakarta')->toDateString();

        // Ambil semua ID karyawan yang sudah presensi hari ini
        $karyawanWithPresensiIds = Absensi::whereDate('tanggal', $today)
            ->pluck('karyawan_id')
            ->unique()
            ->toArray();

        // 1. Karyawan yang SUDAH presensi hari ini -> aktifkan (is_visible = true)
        $activatedCount = 0;
        if (!empty($karyawanWithPresensiIds)) {
            $activatedCount = User::whereIn('id', $karyawanWithPresensiIds)
                ->where('is_visible', false)
                ->update(['is_visible' => true]);
        }

        // 2. Karyawan Helpman yang BELUM presensi hari ini -> nonaktifkan (is_visible = false)
        // Fokuskan pada personil lapangan (tipe_karyawan = 'helpman' atau karyawan pada umumnya)
        $deactivatedCount = User::whereHas('roles', fn ($q) => $q->where('name', 'karyawan'))
            ->where(function ($q) {
                $q->where('tipe_karyawan', 'helpman')
                  ->orWhereNull('tipe_karyawan');
            })
            ->whereNotIn('id', $karyawanWithPresensiIds)
            ->where('is_visible', true)
            ->update(['is_visible' => false]);

        return [
            'activated' => $activatedCount,
            'deactivated' => $deactivatedCount,
            'present_count' => count($karyawanWithPresensiIds),
        ];
    }
}
