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
    ];

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
}
