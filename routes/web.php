<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventManagementController;
use App\Http\Controllers\ImpersonationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::livewire('dashboard', 'pages::dashboard')->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])
    ->group(function (): void {
        Route::livewire('events', 'pages::events.index')->name('events.index');
        Route::post('events', [EventManagementController::class, 'store'])->name('events.store');
        Route::patch('events/{event}', [EventManagementController::class, 'update'])->name('events.update');
        Route::delete('events/{event}', [EventManagementController::class, 'destroy'])->name('events.destroy');
        Route::post('impersonation/leave', [ImpersonationController::class, 'leave'])->name('impersonation.leave');

        Route::livewire('events/{event}', 'pages::events.show')->name('events.show');
        Route::post('events/{event}/availability', [EventController::class, 'storeAvailability'])->name('events.availability.store');
        Route::delete('events/{event}/availability/{availability}', [EventController::class, 'destroyAvailability'])->name('events.availability.destroy');
    });

Route::middleware(['auth', 'verified', 'role:admin'])
    ->name('admin.')
    ->group(function (): void {
        Route::patch('settings/users/{user}/role', [UserManagementController::class, 'updateRole'])->name('users.update-role');
        Route::patch('settings/users/{user}/events', [UserManagementController::class, 'updateEvents'])->name('users.update-events');
        Route::post('settings/users/{user}/impersonate', [UserManagementController::class, 'impersonate'])->name('users.impersonate');
        Route::delete('settings/users/{user}', [UserManagementController::class, 'destroyUser'])->name('users.destroy');
    });

require __DIR__.'/settings.php';
