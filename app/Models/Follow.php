<?php

namespace App\Models;

use Database\Factories\FollowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Follow extends Model
{
    /** @use HasFactory<FollowFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'followable_type', 'followable_id'];

    /** @return MorphTo<Model, $this> */
    public function followable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
