<?php

use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('dashboard');
// })->name('dashboard');

Route::livewire('/', 'pages::menu.dashboard')
    ->middleware(['auth'])
    ->name('dashboard');

// Route::livewire('dashboard', 'pages::menu.dashboard')->name('dashboard');

Route::prefix('management')->name('management.')->group(function () {
    Route::livewire('role', 'pages::management.role-management')->name('role');
    Route::livewire('user', 'pages::management.user-management')->name('user');
});

Route::prefix('menu')->name('menu.')->group(function () {
    Route::livewire('analyst_flow', 'pages::menu.analyst_flow')->name('analyst');
    Route::livewire('report_analyst', 'pages::menu.report_analyst')->name('report');
    Route::livewire('simulation', 'pages::menu.simulation')->name('simulation');
    Route::livewire('report_analyst', 'pages::menu.report_analyst')->name('report');
});

Route::prefix('data')->name('data.')->group(function () {
    Route::livewire('riwayat', 'pages::menu.riwayat')->name('riwayat');
    Route::livewire('validasi_iou', 'pages::menu.validasi_iou')->name('validasi_iou');
});

require __DIR__ . '/settings.php';

use App\Http\Controllers\YamazumiController;

// Halaman utama form upload
Route::get('/yamazumi', [YamazumiController::class, 'index'])->name('yamazumi.index');

// Endpoint untuk menerima form dari Blade dan mengirim ke Python
Route::post('/yamazumi/analyze', [YamazumiController::class, 'analyze'])->name('yamazumi.analyze');

// Endpoint untuk mengecek status (akan dipanggil oleh AJAX/JavaScript dari Blade)
Route::get('/yamazumi/status/{job_id}', [YamazumiController::class, 'checkStatus'])->name('yamazumi.status');

// Halaman untuk menampilkan hasil akhir (Grafik & Tabel)
Route::get('/yamazumi/result/{job_id}', [YamazumiController::class, 'showResult'])->name('yamazumi.result');