<?php

namespace App\Http\Controllers;

use App\Services\LayananService;
use Illuminate\Http\Request;

class DynamicLayananController extends Controller
{
    public function index(string $slug, Request $request)
    {
        $cleanSlug = trim($slug, '/');
        $cabangId = session('selected_cabang_id') ?? $request->cabang_id;
        $layanan = LayananService::getLayanan($cleanSlug, $cabangId);

        if (!$layanan) {
            abort(404, 'Layanan tidak ditemukan atau belum aktif.');
        }

        return view('pages.common.service-detail', [
            'layanan' => $layanan,
            'orderRoute' => 'layanan.pesan',
            'orderRouteParams' => ['slug' => $cleanSlug],
            'slug' => $cleanSlug,
        ]);
    }

    public function pesan(string $slug, Request $request)
    {
        $cleanSlug = trim($slug, '/');
        $cabangId = session('selected_cabang_id') ?? $request->cabang_id;
        $result = LayananService::processPesan($request, $cleanSlug, $cabangId);

        return response()->json($result);
    }
}
