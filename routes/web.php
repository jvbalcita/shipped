<?php

use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\CloudConnectionController;
use App\Http\Controllers\CommentCheerController;
use App\Http\Controllers\ConnectedEnvironmentController;
use App\Http\Controllers\CreatorController;
use App\Http\Controllers\DiscoverController;
use App\Http\Controllers\OgController;
use App\Http\Controllers\ProjectCheerController;
use App\Http\Controllers\ProjectCommentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectReleaseController;
use App\Http\Controllers\ProjectReviewController;
use App\Http\Controllers\ProjectVerificationController;
use App\Http\Controllers\ProjectVisibilityController;
use App\Http\Controllers\ReleaseController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('home');
Route::get('/discover', DiscoverController::class)->name('discover');

Route::get('oauth/{provider}', [OAuthController::class, 'redirect'])
    ->name('oauth.redirect')
    ->whereIn('provider', ['google', 'github']);
Route::get('oauth/{provider}/callback', [OAuthController::class, 'callback'])
    ->name('oauth.callback')
    ->whereIn('provider', ['google', 'github']);

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
    Route::resource('projects.reviews', ProjectReviewController::class)
        ->only(['store', 'update', 'destroy'])
        ->scoped(['review' => 'id']);
    Route::resource('projects.comments', ProjectCommentController::class)
        ->only(['store', 'update', 'destroy'])
        ->scoped(['comment' => 'id']);
    Route::post('comments/{comment}/cheers', [CommentCheerController::class, 'store'])->name('comments.cheers.store');
});

Route::get('/@{creator:username}', [CreatorController::class, 'show'])->name('creators.show');
Route::get('/@{creator:username}/{project:slug}/releases/{release}', [ReleaseController::class, 'show'])
    ->scopeBindings()
    ->name('releases.show');
Route::get('/@{creator:username}/{project:slug}', [ProjectController::class, 'show'])
    ->scopeBindings()
    ->name('projects.show');

Route::get('/og/@{creator:username}/{project:slug}', [OgController::class, 'project'])
    ->scopeBindings()
    ->name('og.project');

Route::get('/covers/@{creator:username}/{project:slug}', [OgController::class, 'cover'])
    ->scopeBindings()
    ->name('cover.project');

require __DIR__.'/settings.php';
