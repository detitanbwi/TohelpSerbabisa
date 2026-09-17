<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CabangLayanan extends Model
{
    protected $table = 'cabang_layanan';

    protected $guarded = ['id'];

    protected $casts = [
        'is_tersedia' => 'boolean',
        'custom_harga' => 'decimal:2',
    ];

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }

    public function subLayanan(): BelongsTo
    {
        return $this->belongsTo(SubLayanan::class, 'sub_layanan_id');
    }
}
