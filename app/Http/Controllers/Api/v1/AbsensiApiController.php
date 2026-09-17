<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AbsensiApiController extends Controller
{
    /**
     * Check attendance status for today.
     */
    public function today(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();

        $absensi = Absensi::where('karyawan_id', $user->id)
            ->whereDate('tanggal', $today)
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'has_checked_in' => (bool) $absensi,
                'absensi' => $absensi ? [
                    'id' => $absensi->id,
                    'tanggal' => $absensi->tanggal,
                    'jam_masuk' => $absensi->jam_masuk,
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

        $existing = Absensi::where('karyawan_id', $user->id)
            ->whereDate('tanggal', $today)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda sudah melakukan absensi masuk hari ini.',
                'data' => $existing
            ], 400);
        }

        $absensi = Absensi::create([
            'karyawan_id' => $user->id,
            'tanggal' => $today,
            'jam_masuk' => $now,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Absensi masuk berhasil',
            'data' => [
                'id' => $absensi->id,
                'tanggal' => $absensi->tanggal,
                'jam_masuk' => $absensi->jam_masuk,
            ]
        ], 201);
    }

    /**
     * Attendance history for the authenticated worker.
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $query = Absensi::where('karyawan_id', $user->id)->orderBy('tanggal', 'desc');

        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('tanggal', $request->month)
                  ->whereYear('tanggal', $request->year);
        }

        $history = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'status' => 'success',
            'data' => $history
        ]);
    }
}
