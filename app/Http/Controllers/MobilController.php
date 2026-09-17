<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Cabang;
use App\Models\Voucher;
use App\Models\Transaksi;
use App\Models\TarifDasar;
use App\Models\TarifJarak;
use Illuminate\Support\Str;
use App\Helpers\OrderHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MobilController extends Controller
{
    public function index(Request $request)
    {
        $cabang = Cabang::all();
        if ($request->has('cabang_id')) {
            session(['selected_cabang_id' => (int) $request->get('cabang_id')]);
        }
        $hasSelectedCabang = session()->has('selected_cabang_id') || $request->has('cabang_id');
        $cabangId = $request->get('cabang_id') ?? session('selected_cabang_id') ?? ($cabang->first()?->id ?? 1);
        $activeCabang = Cabang::find($cabangId) ?? $cabang->first();

        return view('pages.taxi.index', [
            'tarifDasar' => TarifDasar::whereJenis('Mobil')->first(),
            'tarifJarak' => TarifJarak::whereJenis('Mobil')->first(),
            'cabang' => $cabang,
            'activeCabang' => $activeCabang,
            'hasSelectedCabang' => $hasSelectedCabang,
        ]);
    }

    public function showPricing(Request $request)
    {
        $discount = null;
        if($request->discount)
        {
            $discount = Voucher::where('nama', $request->discount)->first();

            if(!$discount)
            {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Voucher tidak ditemukan'
                ], 404);
            }
        }
        $cabangId = $request->cabang ?? $request->cabang_id ?? session('selected_cabang_id');
        $harga = getPricing('Mobil', $request->jarakBaseCampKeTitikJemput, $request->jarakTitikJemputKeTitikTujuan, $request->jarakBaseCampKeTitikTujuan, $request->jarakTitikTujuanKeTitikJemput, $discount->persentase ?? null, $cabangId);

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mendapatkan harga',
            'harga' => $harga,
        ]);
    }

    public function pesan(Request $request)
    {
        DB::beginTransaction();

        try {
            $cabangId = $request->cabang ?? $request->cabang_id ?? session('selected_cabang_id') ?? Cabang::first()?->id;
            $tip = max(0, (int) ($request->tip ?? 0));
            $basePrice = getPricing(
                'Mobil',
                $request->jarakBaseCampKeTitikJemput,
                $request->jarak,
                $request->jarakBaseCampKeTitikTujuan,
                $request->jarakTitikTujuanKeTitikJemput,
                Voucher::whereNama($request->voucher)->first()?->persentase ?? null,
                $cabangId
            );
            $totalHarga = $basePrice + $tip;

            $data = [
                'order_id' =>  OrderHelper::generateOrderId('TX-'),
                'jenis' => 'taxi',
                'voucher_id' => Voucher::whereNama($request->voucher)->first()?->id ?? null,
                'jarak' => $request->jarak,
                'titik_jemput' => $request->titik_jemput,
                'titik_tujuan' => $request->titik_tujuan,
                'cabang_id' => $cabangId,
                'tip' => $tip,
                'total_harga' => $totalHarga,
            ];
            Transaksi::create($data);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil memesan jasa taxi',
                'order_id' => $data['order_id'],
                'harga' => $data['total_harga'],
                'tip' => $tip,
            ]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memesan jasa taxi'
            ], 500);
        }
    }
}
