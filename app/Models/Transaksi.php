<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    protected $guarded = ['id'];

    public function tugas()
    {
        return $this->belongsToMany(User::class, 'karyawan_tugas', 'tugas_id', 'karyawan_id')
            ->withPivot(['id', 'is_selesai'])
            ->withTimestamps();
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function karyawanTugas()
    {
        return $this->hasMany(KaryawanTugas::class, 'tugas_id');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }
}
