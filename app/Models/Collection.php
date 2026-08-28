<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\CollectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property string $title
 * @property string $slug
 * @property string $description
 * @property string|null $cover_image_path
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
class Collection extends Model
{
    /** @use HasFactory<CollectionFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'cover_image_path',
    ];

    protected $appends = ['cover_image_url'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsToMany<Project, $this> */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->withPivot('position')
            ->orderByPivot('position');
    }

    /** @return BelongsToMany<Project, $this> */
    public function discoverableProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->whereIn('projects.id', Project::query()->discoverable()->select('id'))
            ->withPivot('position')
            ->orderByPivot('position');
    }

    /**
     * Collections with at least one currently discoverable member — the
     * only ones the public registry surfaces.
     *
     * @param  Builder<Collection>  $query
     * @return Builder<Collection>
     */
    public function scopeWithLiveMembers(Builder $query): Builder
    {
        return $query->whereHas('projects', function (Builder $query): void {
            $query->whereIn('projects.id', Project::query()->discoverable()->select('id'));
        });
    }

    /** @return Attribute<string|null, never> */
    protected function coverImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->cover_image_path === null
                ? null
                : Storage::disk()->url($this->cover_image_path),
        );
    }

    /**
     * A unique slug derived from the title; a colliding title gets a
     * numeric suffix instead of failing the save.
     */
    public static function uniqueSlugFor(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $suffix = 2;

        while (self::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn (Builder $query): Builder => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
