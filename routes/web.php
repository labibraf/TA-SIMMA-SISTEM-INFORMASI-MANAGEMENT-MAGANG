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
});

// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/home', [DashboardController::class, 'index'])->name('home');

Route::fallback(function () {
    return "gagal memuat rute yang diminta";
});

// mentor
Route::resource('mentor', MentorController::class);

// peserta
Route::resource('peserta', PesertaController::class);
Route::get('/api/mentors/by-bagian/{bagianId}', [MentorController::class, 'getMentorsByBagian'])->name('api.mentors.by_bagian');

// laporan_harian
Route::resource('laporan_harian', LaporanHarianController::class);
Route::get('/laporan_harian/create/{penugasan_id?}', [LaporanHarianController::class, 'create'])->name('laporan_harian.create');

// penugasan
Route::resource('penugasans', PenugasanController::class);
Route::put('/penugasan/{id}/status', [PenugasanController::class, 'updateStatus']);
// Route::put('/penugasans/{id}/nilai_kualitas', [PenugasanController::class, 'updateNilaiKualitas'])->name('penugasans.updateNilaiKualitas');
Route::put('/penugasans/{id}/kualitas', [PenugasanController::class, 'updateKualitas'])->name('penugasans.updateKualitas');
Route::put('/penugasan/{id}/status', [PenugasanController::class, 'updateStatus'])->name('penugasan.update-status');
// Tambahkan route untuk update approve
Route::put('/penugasan/{id}/approve', [PenugasanController::class, 'updateApprove'])->name('penugasan.updateApprove');
// Tambahkan route untuk update feedback
Route::put('/penugasan/{id}/feedback', [PenugasanController::class, 'updateFeedback'])->name('penugasan.updateFeedback');


//laporan akhir
// Route resource untuk CRUD utama (index, create, store, show, edit, update, destroy)
Route::resource('laporan-akhir', LaporanAkhirController::class);
// Route tambahan untuk update status (hanya untuk admin/mentor)
Route::patch('/laporan-akhir/{laporanAkhir}/status', [LaporanAkhirController::class, 'updateStatus'])
    ->name('laporan-akhir.updateStatus');

// Repository
// Route resource untuk CRUD utama
Route::resource('repository', RepositoryController::class);
// Route tambahan untuk publish/unpublish (hanya untuk admin)
Route::patch('/repository/{id}/publish', [RepositoryController::class, 'publish'])
    ->name('repository.publish')
    ->middleware('isAdmin');
Route::patch('/repository/{id}/unpublish', [RepositoryController::class, 'unpublish'])
    ->name('repository.unpublish')
    ->middleware('isAdmin');

// users
Route::resource('users', UserController::class)->middleware('isAdmin');
Route::post('users-update-role', [UserController::class, 'updateRole'])->name('users.update-role');
// bagian
Route::resource('bagian', BagianController::class);


// Route::get('/truncate', function () {
//     Peserta::truncate();
// });
