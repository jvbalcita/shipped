<?php

namespace App\Models;

use Database\Factories\CheerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Cheer extends Model
{
    /** @use HasFactory<CheerFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'cheerable_type', 'cheerable_id'];

    public function cheerable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
