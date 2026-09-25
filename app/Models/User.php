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
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasAvatar, FilamentUser, HasMedia, Wallet
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles, InteractsWithMedia, HasWallet;

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
                if ($user->email === 'admin@gmail.com' || (method_exists($user, 'hasRole') && $user->hasRole('super_admin'))) {
                    return;
                }

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

    /**
     * Get all branch IDs associated with this user (either assigned as staff or as manager).
     *
     * @return array<int>
     */
    public function getCabangIds(): array
    {
        $cabangIds = Cabang::where('manager_id', $this->id)
            ->when($this->cabang_id, fn ($q) => $q->orWhere('id', $this->cabang_id))
            ->pluck('id')
            ->toArray();

        if ($this->cabang_id && ! in_array($this->cabang_id, $cabangIds)) {
            $cabangIds[] = $this->cabang_id;
        }

        return array_values(array_unique(array_filter($cabangIds)));
    }

    /**
     * Determine if this user is a branch manager for the given branch ID.
     */
    public function managesCabang(?int $cabangId): bool
    {
        if (! $cabangId) {
            return false;
        }

        return in_array($cabangId, $this->getCabangIds());
    }
}
