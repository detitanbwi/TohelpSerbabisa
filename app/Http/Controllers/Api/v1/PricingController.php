<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PricingController extends Controller
{
    /**
     * Calculate price for ride services (Motor / Mobil).
     */
    public function calculate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jenis' => 'required|in:Motor,Mobil,motor,mobil',
            'jarak_basecamp_ke_jemput' => 'required|numeric|min:0',
            'jarak_jemput_ke_tujuan' => 'required|numeric|min:0',
            'jarak_basecamp_ke_tujuan' => 'required|numeric|min:0',
            'jarak_tujuan_ke_jemput' => 'required|numeric|min:0',
            'voucher_code' => 'nullable|string',
            'cabang_id' => 'nullable|exists:cabangs,id',
            'tip' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parameter tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        $jenis = ucfirst(strtolower($request->jenis));
        $discountPercentage = null;
        $voucher = null;

        if ($request->filled('voucher_code')) {
            $voucher = Voucher::where('nama', $request->voucher_code)->first();
            if ($voucher) {
                $discountPercentage = $voucher->persentase;
            }
        }

        $cabangId = $request->cabang_id ?? Cabang::first()?->id;
        $tip = max(0, (int) ($request->tip ?? 0));

        $basePrice = getPricing(
            $jenis,
            ceil((float) $request->jarak_basecamp_ke_jemput),
            ceil((float) $request->jarak_jemput_ke_tujuan),
            ceil((float) $request->jarak_basecamp_ke_tujuan),
            ceil((float) $request->jarak_tujuan_ke_jemput),
            $discountPercentage,
            $cabangId
        );

        $totalHarga = $basePrice + $tip;

        return response()->json([
            'status' => 'success',
            'data' => [
                'jenis' => $jenis,
                'cabang_id' => $cabangId,
                'base_fare' => $basePrice,
                'tip' => $tip,
                'total_harga' => $totalHarga,
                'voucher' => $voucher ? [
                    'code' => $voucher->nama,
                    'discount_percentage' => $voucher->persentase,
                ] : null,
            ]
        ]);
    }
}
