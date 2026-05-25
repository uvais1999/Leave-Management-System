<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\DashboardController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // User management routes
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.update-role');
    Route::patch('/users/{user}/manager', [UserController::class, 'updateManager'])->name('users.update-manager');
    Route::patch('/users/{user}/status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

    // Role management routes
    Route::resource('roles', RoleController::class)->except(['create', 'edit']);

    // Leave Type management routes
    Route::resource('leave-types', LeaveTypeController::class)->except(['create', 'edit']);
    Route::patch('leave-types/{leave_type}/status', [LeaveTypeController::class, 'toggleStatus'])->name('leave-types.toggle-status');

    // Leave Application routes
    Route::resource('leaves', LeaveApplicationController::class)->except(['create', 'edit']);
    Route::patch('leaves/{leave}/approve', [LeaveApplicationController::class, 'approve'])->name('leaves.approve');
    Route::patch('leaves/{leave}/reject', [LeaveApplicationController::class, 'reject'])->name('leaves.reject');
});

require __DIR__.'/auth.php';
