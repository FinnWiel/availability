<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('dashboard', function () {
    $nextAvailableDateTime = request()->user()
        ?->eventAvailabilities()
        ->where('available_at', '>=', now())
        ->orderBy('available_at')
        ->first()
        ?->available_at;

    return view('dashboard', [
        'nextAvailableDateTime' => $nextAvailableDateTime,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])
    ->group(function (): void {
        Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');
        Route::post('events/{event}/availability', [EventController::class, 'storeAvailability'])->name('events.availability.store');
        Route::delete('events/{event}/availability/{availability}', [EventController::class, 'destroyAvailability'])->name('events.availability.destroy');
    });

Route::middleware(['auth', 'verified', 'role:admin'])
    ->name('admin.')
    ->group(function (): void {
        Route::get('settings/users', [UserManagementController::class, 'users'])->name('settings.users');
        Route::get('settings/events', [UserManagementController::class, 'events'])->name('settings.events');
        Route::post('settings/events', [UserManagementController::class, 'storeEvent'])->name('events.store');
        Route::patch('settings/events/{event}', [UserManagementController::class, 'updateEvent'])->name('events.update');
        Route::delete('settings/events/{event}', [UserManagementController::class, 'destroyEvent'])->name('events.destroy');
        Route::patch('settings/users/{user}/role', [UserManagementController::class, 'updateRole'])->name('users.update-role');
        Route::patch('settings/users/{user}/events', [UserManagementController::class, 'updateEvents'])->name('users.update-events');
        Route::delete('settings/users/{user}', [UserManagementController::class, 'destroyUser'])->name('users.destroy');
    });

require __DIR__.'/settings.php';
