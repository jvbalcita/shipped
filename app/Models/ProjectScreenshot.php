<?php

namespace App\Models;

use Database\Factories\ProjectScreenshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $project_id
 * @property string $path
 * @property string|null $caption
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Project $project
 */
class ProjectScreenshot extends Model
{
    /** @use HasFactory<ProjectScreenshotFactory> */
    use HasFactory;

    protected $table = 'project_screenshots';

    protected $appends = ['url'];

    protected $fillable = ['project_id', 'path', 'caption', 'sort_order'];

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk()->url($this->path);
    }
}
