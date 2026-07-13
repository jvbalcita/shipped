<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected $fillable = ['category_id', 'connected_environment_id', 'name', 'slug', 'tagline', 'description', 'cover_image_path', 'live_url', 'github_url', 'is_public'];

    protected $appends = ['cover_image_url'];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_demo' => 'boolean',
            'verified_at' => 'datetime',
            'verification_checked_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<ConnectedEnvironment, $this> */
    public function connectedEnvironment(): BelongsTo
    {
        return $this->belongsTo(ConnectedEnvironment::class);
    }

    /** @return HasMany<Release, $this> */
    public function releases(): HasMany
    {
        return $this->hasMany(Release::class);
    }

    /** @return HasMany<Cheer, $this> */
    public function cheers(): HasMany
    {
        return $this->hasMany(Cheer::class);
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeDiscoverable(Builder $query): Builder
    {
        return $query->where(function ($query): void {
            $query->where(function ($query): void {
                $query
                    ->where('is_public', true)
                    ->where('verification_status', 'verified')
                    ->whereHas('releases', fn ($query) => $query
                        ->whereNotNull('published_at')
                        ->where('published_at', '<=', now()));
            });

            if (app()->environment(['local', 'testing'])) {
                $query->orWhere(function ($query): void {
                    $query
                        ->where('is_demo', true)
                        ->where('is_public', true)
                        ->whereHas('releases', fn ($query) => $query
                            ->whereNotNull('published_at')
                            ->where('published_at', '<=', now()));
                });
            }
        });
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->discoverable();
    }

    public function isPubliclyDiscoverable(): bool
    {
        return $this->is_public
            && $this->verification_status === 'verified'
            && $this->releases()->published()->exists();
    }

    public function withdrawFromPublicRegistry(): void
    {
        $this->forceFill(['is_public' => false])->save();
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
}
