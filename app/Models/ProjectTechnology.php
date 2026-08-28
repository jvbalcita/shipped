<?php

namespace App\Models;

use App\Enums\TechnologyProvenance;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property TechnologyProvenance $provenance
 * @property bool $is_declared
 * @property CarbonInterface|null $observed_at
 */
class ProjectTechnology extends Pivot
{
    protected $table = 'project_technology';

    protected $casts = [
        'provenance' => TechnologyProvenance::class,
        'is_declared' => 'boolean',
        'observed_at' => 'datetime',
    ];
}
