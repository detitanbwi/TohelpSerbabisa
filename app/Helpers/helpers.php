<?php 

use App\Models\Cabang;
use App\Models\Layanan;

function getPricing(string $tipe, $jarakBaseCampKeTitikJemput, $jarakTitikJemputKeTitikTujuan, $jarakBaseCampKeTitikTujuan, $jarakTitikTujuanKeTitikJemput, $discount = null, $cabangId = null)
{
    $tipeNormalized = ucfirst(strtolower($tipe));
    $isMotor = ($tipeNormalized === 'Motor');
    $layananSlug = $isMotor ? 'ojek' : 'mobil';

    // Global default from Master Layanan
    $masterLayanan = Layanan::where('slug', $layananSlug)->first();

    // Dynamic branch configuration
    $cabang = $cabangId ? Cabang::find($cabangId) : null;
    $freeDistance = (float) (($cabang?->free_distance_km > 0 ? $cabang->free_distance_km : null) ?? ($masterLayanan?->free_distance_km ?? 3.0));

    // Determine surcharge per KM outside free distance
    if ($isMotor) {
        $tarifDasarHarga = ($cabang?->ojek_surcharge_per_km > 0 ? $cabang->ojek_surcharge_per_km : null) 
            ?? ($masterLayanan?->surcharge_per_km ?? 1000);
    } else {
        $tarifDasarHarga = ($cabang?->taxi_surcharge_per_km > 0 ? $cabang->taxi_surcharge_per_km : null) 
            ?? ($masterLayanan?->surcharge_per_km ?? 2000);
    }
    
    // Rata-rata jarak basecamp ke kedua titik untuk simetri harga
    $avgJarakBaseCampKeTitik = ($jarakBaseCampKeTitikJemput + $jarakBaseCampKeTitikTujuan) / 2;
    $pickupSurcharge = 0;
    if ($avgJarakBaseCampKeTitik > $freeDistance) {
        $pickupSurcharge = ($avgJarakBaseCampKeTitik - $freeDistance) * $tarifDasarHarga;
    }

    // Hitung tarif perjalanan berdasarkan jarak trip
    $avgTripDistance = ($jarakTitikJemputKeTitikTujuan + $jarakTitikTujuanKeTitikJemput) / 2;

    if ($isMotor) {
        $minFare = (int) (($cabang?->ojek_tarif_minimum > 0 ? $cabang->ojek_tarif_minimum : null) ?? ($masterLayanan?->tarif_minimum ?? 7000));
        $perKmFare = (int) (($cabang?->ojek_tarif_per_km > 0 ? $cabang->ojek_tarif_per_km : null) ?? ($masterLayanan?->tarif_per_km ?? 2000));
        $baseTrip = max($minFare, $avgTripDistance * $perKmFare);
        $harga = $pickupSurcharge + $baseTrip;
    } else {
        // Mobil / Taxi
        $minFare = (int) (($cabang?->taxi_tarif_minimum > 0 ? $cabang->taxi_tarif_minimum : null) ?? ($masterLayanan?->tarif_minimum ?? 18000));
        $perKmFare = (int) (($cabang?->taxi_tarif_per_km > 0 ? $cabang->taxi_tarif_per_km : null) ?? ($masterLayanan?->tarif_per_km ?? 5000));
        $perKmLanjutanFare = (int) (($cabang?->taxi_tarif_per_km_lanjutan > 0 ? $cabang->taxi_tarif_per_km_lanjutan : null) ?? ($masterLayanan?->tarif_per_km_lanjutan ?? 4000));

        if ($avgTripDistance <= 3) {
            $baseTrip = $minFare;
        } elseif ($avgTripDistance <= 10) {
            $baseTrip = $minFare + (($avgTripDistance - 3) * $perKmFare);
        } else {
            $baseTrip = $minFare + (7 * $perKmFare) + (($avgTripDistance - 10) * $perKmLanjutanFare);
        }
        $harga = $pickupSurcharge + $baseTrip;
    }

    $harga = (int) round($harga, -3, PHP_ROUND_HALF_UP);

    if ($discount && is_numeric($discount)) {
        $harga -= (int) round($harga * ($discount / 100));
    }

    return max(0, $harga);
}