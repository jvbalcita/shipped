<?php

namespace App\Models;

use App\Enums\TechnologyGroup;
use Database\Factories\TechnologyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property TechnologyGroup $stack_group
 * @property-read ProjectTechnology $pivot
 */
class Technology extends Model
{
    /** @use HasFactory<TechnologyFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'stack_group'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return [
            'stack_group' => TechnologyGroup::class,
        ];
    }

    /** @return BelongsToMany<Project, $this, ProjectTechnology, 'pivot'> */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->using(ProjectTechnology::class)
            ->withPivot('provenance')
            ->withTimestamps();
    }

    /**
     * The curated vocabulary grouped by stack group, shaped for the
     * composer picker and the Discover facet UI.
     *
     * @return array<int, array{group: string, label: string, multiple: bool, technologies: array<int, array{id: int, name: string, slug: string}>}>
     */
    public static function groupedVocabulary(): array
    {
        return collect(TechnologyGroup::cases())
            ->map(fn (TechnologyGroup $group): array => [
                'group' => $group->value,
                'label' => $group->label(),
                'multiple' => $group->allowsMultiple(),
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
