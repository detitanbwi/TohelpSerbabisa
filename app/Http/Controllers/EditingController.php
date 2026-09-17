<?php

namespace App\Http\Controllers;

use App\Services\LayananService;
use Illuminate\Http\Request;

class EditingController extends Controller
{
    public function index(Request $request)
    {
        $cabangId = session('selected_cabang_id') ?? $request->cabang_id;
        $layanan = LayananService::getLayanan('editing', $cabangId);

        return view('pages.editing.index', [
            'layanan' => $layanan,
        ]);
    }

    public function pesan(Request $request)
    {
        $cabangId = session('selected_cabang_id') ?? $request->cabang_id;
        $result = LayananService::processPesan($request, 'editing', $cabangId);

        return response()->json($result);
    }
}
