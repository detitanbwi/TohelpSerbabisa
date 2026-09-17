<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use Illuminate\Http\Request;

class CabangController extends Controller
{
    /**
     * List all active branches.
     */
    public function index()
    {
        $cabangs = Cabang::with('manager:id,name,username,email')->get()->map(function ($c) {
            return [
                'id' => $c->id,
                'nama' => $c->nama,
                'no_wa' => $c->no_wa ?? '6285695908981',
                'formatted_no_wa' => $c->formatted_no_wa,
                'lat' => (float) $c->lat,
                'lng' => (float) ($c->lng ?? $c->long),
                'free_distance_km' => (float) ($c->free_distance_km ?? 3.0),
                'manager' => $c->manager ? [
                    'id' => $c->manager->id,
                    'name' => $c->manager->name,
                    'username' => $c->manager->username,
                ] : null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $cabangs,
        ]);
    }

    /**
     * Find nearest branch by user coordinates.
     */
    public function nearest(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $userLat = (float) $request->lat;
        $userLng = (float) $request->lng;

        $cabangs = Cabang::all();
        if ($cabangs->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Belum ada data cabang',
            ], 404);
        }

        $nearest = null;
        $minDist = PHP_FLOAT_MAX;

        foreach ($cabangs as $c) {
            $cLat = (float) $c->lat;
            $cLng = (float) ($c->lng ?? $c->long);

            // Haversine formula for distance in KM
            $dLat = deg2rad($cLat - $userLat);
            $dLng = deg2rad($cLng - $userLng);
            $a = sin($dLat / 2) * sin($dLat / 2) +
                 cos(deg2rad($userLat)) * cos(deg2rad($cLat)) *
                 sin($dLng / 2) * sin($dLng / 2);
            $dist = 6371 * (2 * atan2(sqrt($a), sqrt(1 - $a)));

            if ($dist < $minDist) {
                $minDist = $dist;
                $nearest = $c;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'cabang' => [
                    'id' => $nearest->id,
                    'nama' => $nearest->nama,
                    'no_wa' => $nearest->no_wa ?? '6285695908981',
                    'formatted_no_wa' => $nearest->formatted_no_wa,
                    'lat' => (float) $nearest->lat,
                    'lng' => (float) ($nearest->lng ?? $nearest->long),
                    'free_distance_km' => (float) ($nearest->free_distance_km ?? 3.0),
                ],
                'distance_km' => round($minDist, 2),
            ]
        ]);
    }
}
