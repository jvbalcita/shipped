<?php

namespace App\Models;

use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property string $verb
 * @property Carbon $occurred_at
 * @property array<string, mixed>|null $meta
 */
class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    protected $fillable = [
        'actor_type',
        'actor_id',
        'subject_type',
        'subject_id',
        'verb',
        'occurred_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
