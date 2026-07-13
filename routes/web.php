<?php

use App\Http\Controllers\CloudConnectionController;
use App\Http\Controllers\ConnectedEnvironmentController;
use App\Http\Controllers\CreatorController;
use App\Http\Controllers\DiscoverController;
use App\Http\Controllers\ProjectCheerController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectReleaseController;
use App\Http\Controllers\ProjectVerificationController;
use App\Http\Controllers\ProjectVisibilityController;
use App\Http\Controllers\ReleaseController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');
Route::get('/discover', DiscoverController::class)->name('discover');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [ProjectController::class, 'index'])->name('dashboard');
    Route::post('cloud-connection', [CloudConnectionController::class, 'store'])->name('cloud-connection.store');
    Route::delete('cloud-connection', [CloudConnectionController::class, 'destroy'])->name('cloud-connection.destroy');
    Route::get('cloud-connection/environments', [ConnectedEnvironmentController::class, 'index'])->name('cloud-connection.environments');
    Route::resource('projects', ProjectController::class)->except(['show']);
    Route::post('projects/{project}/releases', [ProjectReleaseController::class, 'store'])->name('projects.releases.store');
    Route::patch('projects/{project}/visibility', [ProjectVisibilityController::class, 'update'])->name('projects.visibility.update');
    Route::post('projects/{project}/verification', [ProjectVerificationController::class, 'store'])->name('projects.verification.store');
    Route::post('projects/{project}/cheers', [ProjectCheerController::class, 'store'])->name('projects.cheers.store');
});

Route::get('/@{creator:handle}', [CreatorController::class, 'show'])->name('creators.show');
Route::get('/@{creator:handle}/{project:slug}/releases/{release}', [ReleaseController::class, 'show'])
    ->scopeBindings()
    ->name('releases.show');
Route::get('/@{creator:handle}/{project:slug}', [ProjectController::class, 'show'])
    ->scopeBindings()
    ->name('projects.show');

require __DIR__.'/settings.php';
