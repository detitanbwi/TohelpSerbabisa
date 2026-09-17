<?php

use App\Http\Controllers\Api\v1\AbsensiApiController;
use App\Http\Controllers\Api\v1\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Mobile Worker Application (Auth, Profil & Absensi)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Public Auth
    Route::post('/auth/login', [AuthController::class, 'login'])->name('api.v1.auth.login');

    // Authenticated Endpoints (Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        
        // Auth & Profil Pengguna
        Route::get('/auth/me', [AuthController::class, 'me'])->name('api.v1.auth.me');
        Route::get('/profile', [AuthController::class, 'me'])->name('api.v1.profile.show');
        Route::post('/profile/update', [AuthController::class, 'updateProfile'])->name('api.v1.profile.update');
        Route::post('/profile/change-password', [AuthController::class, 'changePassword'])->name('api.v1.profile.change_password');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');

        // Absensi Karyawan / Helpman
        Route::get('/absensi/today', [AbsensiApiController::class, 'today'])->name('api.v1.absensi.today');
        Route::post('/absensi/check-in', [AbsensiApiController::class, 'checkIn'])->name('api.v1.absensi.checkin');
        Route::get('/absensi/history', [AbsensiApiController::class, 'history'])->name('api.v1.absensi.history');
    });
});
