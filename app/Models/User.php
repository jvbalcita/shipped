<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Concerns\Followable;
use App\Concerns\Follows;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $username
 * @property string $title
 * @property string|null $location
 * @property string $email
 * @property string|null $bio
 * @property string|null $avatar_path
 * @property array<int, array{type: string, url: string}>|null $links
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property CloudConnection|null $cloudConnection
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'username', 'title', 'location', 'email', 'bio', 'avatar_path', 'links', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use Followable, Follows, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    public function getRouteKeyName(): string
    {
        return 'username';
    }

    /** @return HasMany<Project, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /** @return HasMany<Cheer, $this> */
    public function cheers(): HasMany
    {
        return $this->hasMany(Cheer::class);
    }

    /** @return HasMany<OAuthAccount, $this> */
    public function oauthAccounts(): HasMany
    {
        return $this->hasMany(OAuthAccount::class);
    }

    /**
     * Build a unique username from a seed, slugified to the allowed pattern.
     */
    public static function generateUniqueUsername(string $seed): string
    {
        $base = strtolower(preg_replace('/[^a-z0-9]+/i', '_', explode('@', $seed)[0]));
        $base = trim($base, '_');

        if ($base === '') {
            $base = 'creator';
        }

        $base = substr($base, 0, 24);
        $candidate = $base;
        $attempt = 1;

        while (static::where('username', $candidate)->exists()) {
            $candidate = substr($base, 0, 20).'_'.$attempt;
            $attempt++;
        }

        return $candidate;
    }

    /** @return HasOne<CloudConnection, $this> */
    public function cloudConnection(): HasOne
    {
        return $this->hasOne(CloudConnection::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'links' => 'array',
        ];
    }
}
