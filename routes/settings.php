<?php

use App\Http\Controllers\Settings\OAuthController as SettingsOAuthController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\UsernameController;
use App\Http\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('settings/username', [UsernameController::class, 'update'])->name('username.update');
    Route::patch('settings/email', [SecurityController::class, 'updateEmail'])
        ->middleware([RequirePassword::class, 'throttle:6,1'])
        ->name('user-email.update');

    Route::post('settings/oauth/{provider}', [SettingsOAuthController::class, 'link'])
        ->middleware([RequirePassword::class, 'throttle:10,1'])
        ->whereIn('provider', ['google', 'github'])
        ->name('oauth.link');
    Route::delete('settings/oauth/{provider}', [SettingsOAuthController::class, 'unlink'])
        ->middleware([RequirePassword::class, 'throttle:10,1'])
        ->whereIn('provider', ['google', 'github'])
        ->name('oauth.unlink');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
