<?php

namespace App\Concerns;

use App\Models\Follow;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The following side of the follow relationship: everything a given user
 * follows, plus scoped helpers for the read-time activity feed.
 */
trait Follows
{
    /** @return HasMany<Follow, $this> */
    public function followings(): HasMany
    {
        return $this->hasMany(Follow::class);
    }

    /** @return HasMany<Follow, $this> */
    public function followedCreators(): HasMany
    {
        return $this->followings()->where('followable_type', 'user');
    }

    /** @return HasMany<Follow, $this> */
    public function followedProjects(): HasMany
    {
        return $this->followings()->where('followable_type', 'project');
    }

    /**
     * @return Builder<Project>
     */
    public function followedProjectQuery(): Builder
    {
        return Project::query()->whereIn(
            'id',
            $this->followedProjects()->select('followable_id'),
        );
    }

    /**
     * @return Builder<User>
     */
    public function followedCreatorQuery(): Builder
    {
        return User::query()->whereIn(
            'id',
            $this->followedCreators()->select('followable_id'),
        );
    }
}
