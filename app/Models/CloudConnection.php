<?php

namespace App\Models;

use Database\Factories\CloudConnectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CloudConnection extends Model
{
    /** @use HasFactory<CloudConnectionFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'api_token', 'status', 'last_validated_at', 'last_error'];

    protected $hidden = ['api_token'];

    protected $attributes = ['status' => 'disconnected'];

    protected function casts(): array
    {
        return [
            'api_token' => 'encrypted',
            'last_validated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<ConnectedEnvironment, $this> */
    public function connectedEnvironments(): HasMany
    {
        return $this->hasMany(ConnectedEnvironment::class);
    }
}
