<?php

namespace App\Models;

use App\Enums\TechnologyProvenance;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property TechnologyProvenance $provenance
 */
class ProjectTechnology extends Pivot
{
    protected $table = 'project_technology';

    protected $casts = [
        'provenance' => TechnologyProvenance::class,
    ];
}
