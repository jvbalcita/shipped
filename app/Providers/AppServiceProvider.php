<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Release;
use App\Models\Review;
use App\Models\User;
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
