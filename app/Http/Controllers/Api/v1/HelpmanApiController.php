<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class HelpmanApiController extends Controller
{
    /**
     * List visible & active Helpman / Joki.
     */
    public function available(Request $request)
    {
        $query = User::visible()->with('cabang');

        // Check if role karyawan exists
        if (\Spatie\Permission\Models\Role::where('name', 'karyawan')->exists()) {
            $query->role('karyawan');
        }

        if ($request->filled('cabang_id')) {
            $query->where('cabang_id', $request->cabang_id);
        }

        if ($request->filled('tipe_karyawan')) {
            $query->where('tipe_karyawan', strtolower($request->tipe_karyawan));
        }

        $helpmen = $query->get()->map(function ($h) {
            return [
                'id' => $h->id,
                'name' => $h->name,
                'username' => $h->username,
                'tipe_karyawan' => $h->tipe_karyawan ?? 'helpman',
                'cabang' => $h->cabang ? [
                    'id' => $h->cabang->id,
                    'nama' => $h->cabang->nama,
                ] : null,
                'avatar_url' => $h->avatar_url,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $helpmen,
        ]);
    }
}
