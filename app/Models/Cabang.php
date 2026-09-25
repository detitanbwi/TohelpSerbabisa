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
            try {
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
                        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'manager_cabang', 'guard_name' => 'web']);
                        if (! $newManager->hasRole('super_admin')) {
                            $newManager->syncRoles(['manager_cabang']);
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Cabang manager sync: ' . $e->getMessage());
            }
        });

        static::deleting(function (Cabang $cabang) {
            try {
                if ($cabang->manager_id) {
                    $manager = User::find($cabang->manager_id);
                    if ($manager && ! $manager->hasRole('super_admin')) {
                        $manager->syncRoles(['karyawan']);
                    }
                }

                // Reassign users to another available cabang so total personil is preserved
                $fallbackCabangId = Cabang::where('id', '!=', $cabang->id)->value('id');
                if ($fallbackCabangId) {
                    User::where('cabang_id', $cabang->id)->update(['cabang_id' => $fallbackCabangId]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Cabang delete cleanup: ' . $e->getMessage());
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

    /**
     * Get clean international format for WhatsApp (e.g. 6285695908981)
     */
    public function getFormattedNoWaAttribute(): string
    {
        $raw = $this->no_wa ?: '6285695908981';
        $digits = preg_replace('/[^0-9]/', '', $raw);

        if (str_starts_with($digits, '08')) {
            $digits = '628' . substr($digits, 2);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '628' . substr($digits, 1);
        }

        return $digits ?: '6285695908981';
    }

    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'cabang_id');
    }

    public function cabangLayanans(): HasMany
    {
        return $this->hasMany(CabangLayanan::class, 'cabang_id');
    }

    public function subLayanans(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(SubLayanan::class, 'cabang_layanan', 'cabang_id', 'sub_layanan_id')
            ->withPivot(['is_tersedia', 'custom_harga', 'custom_satuan', 'custom_label', 'custom_catatan_nb'])
            ->withTimestamps();
    }
}
