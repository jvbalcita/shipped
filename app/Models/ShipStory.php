<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ShipStoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** @property CarbonInterface|null $approved_at */
class ShipStory extends Model
{
    /** @use HasFactory<ShipStoryFactory> */
    use HasFactory;

    protected $fillable = [
        'problem',
        'audience',
        'shipped',
        'build_decisions',
        'hardest_problem',
        'lessons_learned',
        'next',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return list<string> */
    public static function requiredContentFields(): array
    {
        return [
            'problem',
            'audience',
            'shipped',
            'build_decisions',
            'hardest_problem',
            'lessons_learned',
        ];
    }

    /**
     * @param  Builder<ShipStory>  $query
     * @return Builder<ShipStory>
     */
    public function scopeApprovedAndComplete(Builder $query): Builder
    {
        $query->whereNotNull('approved_at');

        foreach (self::requiredContentFields() as $field) {
            $query->where($field, '<>', '');
        }

        return $query;
    }

    public function isApproved(): bool
    {
        return $this->approved_at instanceof CarbonInterface;
    }

    public function isComplete(): bool
    {
        foreach (self::requiredContentFields() as $field) {
            if (trim((string) $this->getAttribute($field)) === '') {
                return false;
            }
        }

        return true;
    }

    public function isApprovedAndComplete(): bool
    {
        return $this->isApproved() && $this->isComplete();
    }

    public function excerpt(int $limit = 180): string
    {
        return Str::limit(Str::squish((string) $this->problem), $limit, '…');
    }
}
