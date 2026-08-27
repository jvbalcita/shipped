<?php

namespace App\Models;

use App\Concerns\Cheerable;
use App\Concerns\Followable;
use App\Enums\ProjectPricing;
use App\Services\LaravelCloud\LaravelCloudUrl;
use Carbon\CarbonInterface;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property ProjectPricing|null $pricing
 * @property Carbon|null $launch_date
 * @property CarbonInterface|null $verified_at
 * @property int|null $profile_featured_order
 * @property-read int $published_releases_count
 * @property-read float|null $reviews_avg_rating
 */
class Project extends Model
{
    use Cheerable, Followable;

    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'connected_environment_id',
        'name',
        'slug',
        'tagline',
        'description',
        'cover_image_path',
        'logo_path',
        'live_url',
        'laravel_cloud_url',
        'github_url',
        'pricing',
        'launch_date',
        'is_public',
        'verification_method',
    ];

    protected $appends = ['cover_image_url', 'logo_url', 'filed_serial'];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_demo' => 'boolean',
            'profile_featured_order' => 'integer',
            'pricing' => ProjectPricing::class,
            'launch_date' => 'date',
            'verified_at' => 'datetime',
            'verification_checked_at' => 'datetime',
            'filed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function cloudUrl(): ?LaravelCloudUrl
    {
        if ($this->laravel_cloud_url === null) {
            return null;
        }

        return LaravelCloudUrl::tryFrom((string) $this->laravel_cloud_url);
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

    /** @return HasOne<ShipStory, $this> */
    public function shipStory(): HasOne
    {
        return $this->hasOne(ShipStory::class);
    }

    /** @return HasOne<ShipStory, $this> */
    public function approvedShipStory(): HasOne
    {
        return $this->shipStory()->approvedAndComplete();
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /** @return BelongsToMany<Technology, $this, ProjectTechnology, 'pivot'> */
    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class)
            ->using(ProjectTechnology::class)
            ->withPivot('provenance')
            ->withTimestamps();
    }

    /** @return HasMany<ProjectScreenshot, $this> */
    public function screenshots(): HasMany
    {
        return $this->hasMany(ProjectScreenshot::class)->orderBy('sort_order');
    }

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** @return HasMany<Comment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
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
                        ->where('published_at', '<=', now()))
                    ->whereHas('approvedShipStory');
            });

            if (app()->environment(['local', 'testing'])) {
                $query->orWhere(function ($query): void {
                    $query
                        ->where('is_demo', true)
                        ->where('is_public', true)
                        ->whereHas('releases', fn ($query) => $query
                            ->whereNotNull('published_at')
                            ->where('published_at', '<=', now()))
                        ->whereHas('approvedShipStory');
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
            && $this->releases()->published()->exists()
            && $this->hasApprovedShipStory();
    }

    public function hasApprovedShipStory(): bool
    {
        if ($this->relationLoaded('shipStory')) {
            return $this->shipStory?->isApprovedAndComplete() === true;
        }

        return $this->approvedShipStory()->exists();
    }

    public function withdrawFromPublicRegistry(): void
    {
        $this->forceFill(['is_public' => false])->save();
    }

    /**
     * Assign the next filed serial number when a project enters the registry.
     * Race-safe against the unique index via a bounded retry loop that
     * re-reads the maximum on each unique_violation (Postgres 23505).
     */
    public function assignFiledNumber(): void
    {
        if ($this->filed_number !== null) {
            return;
        }

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $next = (int) (Project::query()->max('filed_number') ?? 0) + 1;

            try {
                $this->forceFill(['filed_number' => $next, 'filed_at' => now()])->save();

                return;
            } catch (QueryException $e) {
                if ($e->getCode() !== '23505' && ! str_contains((string) $e->getMessage(), '23505')) {
                    throw $e;
                }
            }
        }
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

    /** @return Attribute<string|null, never> */
    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->logo_path === null
                ? null
                : Storage::disk()->url($this->logo_path),
        );
    }

    /** @return Attribute<string|null, never> */
    protected function filedSerial(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->filed_number === null
                ? null
                : 'DISPATCH '.str_pad((string) $this->filed_number, 4, '0', STR_PAD_LEFT),
        );
    }
}
