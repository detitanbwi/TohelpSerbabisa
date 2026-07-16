<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Transaksi;
use App\Helpers\OrderHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SpaController extends Controller
{
    public function index()
    {
        return view('pages.spa.index');
    }

    public function pesan(Request $request)
    {
        DB::beginTransaction();

        try {
            $data = [
                'order_id' => OrderHelper::generateOrderId('TIP-'),
                'jenis' => 'penitipan',
                'jasa' => $request->jasa,
            ];
            if ($request->has('total_harga')) {
                $data['total_harga'] = $request->total_harga;
            }

            Transaksi::create($data);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil memesan jasa titip barang',
                'order_id' => $data['order_id']
            ]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memesan jasa titip barang'
            ], 500);
        }
    }
}
