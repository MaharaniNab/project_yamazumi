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
});

Route::prefix('data')->name('data.')->group(function () {
    Route::livewire('riwayat', 'pages::menu.riwayat')->name('riwayat');
    Route::livewire('validasi_iou', 'pages::menu.validasi_iou')->name('validasi_iou');
});

require __DIR__ . '/settings.php';

use App\Http\Controllers\AnalysisController;

Route::middleware(['auth'])->group(function () {
    Route::post('/analysis/upload',          [AnalysisController::class, 'upload']);
    Route::get('/analysis/status/{jobId}',   [AnalysisController::class, 'checkStatus']);
    Route::get('/analysis/results/{jobId}',  [AnalysisController::class, 'showResults']);
    Route::get('/analysis/simulate/{jobId}', [AnalysisController::class, 'simulate']);
});
