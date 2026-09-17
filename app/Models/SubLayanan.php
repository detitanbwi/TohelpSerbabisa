<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SubLayanan extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'default_harga' => 'decimal:2',
        'is_active' => 'boolean',
        'urutan' => 'integer',
    ];

    public function layanan(): BelongsTo
    {
        return $this->belongsTo(Layanan::class, 'layanan_id');
    }

    public function cabangLayanans(): HasMany
    {
        return $this->hasMany(CabangLayanan::class, 'sub_layanan_id');
    }

    public function cabangs(): BelongsToMany
    {
        return $this->belongsToMany(Cabang::class, 'cabang_layanan', 'sub_layanan_id', 'cabang_id')
            ->withPivot(['is_tersedia', 'custom_harga', 'custom_satuan', 'custom_label', 'custom_catatan_nb'])
            ->withTimestamps();
    }

    /**
     * Check if available for a given cabang (default true if not configured in pivot).
     */
    public function isTersediaForCabang(?int $cabangId): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$cabangId) {
            return true;
        }

        $pivot = $this->cabangLayanans()->where('cabang_id', $cabangId)->first();
        if ($pivot) {
            return (bool) $pivot->is_tersedia;
        }

        return true;
    }

    /**
     * Get effective price for a given cabang.
     */
    public function getHargaForCabang(?int $cabangId): float
    {
        if ($cabangId) {
            $pivot = $this->cabangLayanans()->where('cabang_id', $cabangId)->first();
            if ($pivot && $pivot->custom_harga !== null) {
                return (float) $pivot->custom_harga;
            }
        }

        return (float) $this->default_harga;
    }

    /**
     * Get effective satuan for a given cabang.
     */
    public function getSatuanForCabang(?int $cabangId): ?string
    {
        if ($cabangId) {
            $pivot = $this->cabangLayanans()->where('cabang_id', $cabangId)->first();
            if ($pivot && !empty($pivot->custom_satuan)) {
                return $pivot->custom_satuan;
            }
        }

        return $this->default_satuan;
    }

    /**
     * Get effective custom label (e.g. 'Start from') for a given cabang.
     */
    public function getLabelForCabang(?int $cabangId): ?string
    {
        if ($cabangId) {
            $pivot = $this->cabangLayanans()->where('cabang_id', $cabangId)->first();
            if ($pivot && !empty($pivot->custom_label)) {
                return $pivot->custom_label;
            }
        }

        return $this->label_harga_custom;
    }

    /**
     * Get effective catatan/NB for a given cabang.
     */
    public function getCatatanNbForCabang(?int $cabangId): ?string
    {
        if ($cabangId) {
            $pivot = $this->cabangLayanans()->where('cabang_id', $cabangId)->first();
            if ($pivot && !empty($pivot->custom_catatan_nb)) {
                return $pivot->custom_catatan_nb;
            }
        }

        return $this->catatan_nb;
    }

    /**
     * Formatted display price string e.g. "Start from Rp 30.000 / foto"
     */
    public function getFormattedDisplayPrice(?int $cabangId = null): string
    {
        $label = $this->getLabelForCabang($cabangId);
        $harga = $this->getHargaForCabang($cabangId);
        $satuan = $this->getSatuanForCabang($cabangId);

        $formatted = 'Rp ' . number_format($harga, 0, ',', '.');
        if ($label) {
            $formatted = $label . ' ' . $formatted;
        }
        if ($satuan) {
            $formatted .= ' ' . $satuan;
        }

        return $formatted;
    }
}
