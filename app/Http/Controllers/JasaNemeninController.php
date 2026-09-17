<?php

namespace App\Http\Controllers;

use App\Services\LayananService;
use Illuminate\Http\Request;

class JasaNemeninController extends Controller
{
    public function index(Request $request)
    {
        $cabangId = session('selected_cabang_id') ?? $request->cabang_id;
        $layanan = LayananService::getLayanan('jasa-nemenin', $cabangId);

        return view('pages.jasa-nemenin.index', [
            'layanan' => $layanan,
        ]);
    }

    public function pesan(Request $request)
    {
        $cabangId = session('selected_cabang_id') ?? $request->cabang_id;
        $result = LayananService::processPesan($request, 'jasa-nemenin', $cabangId);

        return response()->json($result);
    }
}
