<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AbsensiApiController extends Controller
{
    /**
     * Check attendance status for today.
     */
    public function today(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $now = Carbon::now('Asia/Jakarta');
        $nowTime = $now->format('H:i:s');

        $masterAbsensi = \App\Models\AbsensiBase::first();

        $absensi = Absensi::with('media')
            ->where('karyawan_id', $user->id)
            ->whereDate('tanggal', $today)
            ->first();

        $fotoUrl = $absensi ? $absensi->getFirstMediaUrl('bukti-absensi') : null;

        $times = \App\Services\AbsensiService::getMasterAbsensiTimes();
        $jamMasukSetting = $times['jam_masuk_formatted'];
        $jamKeluarSetting = $times['jam_keluar_formatted'];

        $isWithinTime = true;
        $timeStatus = 'open'; // 'early', 'open', 'late'
        $timeMessage = "Jadwal presensi sedang dibuka ({$jamMasukSetting} - {$jamKeluarSetting} WIB).";

        if ($nowTime < $times['jam_masuk']) {
            $isWithinTime = false;
            $timeStatus = 'early';
            $timeMessage = "Presensi belum dibuka. Jadwal presensi dimulai pukul {$jamMasukSetting} hingga {$jamKeluarSetting} WIB.";
        } elseif ($nowTime > $times['jam_keluar']) {
            $isWithinTime = false;
            $timeStatus = 'late';
            $timeMessage = "Waktu presensi telah berakhir pada pukul {$jamKeluarSetting} WIB.";
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'has_checked_in' => (bool) $absensi,
                'can_check_in' => !$absensi && $isWithinTime,
                'time_status' => $timeStatus,
                'time_message' => $timeMessage,
                'jam_masuk_setting' => $jamMasukSetting,
                'jam_keluar_setting' => $jamKeluarSetting,
                'absensi' => $absensi ? [
                    'id' => $absensi->id,
                    'tanggal' => $absensi->tanggal,
                    'jam_masuk' => $absensi->jam_masuk,
                    'foto_url' => $fotoUrl ?: null,
                    'photo_path' => $fotoUrl ?: null,
                ] : null,
            ]
        ]);
    }

    /**
     * Check-in attendance for Helpman/Joki.
     */
    public function checkIn(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $now = Carbon::now('Asia/Jakarta');
        $nowTime = $now->format('H:i:s');

        // Validasi Jadwal Waktu Presensi terhadap Master Data Absensi
        $times = \App\Services\AbsensiService::getMasterAbsensiTimes();
        $jamMasuk = $times['jam_masuk'];
        $jamKeluar = $times['jam_keluar'];
        $jamMasukFormatted = $times['jam_masuk_formatted'];
        $jamKeluarFormatted = $times['jam_keluar_formatted'];

        if ($nowTime < $jamMasuk) {
            return response()->json([
                'status' => 'error',
                'message' => "Presensi belum dibuka. Jadwal presensi dimulai pukul {$jamMasukFormatted} hingga {$jamKeluarFormatted} WIB.",
            ], 422);
        }

        if ($nowTime > $jamKeluar) {
            return response()->json([
                'status' => 'error',
                'message' => "Waktu presensi telah berakhir pada pukul {$jamKeluarFormatted} WIB. Presensi di luar jam yang ditentukan tidak dapat diterima.",
            ], 422);
        }

        // Identify any uploaded file key
        $fileKey = null;
        foreach (['foto', 'bukti_absen', 'image', 'file', 'photo'] as $key) {
            if ($request->hasFile($key)) {
                $fileKey = $key;
                break;
            }
        }

        $existing = Absensi::with('media')
            ->where('karyawan_id', $user->id)
            ->whereDate('tanggal', $today)
            ->first();

        if ($existing) {
            // If existing attendance has no media yet and a photo is uploaded now, attach it!
            if ($fileKey && $existing->getMedia('bukti-absensi')->isEmpty()) {
                try {
                    $existing->addMediaFromRequest($fileKey)->toMediaCollection('bukti-absensi');
                } catch (\Exception $e) {
                    Log::error('Gagal menyimpan foto absensi API: ' . $e->getMessage());
                }
            }

            $existingFotoUrl = $existing->getFirstMediaUrl('bukti-absensi');
            return response()->json([
                'status' => 'success',
                'message' => 'Absensi hari ini sudah tercatat.',
                'data' => [
                    'id' => $existing->id,
                    'tanggal' => $existing->tanggal,
                    'jam_masuk' => $existing->jam_masuk,
                    'foto_url' => $existingFotoUrl ?: null,
                    'photo_path' => $existingFotoUrl ?: null,
                ]
            ], 200);
        }

        $absensi = Absensi::create([
            'karyawan_id' => $user->id,
            'tanggal' => $today,
            'jam_masuk' => $nowTime,
        ]);

        if ($fileKey) {
            try {
                $absensi->addMediaFromRequest($fileKey)->toMediaCollection('bukti-absensi');
            } catch (\Exception $e) {
                Log::error('Gagal menyimpan foto absensi API: ' . $e->getMessage());
            }
        }

        // Set status personil menjadi aktif (siaga) setelah presensi
        $user->update(['is_visible' => true]);

        $fotoUrl = $absensi->getFirstMediaUrl('bukti-absensi');

        return response()->json([
            'status' => 'success',
            'message' => 'Absensi masuk berhasil (Tepat Waktu)',
            'data' => [
                'id' => $absensi->id,
                'tanggal' => $absensi->tanggal,
                'jam_masuk' => $absensi->jam_masuk,
                'foto_url' => $fotoUrl ?: null,
                'photo_path' => $fotoUrl ?: null,
            ]
        ], 201);
    }

    /**
     * Attendance history for the authenticated worker.
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $query = Absensi::with('media')
            ->where('karyawan_id', $user->id)
            ->orderBy('tanggal', 'desc');

        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('tanggal', $request->month)
                  ->whereYear('tanggal', $request->year);
        }

        $history = $query->paginate($request->get('per_page', 20));

        $history->getCollection()->transform(function ($item) {
            $url = $item->getFirstMediaUrl('bukti-absensi');
            $item->foto_url = $url;
            $item->photo_path = $url;
            return $item;
        });

        return response()->json([
            'status' => 'success',
            'data' => $history
        ]);
    }
}
