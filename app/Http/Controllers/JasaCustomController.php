<?php

namespace App\Http\Controllers;

use App\Services\LayananService;
use Illuminate\Http\Request;

class JasaCustomController extends Controller
{
    public function index(Request $request)
    {
        $cabangId = session('selected_cabang_id') ?? $request->cabang_id;
        $layanan = LayananService::getLayanan('jasa-kustom', $cabangId);

        return view('pages.jasa-kustom.index', [
            'layanan' => $layanan,
        ]);
    }

    public function pesan(Request $request)
    {
        $cabangId = session('selected_cabang_id') ?? $request->cabang_id;
        $result = LayananService::processPesan($request, 'jasa-kustom', $cabangId);

        return response()->json($result);
    }
}
