<?php 

use App\Models\Cabang;
use App\Models\TarifDasar;
use App\Models\TarifJarak;

function getPricing(string $tipe, $jarakBaseCampKeTitikJemput, $jarakTitikJemputKeTitikTujuan, $jarakBaseCampKeTitikTujuan, $jarakTitikTujuanKeTitikJemput, $discount = null, $cabangId = null)
{
    $tipeNormalized = ucfirst(strtolower($tipe));
    $tarifDasar = TarifDasar::whereJenis($tipeNormalized)->first();
    $tarifDasarHarga = $tarifDasar ? $tarifDasar->harga : ($tipeNormalized === 'Motor' ? 1000 : 2000);
    
    // Dynamic free distance per cabang (default 3.0 km)
    $freeDistance = 3.0;
    if ($cabangId) {
        $cabang = Cabang::find($cabangId);
        if ($cabang && isset($cabang->free_distance_km)) {
            $freeDistance = (float) $cabang->free_distance_km;
        }
    }
    
    // Rata-rata jarak basecamp ke kedua titik untuk simetri harga
    $avgJarakBaseCampKeTitik = ($jarakBaseCampKeTitikJemput + $jarakBaseCampKeTitikTujuan) / 2;
    $pickupSurcharge = 0;
    if ($avgJarakBaseCampKeTitik > $freeDistance) {
        $pickupSurcharge = ($avgJarakBaseCampKeTitik - $freeDistance) * $tarifDasarHarga;
    }

    // Cek apakah ada konfigurasi tarif jarak di database
    $firstRange = TarifJarak::whereJenis($tipeNormalized)
        ->orderBy('jarak_min', 'asc')
        ->first();
    
    $harga = 0;

    if ($firstRange) {
        $firstRangeMax = $firstRange->jarak_max ?? ($firstRange->jarak_min + 3);

        // Tarif untuk perjalanan pergi (A ke B)
        $tarifJarak = TarifJarak::whereJenis($tipeNormalized)
            ->where(function($query) use ($jarakTitikJemputKeTitikTujuan) {
                $query->where('jarak_min', '<=', $jarakTitikJemputKeTitikTujuan)
                      ->where(function($q) use ($jarakTitikJemputKeTitikTujuan) {
                          $q->where('jarak_max', '>=', $jarakTitikJemputKeTitikTujuan)
                            ->orWhereNull('jarak_max');
                      });
            })
            ->first() ?? $firstRange;
        
        // Tarif untuk perjalanan pulang (B ke A)
        $tarifJarakKembali = TarifJarak::whereJenis($tipeNormalized)
            ->where(function($query) use ($jarakTitikTujuanKeTitikJemput) {
                $query->where('jarak_min', '<=', $jarakTitikTujuanKeTitikJemput)
                      ->where(function($q) use ($jarakTitikTujuanKeTitikJemput) {
                          $q->where('jarak_max', '>=', $jarakTitikTujuanKeTitikJemput)
                            ->orWhereNull('jarak_max');
                      });
            })
            ->first() ?? $firstRange;

        $hargaAkeB = $pickupSurcharge + (($jarakTitikJemputKeTitikTujuan > $firstRangeMax) ? ($jarakTitikJemputKeTitikTujuan * $tarifJarak->harga) : $tarifJarak->harga);
        $hargaBkeA = $pickupSurcharge + (($jarakTitikTujuanKeTitikJemput > $firstRangeMax) ? ($jarakTitikTujuanKeTitikJemput * $tarifJarakKembali->harga) : $tarifJarakKembali->harga);
        
        $harga = ($hargaAkeB + $hargaBkeA) / 2;
    } else {
        // Fallback default pricing model jika tabel tarif_jarak kosong
        $avgTripDistance = ($jarakTitikJemputKeTitikTujuan + $jarakTitikTujuanKeTitikJemput) / 2;
        if ($tipeNormalized === 'Motor') {
            $baseTrip = max(7000, $avgTripDistance * 2000);
            $harga = $pickupSurcharge + $baseTrip;
        } else {
            // Mobil
            if ($avgTripDistance <= 3) {
                $baseTrip = 18000;
            } elseif ($avgTripDistance <= 10) {
                $baseTrip = 18000 + (($avgTripDistance - 3) * 5000);
            } else {
                $baseTrip = 18000 + (7 * 5000) + (($avgTripDistance - 10) * 4000);
            }
            $harga = $pickupSurcharge + $baseTrip;
        }
    }

    $harga = (int) round($harga, -3, PHP_ROUND_HALF_UP);

    if ($discount && is_numeric($discount)) {
        $harga -= (int) round($harga * ($discount / 100));
    }

    return max(0, $harga);
}