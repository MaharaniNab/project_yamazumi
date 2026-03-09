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
});

require __DIR__ . '/settings.php';
