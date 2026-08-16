<?php

namespace App\Providers;

use App\Models\Cheer;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Project;
use App\Models\Release;
use App\Models\Review;
use App\Models\User;
use App\Observers\CheerObserver;
use App\Observers\CommentObserver;
use App\Observers\FollowObserver;
use App\Observers\ProjectObserver;
use App\Observers\ReleaseObserver;
use App\Observers\ReviewObserver;
use App\Policies\FollowPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerObservers();
    }

    /**
     * Observer hooks: activity feed events plus in-app notifications,
     * both cheap, idempotent writes on model events.
     */
    protected function registerObservers(): void
    {
        Project::observe(ProjectObserver::class);
        Release::observe(ReleaseObserver::class);
        Review::observe(ReviewObserver::class);
        Cheer::observe(CheerObserver::class);
        Comment::observe(CommentObserver::class);
        Follow::observe(FollowObserver::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Stable, short morph keys for polymorphic relationships
        // (cheers, follows, activities).
        Relation::enforceMorphMap([
            'project' => Project::class,
            'comment' => Comment::class,
            'user' => User::class,
            'release' => Release::class,
            'review' => Review::class,
        ]);

        Gate::define('follow', fn (User $user, User|Project $followable): bool => (new FollowPolicy)->follow($user, $followable));

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
