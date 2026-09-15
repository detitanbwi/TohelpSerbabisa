<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cabang extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saved(function (Cabang $cabang) {
            $oldManagerId = $cabang->getOriginal('manager_id');
            $newManagerId = $cabang->manager_id;

            // 1. If old manager was changed/removed, revert old manager's role to 'karyawan'
            if ($oldManagerId && $oldManagerId != $newManagerId) {
                $oldManager = User::find($oldManagerId);
                if ($oldManager && ! $oldManager->hasRole('super_admin')) {
                    $otherManaged = Cabang::where('manager_id', $oldManagerId)->where('id', '!=', $cabang->id)->exists();
                    if (! $otherManaged) {
                        $oldManager->syncRoles(['karyawan']);
                    }
                }
            }

            // 2. If new manager is assigned
            if ($newManagerId) {
                // Ensure 1 User = 1 Cabang (detach from any other cabang)
                Cabang::where('manager_id', $newManagerId)
                    ->where('id', '!=', $cabang->id)
                    ->update(['manager_id' => null]);

                $newManager = User::find($newManagerId);
                if ($newManager) {
                    // Update user's cabang_id to this cabang
                    $newManager->update(['cabang_id' => $cabang->id]);
                    // Ensure role is manager_cabang
                    if (! $newManager->hasRole('super_admin')) {
                        $newManager->syncRoles(['manager_cabang']);
                    }
                }
            }
        });

        static::deleting(function (Cabang $cabang) {
            if ($cabang->manager_id) {
                $manager = User::find($cabang->manager_id);
                if ($manager && ! $manager->hasRole('super_admin')) {
                    $manager->syncRoles(['karyawan']);
                }
            }
        });
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'cabang_id');
    }

    public function managers(): HasMany
    {
        return $this->hasMany(User::class, 'cabang_id')->whereHas('roles', function ($query) {
            $query->where('name', 'manager_cabang');
        });
    }

    public function helpmans(): HasMany
    {
        return $this->hasMany(User::class, 'cabang_id')->whereHas('roles', function ($query) {
            $query->where('name', 'karyawan');
        });
    }

    public function visibleHelpmans(): HasMany
    {
        return $this->hasMany(User::class, 'cabang_id')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'karyawan');
            })
            ->where('is_visible', true);
    }

    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'cabang_id');
    }
}
