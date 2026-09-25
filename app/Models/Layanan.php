<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Layanan extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'urutan' => 'integer',
        'is_transportasi' => 'boolean',
        'tarif_minimum' => 'integer',
        'tarif_per_km' => 'integer',
        'tarif_per_km_lanjutan' => 'integer',
        'surcharge_per_km' => 'integer',
        'free_distance_km' => 'float',
    ];

    /**
     * Check if this service is distance-based transportation (Ojek, Taxi, etc.).
     */
    public function isTransportasi(): bool
    {
        return $this->is_transportasi || in_array($this->clean_slug, ['ojek', 'mobil', 'taxi']);
    }

    public function subLayanans(): HasMany
    {
        return $this->hasMany(SubLayanan::class, 'layanan_id')->orderBy('urutan');
    }

    public function activeSubLayanans(): HasMany
    {
        return $this->hasMany(SubLayanan::class, 'layanan_id')
            ->where('is_active', true)
            ->orderBy('urutan');
    }

    /**
     * Default WhatsApp Message template if none defined.
     */
    public function getWaTemplateOrDefaultAttribute(): string
    {
        if (!empty($this->wa_template)) {
            return $this->wa_template;
        }

        return "Hii kak, saya ingin meminta bantuan To Help\n\n" .
               "ID Order : {order_id}\n" .
               "Jenis Jasa : {layanan}\n" .
               "Tipe Jasa : {sub_layanan}\n" .
               "Hari/Tanggal : \n" .
               "Lokasi : \n" .
               "Nama : \n" .
               "Nomor WhatsApp : ";
    }

    /**
     * Build formatted WhatsApp message with placeholder replacement.
     */
    public function formatWaMessage(array $params): string
    {
        $template = $this->wa_template_or_default;

        $search = [
            '{order_id}',
            '{layanan}',
            '{sub_layanan}',
            '{harga}',
            '{satuan}',
            '{cabang}',
        ];

        $replace = [
            $params['order_id'] ?? '-',
            $params['layanan'] ?? $this->nama,
            $params['sub_layanan'] ?? '-',
            $params['harga'] ?? '-',
            $params['satuan'] ?? '',
            $params['cabang'] ?? '-',
        ];

        return str_replace($search, $replace, $template);
    }

    /**
     * Ensure slug attribute is always cleanly formatted without leading/trailing slashes.
     */
    public function setSlugAttribute($value): void
    {
        $this->attributes['slug'] = trim((string) $value, '/');
    }

    /**
     * Get clean slug without slashes.
     */
    public function getCleanSlugAttribute(): string
    {
        return trim((string) $this->slug, '/');
    }

    /**
     * Resolve target route URL for this service.
     */
    public function getRouteUrlAttribute(): string
    {
        $slug = $this->clean_slug;

        $namedRoutes = [
            'ojek' => 'ojek',
            'mobil' => 'taxi',
            'taxi' => 'taxi',
            'bersih-bersih' => 'bersih',
            'bersih' => 'bersih',
            'pindahan' => 'pindahan',
            'bantuan-online' => 'bantuan',
            'bantuan' => 'bantuan',
            'jastip' => 'jastip',
            'daily' => 'daily',
            'daily-activity' => 'daily',
            'jasa-nemenin' => 'nemenin',
            'nemenin' => 'nemenin',
            'service' => 'service',
            'all-service' => 'service',
            'travel' => 'travel',
            'editing' => 'editing',
            'joki-tugas' => 'joki-tugas',
            'teknisi' => 'teknisi',
            'penitipan' => 'penitipan',
            'penitipan-barang' => 'penitipan',
            'spa' => 'penitipan',
            'jasa-kustom' => 'kustom',
            'custom' => 'kustom',
        ];

        if (isset($namedRoutes[$slug]) && \Illuminate\Support\Facades\Route::has($namedRoutes[$slug])) {
            return route($namedRoutes[$slug]);
        }

        if (\Illuminate\Support\Facades\Route::has('layanan.show')) {
            return route('layanan.show', ['slug' => $slug]);
        }

        return '#' . $slug;
    }

    /**
     * Get representative icon class (FontAwesome).
     */
    public function getIconClassAttribute(): string
    {
        if (!empty($this->icon_or_image)) {
            if (str_starts_with($this->icon_or_image, 'fa')) {
                return $this->icon_or_image;
            }
        }

        $slug = $this->clean_slug;
        $iconMap = [
            'ojek' => 'fas fa-motorcycle',
            'mobil' => 'fas fa-taxi',
            'taxi' => 'fas fa-taxi',
            'pindahan' => 'fas fa-truck',
            'bersih-bersih' => 'fas fa-broom',
            'bersih' => 'fas fa-broom',
            'jastip' => 'fas fa-shopping-bag',
            'daily' => 'fas fa-tasks',
            'daily-activity' => 'fas fa-tasks',
            'jasa-nemenin' => 'fas fa-users',
            'nemenin' => 'fas fa-users',
            'laundry' => 'fas fa-tshirt',
            'service' => 'fas fa-tools',
            'all-service' => 'fas fa-tools',
            'travel' => 'fas fa-car',
            'pariwisata' => 'fas fa-map-marked-alt',
            'editing' => 'fas fa-camera',
            'bantuan-online' => 'fas fa-headset',
            'bantuan' => 'fas fa-headset',
            'joki-tugas' => 'fas fa-book',
            'teknisi' => 'fas fa-wrench',
            'penitipan' => 'fas fa-boxes',
            'penitipan-barang' => 'fas fa-boxes',
            'spa' => 'fas fa-spa',
            'jasa-it' => 'fas fa-laptop',
            'it' => 'fas fa-laptop',
            'jasa-kustom' => 'fas fa-hand-holding-heart',
            'custom' => 'fas fa-hand-holding-heart',
        ];

        return $iconMap[$slug] ?? 'fas fa-concierge-bell';
    }

    /**
     * Get representative brand color hex for cards.
     */
    public function getColorHexAttribute(): string
    {
        $slug = $this->clean_slug;
        $colorMap = [
            'ojek' => '#FF5733',
            'mobil' => '#2E86C1',
            'taxi' => '#2E86C1',
            'pindahan' => '#F4D03F',
            'bersih-bersih' => '#16A085',
            'bersih' => '#16A085',
            'jastip' => '#E74C3C',
            'daily' => '#9B59B6',
            'daily-activity' => '#9B59B6',
            'jasa-nemenin' => '#F39C12',
            'nemenin' => '#F39C12',
            'laundry' => '#3498DB',
            'service' => '#34495E',
            'all-service' => '#34495E',
            'travel' => '#1ABC9C',
            'pariwisata' => '#00b894',
            'editing' => '#E84393',
            'bantuan-online' => '#27AE60',
            'bantuan' => '#27AE60',
            'joki-tugas' => '#8E44AD',
            'teknisi' => '#F1C40F',
            'penitipan' => '#3498DB',
            'penitipan-barang' => '#3498DB',
            'spa' => '#fd79a8',
            'jasa-it' => '#E74C3C',
            'it' => '#E74C3C',
            'jasa-kustom' => '#e17055',
            'custom' => '#e17055',
        ];

        if (isset($colorMap[$slug])) {
            return $colorMap[$slug];
        }

        $palette = ['#2E86C1', '#16A085', '#E74C3C', '#9B59B6', '#F39C12', '#1ABC9C', '#E84393', '#27AE60'];
        return $palette[($this->id ?? 0) % count($palette)];
    }
}
