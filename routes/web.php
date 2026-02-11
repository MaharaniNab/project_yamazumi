<?php

use App\Livewire\Dashboard;
use App\Livewire\ManagemenPengguna\CreateRole;
use App\Livewire\ManagemenPengguna\CreateUser;
use App\Livewire\ManagemenPengguna\EditRole;
use App\Livewire\ManagemenPengguna\EditUser;
use App\Livewire\ManagemenPengguna\RoleManagement;
use App\Livewire\ManagemenPengguna\UserManagement;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('dashboard');
// })->name('dashboard');

// Route::view('/', 'dashboard')
//     ->middleware(['auth', 'verified'])
//     ->name('dashboard');

Route::get('/', Dashboard::class)
    ->middleware(['auth'])
    ->name('dashboard');

// Route::prefix('manajemen')->name('manajemen.')->group(function () {
//     Route::get('role', RoleManagement::class)->name('role');
//     Route::get('user', UserManagement::class)->name('user');
//     Route::get('role/create', CreateRole::class)->name('role.create');
//     Route::get('role/{roleId}/edit', EditRole::class)->name('role.edit');
//     Route::get('user/create', CreateUser::class)->name('user.create');
//     Route::get('user/{userId}/edit', EditUser::class)->name('user.edit');
// });

Route::prefix('management')->name('management.')->group(function () {
    Route::livewire('role', 'pages::management.role-management')->name('role');
    Route::livewire('user', 'pages::management.user-management')->name('user');
    Route::livewire('role/create', 'management.create-role')->name('role.create');
    Route::livewire('role/{roleId}/edit', 'management.edit-role')->name('role.edit');
    Route::livewire('user/create', 'management.create-user')->name('user.create');
    Route::livewire('user/{userId}/edit', 'management.edit-user')->name('user.edit');
});

require __DIR__ . '/settings.php';
