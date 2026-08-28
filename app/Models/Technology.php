<?php

namespace App\Models;

use App\Enums\TechnologyGroup;
use Database\Factories\TechnologyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property TechnologyGroup $stack_group
 * @property array<int, string>|null $observation_keys
 * @property-read ProjectTechnology $pivot
 */
class Technology extends Model
{
    /** @use HasFactory<TechnologyFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'stack_group', 'observation_keys'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return [
            'stack_group' => TechnologyGroup::class,
            'observation_keys' => 'array',
        ];
    }

    /** @return BelongsToMany<Project, $this, ProjectTechnology, 'pivot'> */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->using(ProjectTechnology::class)
            ->withPivot('provenance', 'is_declared', 'observed_at')
            ->withTimestamps();
    }

    /**
     * The curated vocabulary grouped by stack group, shaped for the
     * composer picker and the Discover facet UI. The package group is
     * searchable — its vocabulary grows — and carries curated
     * suggestion slugs from the suggested_packages config.
     *
     * @return array<int, array{group: string, label: string, multiple: bool, searchable: bool, suggested: array<int, string>, technologies: array<int, array{id: int, name: string, slug: string}>}>
     */
    public static function groupedVocabulary(): array
    {
        $suggestedPackageSlugs = static::query()
            ->where('stack_group', TechnologyGroup::Package->value)
            ->whereIn('name', config('shipped.suggested_packages', []))
            ->pluck('slug')
            ->all();

        return collect(TechnologyGroup::cases())
            ->map(fn (TechnologyGroup $group): array => [
                'group' => $group->value,
                'label' => $group->label(),
                'multiple' => $group->allowsMultiple(),
                'searchable' => $group === TechnologyGroup::Package,
                'suggested' => $group === TechnologyGroup::Package ? $suggestedPackageSlugs : [],
                'technologies' => static::query()
                    ->where('stack_group', $group->value)
                    ->orderBy('name')
                    ->get()
                    ->map(fn (self $technology): array => [
                        'id' => $technology->id,
                        'name' => $technology->name,
                        'slug' => $technology->slug,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }
}
