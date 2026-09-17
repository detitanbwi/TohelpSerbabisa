<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\OrderHelper;
use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Transaksi;
use App\Models\Voucher;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TransaksiApiController extends Controller
{
    /**
     * Create order from Mobile App.
     */
    public function order(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jenis' => 'required|string|in:ojek,taxi,bantuan,jastip,daily,nemenin,service,travel,editing,joki_tugas,teknisi_spa,penitipan',
            'titik_jemput' => 'required|string',
            'titik_tujuan' => 'required|string',
            'jarak' => 'nullable|numeric',
            'jarakBaseCampKeTitikJemput' => 'nullable|numeric',
            'jarakBaseCampKeTitikTujuan' => 'nullable|numeric',
            'jarakTitikTujuanKeTitikJemput' => 'nullable|numeric',
            'voucher_code' => 'nullable|string',
            'cabang_id' => 'nullable|exists:cabangs,id',
            'tip' => 'nullable|numeric|min:0',
            'metode_pembayaran' => 'nullable|string|in:Cash,Transfer,Wallet',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parameter pesanan tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $cabangId = $request->cabang_id ?? Cabang::first()?->id;
            $tip = max(0, (int) ($request->tip ?? 0));
            $voucher = null;

            if ($request->filled('voucher_code')) {
                $voucher = Voucher::where('nama', $request->voucher_code)->first();
            }

            $prefix = match ($request->jenis) {
                'ojek' => 'OJK-',
                'taxi' => 'TX-',
                default => 'TRX-',
            };

            $totalHarga = 0;
            if (in_array($request->jenis, ['ojek', 'taxi'])) {
                $vehicleType = $request->jenis === 'ojek' ? 'Motor' : 'Mobil';
                $basePrice = getPricing(
                    $vehicleType,
                    ceil((float) ($request->jarakBaseCampKeTitikJemput ?? 0)),
                    ceil((float) ($request->jarak ?? 0)),
                    ceil((float) ($request->jarakBaseCampKeTitikTujuan ?? 0)),
                    ceil((float) ($request->jarakTitikTujuanKeTitikJemput ?? 0)),
                    $voucher?->persentase,
                    $cabangId
                );
                $totalHarga = $basePrice + $tip;
            } else {
                $totalHarga = (int) ($request->total_harga ?? 0) + $tip;
            }

            $orderId = OrderHelper::generateOrderId($prefix);

            $transaksi = Transaksi::create([
                'order_id' => $orderId,
                'jenis' => $request->jenis,
                'voucher_id' => $voucher?->id,
                'jarak' => $request->jarak ?? 0,
                'titik_jemput' => $request->titik_jemput,
                'titik_tujuan' => $request->titik_tujuan,
                'cabang_id' => $cabangId,
                'tip' => $tip,
                'total_harga' => $totalHarga,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pesanan berhasil dibuat',
                'data' => [
                    'order_id' => $transaksi->order_id,
                    'jenis' => $transaksi->jenis,
                    'total_harga' => $transaksi->total_harga,
                    'tip' => $transaksi->tip,
                    'titik_jemput' => $transaksi->titik_jemput,
                    'titik_tujuan' => $transaksi->titik_tujuan,
                    'cabang_id' => $transaksi->cabang_id,
                    'status_transaksi' => $transaksi->status_transaksi ?? 'pending',
                ]
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('API Order Exception: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat pesanan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order details by Order ID.
     */
    public function show($orderId)
    {
        $transaksi = Transaksi::with(['cabang', 'voucher'])->where('order_id', $orderId)->first();

        if (!$transaksi) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'order_id' => $transaksi->order_id,
                'jenis' => $transaksi->jenis,
                'status_transaksi' => $transaksi->status_transaksi ?? 'pending',
                'titik_jemput' => $transaksi->titik_jemput,
                'titik_tujuan' => $transaksi->titik_tujuan,
                'jarak' => (float) $transaksi->jarak,
                'total_harga' => (int) $transaksi->total_harga,
                'tip' => (int) ($transaksi->tip ?? 0),
                'cabang' => $transaksi->cabang ? [
                    'id' => $transaksi->cabang->id,
                    'nama' => $transaksi->cabang->nama,
                ] : null,
                'created_at' => $transaksi->created_at?->toIso8601String(),
            ]
        ]);
    }

    /**
     * Transaction history with optional filters and branch scoping.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Transaksi::with(['cabang', 'voucher'])->latest();

        if ($user && $user->hasRole('Manager Cabang') && $user->cabang_id) {
            $query->where('cabang_id', $user->cabang_id);
        } elseif ($request->filled('cabang_id')) {
            $query->where('cabang_id', $request->cabang_id);
        }

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        $transaksis = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $transaksis
        ]);
    }
}
