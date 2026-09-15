<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Bavix\Wallet\Interfaces\Wallet;
use Bavix\Wallet\Traits\HasWallet;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasAvatar, FilamentUser, HasMedia, Wallet
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, InteractsWithMedia, HasWallet;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'cabang_id',
        'is_visible',
        'tipe_karyawan',
        'avatar_url',
        'custom_fields',
    ];

    protected static function booted(): void
    {
        static::creating(function ($user) {
            if (empty($user->cabang_id)) {
                $randomCabangId = Cabang::inRandomOrder()->value('id') ?? Cabang::value('id');
                if ($randomCabangId) {
                    $user->cabang_id = $randomCabangId;
                }
            }
        });
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function managedCabang()
    {
        return $this->hasOne(Cabang::class, 'manager_id');
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? Storage::url("$this->avatar_url") : null;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'custom_fields' => 'json',
            'is_visible' => 'boolean',
        ];
    }

    public function tugas()
    {
        return $this->belongsToMany(Transaksi::class, 'karyawan_tugas', 'karyawan_id', 'tugas_id')->withPivot('id');
    }

    public function karyawanTugas()
    {
        return $this->hasMany(KaryawanTugas::class, 'karyawan_id');
    }

    public function absensi()
    {
        return $this->hasMany(Absensi::class, 'karyawan_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if($panel->getId() == 'admin')
        {
            return $this->hasAnyRole(['super_admin', 'manager_cabang']);
        }
        if($panel->getId() == 'karyawan')
        {
            return $this->hasRole('karyawan');
        }

        return false;
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }
}
