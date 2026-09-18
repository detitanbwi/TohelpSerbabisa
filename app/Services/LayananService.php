<?php

namespace App\Services;

use App\Helpers\OrderHelper;
use App\Models\Cabang;
use App\Models\Layanan;
use App\Models\SubLayanan;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LayananService
{
    /**
     * Get active Layanan by slug, attaching effective branch prices to its sub-services.
     */
    public static function getLayanan(string $slug, ?int $cabangId = null): ?Layanan
    {
        try {
            $layanan = Layanan::with(['subLayanans' => function ($q) {
                $q->where('is_active', true)->orderBy('urutan');
            }])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

            if (!$layanan) {
                return null;
            }

            // Filter and enrich sub-services based on cabang availability
            $filteredSub = $layanan->subLayanans->filter(function (SubLayanan $sub) use ($cabangId) {
                return $sub->isTersediaForCabang($cabangId);
            })->values();

            $layanan->setRelation('subLayanans', $filteredSub);

            return $layanan;
        } catch (\Throwable $e) {
            Log::error("Error loading layanan {$slug}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Process order and prepare dynamic WhatsApp message.
     */
    public static function processPesan(Request $request, string $slug, ?int $cabangId = null): array
    {
        $layanan = Layanan::where('slug', $slug)->first();
        $prefix = $layanan ? ($layanan->kode_layanan ?: 'ORD-') : 'ORD-';

        $cabang = $cabangId ? Cabang::find($cabangId) : (Cabang::first());
        $cabangNama = $cabang ? $cabang->nama : 'Pusat';
        $cabangNoWa = $cabang ? $cabang->formatted_no_wa : '6285695908981';

        $orderId = OrderHelper::generateOrderId($prefix);

        $serviceName = $request->jasa ?: ($layanan ? $layanan->nama : 'Layanan');
        $rawPrice = $request->total_harga ?: 0;
        if (is_string($rawPrice)) {
            $rawPrice = (int) preg_replace('/\D/', '', $rawPrice);
        }

        // Save Transaction
        Transaksi::create([
            'order_id' => $orderId,
            'jenis' => $slug,
            'jasa' => $serviceName,
            'total_harga' => $rawPrice,
            'cabang_id' => $cabang ? $cabang->id : null,
        ]);

        // Build WhatsApp text
        $waMessage = $layanan ? $layanan->formatWaMessage([
            'order_id' => $orderId,
            'layanan' => $layanan->nama,
            'sub_layanan' => $serviceName,
            'harga' => 'Rp ' . number_format($rawPrice, 0, ',', '.'),
            'satuan' => $request->satuan ?? '',
            'cabang' => $cabangNama,
        ]) : "Hii kak, saya ingin memesan layanan {$serviceName}\nID Order: {$orderId}";

        return [
            'status' => 'success',
            'order_id' => $orderId,
            'wa_message' => $waMessage,
            'wa_number' => $cabangNoWa,
        ];
    }
}
