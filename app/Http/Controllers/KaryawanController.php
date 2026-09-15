<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Contracts\Database\Eloquent\Builder;

class KaryawanController extends Controller
{
    public function index()
    {
        // $karyawanRole = Role::where('name', 'karyawan')->first();
        // $karyawan = User::role('karyawan')
        $karyawan = User::query()->with('media')->whereNot('name', 'Admin')->whereHas('roles', fn(Builder $query) => $query->where('name', 'karyawan'))
            ->get()
            ->map(function ($user) {
                // Calculate age from tanggal_lahir
                $age = isset($user->custom_fields['tanggal_lahir'])
                    ? Carbon::parse($user->custom_fields['tanggal_lahir'])->age
                    : null;
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->avatar_url,
                    'age' => $age,
                    'tipe_karyawan' => $user->tipe_karyawan ?? 'helpman',
                    'cabang' => $user->cabang->nama ?? null,
                ];
            });

        return view('pages.profile', compact('karyawan'));
    }
}
