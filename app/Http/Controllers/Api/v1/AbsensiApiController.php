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
        $today = Carbon::today()->toDateString();

        $absensi = Absensi::with('media')
            ->where('karyawan_id', $user->id)
            ->whereDate('tanggal', $today)
            ->first();

        $fotoUrl = $absensi ? $absensi->getFirstMediaUrl('bukti-absensi') : null;

        return response()->json([
            'status' => 'success',
            'data' => [
                'has_checked_in' => (bool) $absensi,
                'absensi' => $absensi ? [
                    'id' => $absensi->id,
                    'tanggal' => $absensi->tanggal,
                    'jam_masuk' => $absensi->jam_masuk,
                    'foto_url' => $fotoUrl,
                    'photo_path' => $fotoUrl,
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
        $today = Carbon::today()->toDateString();
        $now = Carbon::now()->toTimeString();

        $existing = Absensi::with('media')
            ->where('karyawan_id', $user->id)
            ->whereDate('tanggal', $today)
            ->first();

        if ($existing) {
            $existingFotoUrl = $existing->getFirstMediaUrl('bukti-absensi');
            return response()->json([
                'status' => 'error',
                'message' => 'Anda sudah melakukan absensi masuk hari ini.',
                'data' => [
                    'id' => $existing->id,
                    'tanggal' => $existing->tanggal,
                    'jam_masuk' => $existing->jam_masuk,
                    'foto_url' => $existingFotoUrl,
                    'photo_path' => $existingFotoUrl,
                ]
            ], 400);
        }

        $absensi = Absensi::create([
            'karyawan_id' => $user->id,
            'tanggal' => $today,
            'jam_masuk' => $now,
        ]);

        // Attach photo if provided in request
        $fileKey = null;
        foreach (['foto', 'bukti_absen', 'image', 'file', 'photo'] as $key) {
            if ($request->hasFile($key)) {
                $fileKey = $key;
                break;
            }
        }

        if ($fileKey) {
            try {
                $absensi->addMediaFromRequest($fileKey)->toMediaCollection('bukti-absensi');
            } catch (\Exception $e) {
                Log::error('Gagal menyimpan foto absensi API: ' . $e->getMessage());
            }
        }

        $fotoUrl = $absensi->getFirstMediaUrl('bukti-absensi');

        return response()->json([
            'status' => 'success',
            'message' => 'Absensi masuk berhasil',
            'data' => [
                'id' => $absensi->id,
                'tanggal' => $absensi->tanggal,
                'jam_masuk' => $absensi->jam_masuk,
                'foto_url' => $fotoUrl,
                'photo_path' => $fotoUrl,
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
