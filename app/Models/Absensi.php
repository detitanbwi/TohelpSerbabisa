<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Absensi extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = ['id'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('bukti-absensi')
            ->singleFile();
    }

    public function karyawan()
    {
        return $this->belongsTo(User::class, 'karyawan_id');
    }
}
