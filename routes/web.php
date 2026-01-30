<?php

use App\Models\Peserta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\String\TruncateMode;
use App\Http\Controllers\PesertaController;
use App\Http\Controllers\BagianController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MentorController;
use App\Http\Controllers\LaporanHarianController;
use App\Http\Controllers\PenugasanController;
use App\Http\Controllers\LaporanAkhirController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RepositoryController;

Route::get('/', function () {
    return view('auth.login');
});


Auth::routes();

// Dashboard routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/home', [DashboardController::class, 'index'])->name('home');
});

Route::fallback(function () {
    return "gagal memuat rute yang diminta";
});

// mentor - Hanya Admin dan Mentor yang bisa akses
Route::resource('mentor', MentorController::class)->middleware(['auth', 'role:Admin,Mentor']);

// peserta - Hanya Admin dan Mentor yang bisa akses (Peserta tidak boleh akses halaman manajemen peserta)
Route::resource('peserta', PesertaController::class)->middleware(['auth', 'role:Admin,Mentor']);
Route::get('/api/mentors/by-bagian/{bagianId}', [MentorController::class, 'getMentorsByBagian'])->name('api.mentors.by_bagian');

// laporan_harian - Semua role bisa akses
Route::resource('laporan_harian', LaporanHarianController::class)->middleware('auth');
Route::get('/laporan_harian/create/{penugasan_id?}', [LaporanHarianController::class, 'create'])->name('laporan_harian.create')->middleware('auth');

// penugasan - Semua role bisa akses (ada pengecekan di controller)
Route::resource('penugasans', PenugasanController::class)->middleware('auth');
Route::put('/penugasan/{id}/status', [PenugasanController::class, 'updateStatus'])->middleware('auth');
// Route::put('/penugasans/{id}/nilai_kualitas', [PenugasanController::class, 'updateNilaiKualitas'])->name('penugasans.updateNilaiKualitas');
Route::put('/penugasans/{id}/kualitas', [PenugasanController::class, 'updateKualitas'])->name('penugasans.updateKualitas')->middleware('auth');
Route::put('/penugasan/{id}/status', [PenugasanController::class, 'updateStatus'])->name('penugasan.update-status')->middleware('auth');
// Tambahkan route untuk update approve - Hanya Admin dan Mentor
Route::put('/penugasan/{id}/approve', [PenugasanController::class, 'updateApprove'])->name('penugasan.updateApprove')->middleware(['auth', 'role:Admin,Mentor']);
// Tambahkan route untuk update feedback - Hanya Admin dan Mentor
Route::put('/penugasan/{id}/feedback', [PenugasanController::class, 'updateFeedback'])->name('penugasan.updateFeedback')->middleware(['auth', 'role:Admin,Mentor']);


//laporan akhir - Semua role bisa akses
Route::resource('laporan-akhir', LaporanAkhirController::class)->middleware('auth');
// Route tambahan untuk update status (hanya untuk admin/mentor)
Route::patch('/laporan-akhir/{laporanAkhir}/status', [LaporanAkhirController::class, 'updateStatus'])
    ->name('laporan-akhir.updateStatus')->middleware(['auth', 'role:Admin,Mentor']);

// Repository - Semua role bisa akses
Route::resource('repository', RepositoryController::class)->middleware('auth');
// Route tambahan untuk publish/unpublish (hanya untuk admin)
Route::patch('/repository/{id}/publish', [RepositoryController::class, 'publish'])
    ->name('repository.publish')
    ->middleware('isAdmin');
Route::patch('/repository/{id}/unpublish', [RepositoryController::class, 'unpublish'])
    ->name('repository.unpublish')
    ->middleware('isAdmin');

// users - Hanya Admin
Route::resource('users', UserController::class)->middleware('isAdmin');
Route::post('users-update-role', [UserController::class, 'updateRole'])->name('users.update-role')->middleware('isAdmin');
// bagian - Hanya Admin dan Mentor
Route::resource('bagian', BagianController::class)->middleware(['auth', 'role:Admin,Mentor']);


// Route::get('/truncate', function () {
//     Peserta::truncate();
// });
