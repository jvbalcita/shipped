<?php

use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\BadgeController;
use App\Http\Controllers\CloudConnectionController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\CommentCheerController;
use App\Http\Controllers\ConnectedEnvironmentController;
use App\Http\Controllers\ContentReportController;
use App\Http\Controllers\CreatorController;
use App\Http\Controllers\DiscoverController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\LaunchKitController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OgController;
use App\Http\Controllers\Onboarding\UsernameController as OnboardingUsernameController;
use App\Http\Controllers\ProductEventController;
use App\Http\Controllers\ProfileFeaturedProjectsController;
use App\Http\Controllers\ProjectCheerController;
use App\Http\Controllers\ProjectCommentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectFollowController;
use App\Http\Controllers\ProjectReleaseController;
use App\Http\Controllers\ProjectReviewController;
use App\Http\Controllers\ProjectShipStoryController;
use App\Http\Controllers\ProjectVerificationController;
use App\Http\Controllers\ProjectVisibilityController;
use App\Http\Controllers\ReleaseController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TechnologyController;
use App\Http\Controllers\UserFollowController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('home');
Route::get('/discover', DiscoverController::class)->name('discover');
Route::get('/built-with', [TechnologyController::class, 'index'])->name('technologies.index');
Route::get('/built-with/{technology:slug}', [TechnologyController::class, 'show'])->name('technologies.show');
Route::get('/sitemap.xml', [SitemapController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

Route::get('oauth/{provider}', [OAuthController::class, 'redirect'])
    ->name('oauth.redirect')
    ->whereIn('provider', ['google', 'github']);
Route::get('oauth/{provider}/callback', [OAuthController::class, 'callback'])
    ->name('oauth.callback')
    ->whereIn('provider', ['google', 'github']);

Route::middleware(['auth'])->group(function () {
    Route::get('welcome/username', [OnboardingUsernameController::class, 'edit'])->name('username.welcome');
    Route::patch('welcome/username', [OnboardingUsernameController::class, 'update'])
        ->middleware('throttle:10,1')
        ->name('username.claim');
    Route::put('profile/featured-projects', [ProfileFeaturedProjectsController::class, 'update'])
        ->name('profile.featured-projects.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [ProjectController::class, 'index'])->name('dashboard');
    Route::get('feed', FeedController::class)->name('feed');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::delete('cloud-connection', [CloudConnectionController::class, 'destroy'])->name('cloud-connection.destroy');
    Route::get('cloud-connection/environments', [ConnectedEnvironmentController::class, 'index'])->name('cloud-connection.environments');

    // Curator-only collection management. Registered before the public
    // /collections/{slug} route so create/edit never bind as slugs.
    Route::middleware('can:curate')->group(function (): void {
        Route::get('collections/create', [CollectionController::class, 'create'])->name('collections.create');
        Route::post('collections', [CollectionController::class, 'store'])->name('collections.store');
        Route::get('collections/{collection}/edit', [CollectionController::class, 'edit'])->name('collections.edit');
        Route::put('collections/{collection}', [CollectionController::class, 'update'])->name('collections.update');
        Route::delete('collections/{collection}', [CollectionController::class, 'destroy'])->name('collections.destroy');

        // Curator moderation queue for builder-filed content reports.
        Route::get('reports', [ContentReportController::class, 'index'])->name('reports.index');
        Route::patch('reports/{report}', [ContentReportController::class, 'update'])
            ->scopeBindings()
            ->name('reports.update');
    });

    // Any signed-in builder can report registry content they believe
    // violates the trust contract; resolution stays curator-only.
    Route::post('reports', [ContentReportController::class, 'store'])
        ->middleware('throttle:content-reports')
        ->name('reports.store');

    Route::resource('projects', ProjectController::class)->except(['show']);
    Route::get('projects/{project}/launch-kit', [LaunchKitController::class, 'show'])->name('projects.launch-kit.show');
    Route::post('product-events', [ProductEventController::class, 'store'])
        ->middleware('throttle:60,1')
        ->name('product-events.store');
    Route::post('projects/{project}/releases', [ProjectReleaseController::class, 'store'])->name('projects.releases.store');
    Route::put('projects/{project}/ship-story', [ProjectShipStoryController::class, 'update'])->name('projects.ship-story.update');
    Route::patch('projects/{project}/visibility', [ProjectVisibilityController::class, 'update'])->name('projects.visibility.update');
    Route::post('projects/{project}/verification', [ProjectVerificationController::class, 'store'])
        ->middleware('throttle:project-verification')
        ->name('projects.verification.store');
    Route::post('projects/{project}/cheers', [ProjectCheerController::class, 'store'])->name('projects.cheers.store');
    Route::resource('projects.reviews', ProjectReviewController::class)
        ->only(['store', 'update', 'destroy'])
        ->scoped(['review' => 'id']);
    Route::resource('projects.comments', ProjectCommentController::class)
        ->only(['store', 'update', 'destroy'])
        ->scoped(['comment' => 'id']);
    Route::post('comments/{comment}/cheers', [CommentCheerController::class, 'store'])->name('comments.cheers.store');
    Route::post('users/{user}/follow', [UserFollowController::class, 'store'])->name('users.follow.store');
    Route::delete('users/{user}/follow', [UserFollowController::class, 'destroy'])->name('users.follow.destroy');
    Route::post('projects/{project}/follow', [ProjectFollowController::class, 'store'])->name('projects.follow.store');
    Route::delete('projects/{project}/follow', [ProjectFollowController::class, 'destroy'])->name('projects.follow.destroy');
});

Route::get('/@{creator:username}', [CreatorController::class, 'show'])->name('creators.show');

Route::get('/collections', [CollectionController::class, 'index'])->name('collections.index');
Route::get('/collections/{collection:slug}', [CollectionController::class, 'show'])->name('collections.show');
Route::get('/@{creator:username}/{project:slug}/releases/{release}', [ReleaseController::class, 'show'])
    ->scopeBindings()
    ->name('releases.show');
Route::get('/@{creator:username}/{project:slug}', [ProjectController::class, 'show'])
    ->scopeBindings()
    ->name('projects.show');

Route::get('/og/@{creator:username}', [OgController::class, 'creator'])->name('og.creator');
Route::get('/og/site.svg', [OgController::class, 'site'])->name('og.site');
Route::get('/og/@{creator:username}/{project:slug}', [OgController::class, 'project'])
    ->scopeBindings()
    ->name('og.project');
Route::get('/og/@{creator:username}/{project:slug}/releases/{release}.svg', [OgController::class, 'release'])
    ->scopeBindings()
    ->name('og.release');

Route::get('/covers/@{creator:username}/{project:slug}', [OgController::class, 'cover'])
    ->scopeBindings()
    ->name('cover.project');

Route::get('/manifests/{creator:username}/{project:slug}.svg', [ManifestController::class, 'show'])
    ->scopeBindings()
    ->name('manifests.show');

Route::get('/badges/{project}.svg', [BadgeController::class, 'show'])->name('badges.show');

require __DIR__.'/settings.php';
