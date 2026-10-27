<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\Hr\UserController;
use App\Http\Controllers\Manage\EmployeeManageController;
use App\Http\Controllers\Manage\StructureController;
use Illuminate\Support\Facades\Route;

// Public landing page
Route::view('/', 'home')->name('home');

// Sign in / out (no public registration: HR creates accounts)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

// Signed-in area. While open access is on (config/workforce.php) no sign-in is needed.
$appMiddleware = config('workforce.open_access') ? [] : ['auth', 'active'];

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware($appMiddleware)->group(function () {
    // ---- Everyone signed in ----
    Route::get('/directory', [DirectoryController::class, 'index'])->name('directory');
    Route::get('/me', [EmployeeController::class, 'me'])->name('me');
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
    Route::get('/employees/{employee}/photo', [EmployeeController::class, 'photo'])->name('employees.photo');

    Route::get('/account/password', [AccountController::class, 'editPassword'])->name('account.password');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');

    // HR overview (others are redirected to the directory)
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // ---- HR: employees and organisation ----
    Route::middleware('can:manage-records')->prefix('manage')->name('manage.')->group(function () {
        Route::get('/employees/create', [EmployeeManageController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeManageController::class, 'store'])->name('employees.store');
        Route::get('/employees/{employee}/edit', [EmployeeManageController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [EmployeeManageController::class, 'update'])->name('employees.update');
        Route::post('/employees/{employee}/transfer', [EmployeeManageController::class, 'transfer'])->name('employees.transfer');
        Route::delete('/employees/{employee}', [EmployeeManageController::class, 'destroy'])->name('employees.destroy');

        // divisions | departments | offices | projects | designations
        $types = 'divisions|departments|offices|projects|designations';
        Route::get('/{type}', [StructureController::class, 'index'])->where('type', $types)->name('structure.index');
        Route::get('/{type}/create', [StructureController::class, 'create'])->where('type', $types)->name('structure.create');
        Route::post('/{type}', [StructureController::class, 'store'])->where('type', $types)->name('structure.store');
        Route::get('/{type}/{id}/edit', [StructureController::class, 'edit'])->where(['type' => $types, 'id' => '[0-9]+'])->name('structure.edit');
        Route::put('/{type}/{id}', [StructureController::class, 'update'])->where(['type' => $types, 'id' => '[0-9]+'])->name('structure.update');
        Route::delete('/{type}/{id}', [StructureController::class, 'destroy'])->where(['type' => $types, 'id' => '[0-9]+'])->name('structure.destroy');
    });

    // ---- HR: sign-in accounts ----
    Route::middleware('can:manage-users')->prefix('hr')->name('hr.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
    });
});
