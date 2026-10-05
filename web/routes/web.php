<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes — IntelliSched
|--------------------------------------------------------------------------
*/

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Role-Based Dashboards
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return view('welcome');
    })->name('admin.dashboard');

    Route::get('/coordinator/dashboard', function () {
        return view('welcome');
    })->name('coordinator.dashboard');

    Route::get('/teacher/dashboard', function () {
        return view('welcome');
    })->name('teacher.dashboard');

    Route::get('/student/dashboard', function () {
        return view('welcome');
    })->name('student.dashboard');
});
