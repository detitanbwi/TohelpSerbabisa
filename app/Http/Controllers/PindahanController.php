<?php

namespace App\Http\Controllers;

use App\Services\LayananService;
use Illuminate\Http\Request;

class PindahanController extends Controller
{
    public function index(Request $request)
    {
        $cabangId = session('selected_cabang_id') ?? $request->cabang_id;
        $layanan = LayananService::getLayanan('pindahan', $cabangId);

        return view('pages.pindahan.index', [
            'layanan' => $layanan,
        ]);
    }

    public function pesan(Request $request)
    {
        $cabangId = session('selected_cabang_id') ?? $request->cabang_id;
        $result = LayananService::processPesan($request, 'pindahan', $cabangId);

        return response()->json($result);
    }
}
