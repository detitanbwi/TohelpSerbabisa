<?php

use App\Http\Controllers\BantuanController;
use App\Http\Controllers\BersihController;
use App\Http\Controllers\DailyActivityController;
use App\Http\Controllers\EditingController;
use App\Http\Controllers\JasaCustomController;
use App\Http\Controllers\JasaNemeninController;
use App\Http\Controllers\JastipController;
use App\Http\Controllers\JokiTugasController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\MobilController;
use App\Http\Controllers\OjekController;
use App\Http\Controllers\PindahanController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SpaController;
use App\Http\Controllers\TeknisiController;
use App\Http\Controllers\TravelController;
use App\Http\Controllers\VoucherController;
use App\Models\Testimoni;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pages.beranda.index', [
        'testimonis' => Testimoni::latest()->get()
    ]);
})->name('index');

Route::get('/profile', [KaryawanController::class, 'index'])->name('profile');

Route::post('/voucher/check', [VoucherController::class, 'check'])->name('voucher.check');

Route::post('/testimoni', function (Request $request) {
    $request->validate([
        'nama' => 'required|unique:testimonis,nama',
        'rating' => 'required|integer|min:1|max:5',
        'komentar' => 'required'
    ], [
        'nama.required' => 'Nama harus diisi',
        'nama.unique' => 'Nama sudah ada',
        'rating.required' => 'Rating harus diisi',
        'rating.integer' => 'Rating harus berupa angka',
        'rating.min' => 'Rating minimal 1',
        'rating.max' => 'Rating maksimal 5',
        'komentar.required' => 'Komentar harus diisi'
    ]);

    DB::beginTransaction();
    try {
        //code...
        Testimoni::create($request->all());
        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Testimoni berhasil ditambahkan'
        ]);
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => 'Terjadi kesalahan saat menambahkan testimoni'
        ], 500);
    }
})->name('testimoni');

Route::get('/ojek', [OjekController::class, 'index'])->name('ojek');
Route::post('/ojek/show-pricing', [OjekController::class, 'showPricing'])->name('ojek.show-pricing');
Route::post('/ojek/pesan', [OjekController::class, 'pesan'])->name('ojek.pesan');

Route::get('/mobil', [MobilController::class, 'index'])->name('taxi');
Route::post('/mobil/show-pricing', [MobilController::class, 'showPricing'])->name('taxi.show-pricing');
Route::post('/mobil/pesan', [MobilController::class, 'pesan'])->name('taxi.pesan');

Route::get('/bersih-bersih', [BersihController::class, 'index'])->name('bersih');
Route::post('/bersih-bersih/pesan', [BersihController::class, 'pesan'])->name('bersih.pesan');

Route::get('/pindahan', [PindahanController::class, 'index'])->name('pindahan');
Route::post('/pindahan/pesan', [PindahanController::class, 'pesan'])->name('pindahan.pesan');

Route::get('/bantuan-online', [BantuanController::class, 'index'])->name('bantuan');
Route::post('/bantuan-online/pesan', [BantuanController::class, 'pesan'])->name('bantuan.pesan');

Route::get('/jastip', [JastipController::class, 'index'])->name('jastip');
Route::post('/jastip/pesan', [JastipController::class, 'pesan'])->name('jastip.pesan');

Route::get('/daily', [DailyActivityController::class, 'index'])->name('daily');
Route::post('/daily/pesan', [DailyActivityController::class, 'pesan'])->name('daily.pesan');

Route::get('/jasa-nemenin', [JasaNemeninController::class, 'index'])->name('nemenin');
Route::post('/jasa-nemenin/pesan', [JasaNemeninController::class, 'pesan'])->name('nemenin.pesan');

Route::get('/service', [ServiceController::class, 'index'])->name('service');
Route::post('/service/pesan', [ServiceController::class, 'pesan'])->name('service.pesan');

Route::get('/travel', [TravelController::class, 'index'])->name('travel');
Route::post('/travel/pesan', [TravelController::class, 'pesan'])->name('travel.pesan');

Route::get('/editing', [EditingController::class, 'index'])->name('editing');
Route::post('/editing/pesan', [EditingController::class, 'pesan'])->name('editing.pesan');

Route::get('/joki-tugas', [JokiTugasController::class, 'index'])->name('joki-tugas');
Route::post('/joki-tugas/pesan', [JokiTugasController::class, 'pesan'])->name('joki-tugas.pesan');

Route::get('/teknisi', [TeknisiController::class, 'index'])->name('teknisi');
Route::post('/teknisi/pesan', [TeknisiController::class, 'pesan'])->name('teknisi.pesan');

Route::get('/penitipan', [SpaController::class, 'index'])->name('penitipan');
Route::post('/penitipan/pesan', [SpaController::class, 'pesan'])->name('penitipan.pesan');

Route::get('/jasa-kustom', [JasaCustomController::class, 'index'])->name('kustom');
Route::post('/jasa-kustom/pesan', [JasaCustomController::class, 'pesan'])->name('kustom.pesan');
