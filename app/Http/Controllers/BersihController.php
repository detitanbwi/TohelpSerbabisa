<?php

namespace App\Http\Controllers;

use App\Services\LayananService;
use Illuminate\Http\Request;

class BersihController extends Controller
{
    public function index(Request $request)
    {
        $cabangId = session('selected_cabang_id') ?? $request->cabang_id;
        $layanan = LayananService::getLayanan('bersih-bersih', $cabangId);

        return view('pages.bersih.index', [
            'layanan' => $layanan,
        ]);
    }

    public function pesan(Request $request)
    {
        $cabangId = session('selected_cabang_id') ?? $request->cabang_id;
        $result = LayananService::processPesan($request, 'bersih-bersih', $cabangId);

        return response()->json($result);
    }
}
