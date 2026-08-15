<?php

namespace App\Models;

use Database\Factories\OAuthAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $provider
 * @property string $provider_id
 * @property string|null $provider_token
 * @property string|null $provider_refresh_token
 * @property Carbon|null $token_expires_at
 * @property Carbon|null $linked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property User $user
 */
class OAuthAccount extends Model
{
    /** @use HasFactory<OAuthAccountFactory> */
    use HasFactory;

    protected $table = 'oauth_accounts';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'provider_token',
        'provider_refresh_token',
        'token_expires_at',
        'linked_at',
    ];

    protected $hidden = [
        'provider_token',
        'provider_refresh_token',
    ];

    protected $casts = [
        'provider_token' => 'encrypted',
        'provider_refresh_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'linked_at' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
