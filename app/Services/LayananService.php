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
     * Get all active Layanan that have at least one available SubLayanan in the specified cabang.
     */
    public static function getAvailableLayanansForCabang(?int $cabangId = null): \Illuminate\Support\Collection
    {
        try {
            if (!$cabangId) {
                $cabangId = session('selected_cabang_id') ?? request('cabang_id') ?? Cabang::first()?->id;
            }

            return Layanan::with(['subLayanans' => function ($q) {
                    $q->where('is_active', true)->orderBy('urutan');
                }])
                ->where('is_active', true)
                ->orderBy('urutan')
                ->get()
                ->filter(function (Layanan $layanan) use ($cabangId) {
                    $availableSubs = $layanan->subLayanans->filter(function (SubLayanan $sub) use ($cabangId) {
                        return $sub->isTersediaForCabang($cabangId);
                    })->values();

                    $layanan->setRelation('subLayanans', $availableSubs);

                    return $availableSubs->isNotEmpty();
                })
                ->values();
        } catch (\Throwable $e) {
            Log::error("Error loading available layanans for cabang {$cabangId}: " . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get active Layanan by slug, attaching effective branch prices to its sub-services.
     */
    public static function getLayanan(string $slug, ?int $cabangId = null): ?Layanan
    {
        try {
            $cleanSlug = trim($slug, '/');
            $layanan = Layanan::with(['subLayanans' => function ($q) {
                $q->where('is_active', true)->orderBy('urutan');
            }])
            ->where(function ($query) use ($cleanSlug, $slug) {
                $query->where('slug', $cleanSlug)
                      ->orWhere('slug', '/' . $cleanSlug)
                      ->orWhere('slug', $slug);
            })
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
        try {
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

            // Map slug to enum-safe or string jenis
            $slugToJenis = [
                'bersih-bersih' => 'bersih-rumah',
                'pindahan' => 'angkutan',
                'daily' => 'daily-activity',
                'service' => 'all-service',
                'jasa-kustom' => 'custom',
                'bantuan-online' => 'bantuan-online',
                'jastip' => 'jastip',
                'jasa-nemenin' => 'jasa-nemenin',
                'travel' => 'travel',
                'editing' => 'editing',
                'joki-tugas' => 'joki-tugas',
                'teknisi' => 'teknisi',
                'spa' => 'spa',
                'penitipan' => 'penitipan',
            ];
            $jenis = $slugToJenis[$slug] ?? $slug;

            // Save Transaction with fail-safe handling
            try {
                Transaksi::create([
                    'order_id' => $orderId,
                    'jenis' => $jenis,
                    'jasa' => $serviceName,
                    'total_harga' => $rawPrice,
                    'cabang_id' => $cabang ? $cabang->id : null,
                ]);
            } catch (\Throwable $dbEx) {
                Log::error("Failed to insert transaksi for {$slug}: " . $dbEx->getMessage());
            }

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
        } catch (\Throwable $e) {
            Log::error("Critical error in processPesan for {$slug}: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memproses pesanan: ' . $e->getMessage(),
            ];
        }
    }
}
