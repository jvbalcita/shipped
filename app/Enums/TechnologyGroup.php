<?php

namespace App\Enums;

enum TechnologyGroup: string
{
    case LaravelVersion = 'laravel_version';
    case PhpVersion = 'php_version';
    case Frontend = 'frontend';
    case Database = 'database';
    case Infrastructure = 'infrastructure';
    case Package = 'package';

    public function label(): string
    {
        return match ($this) {
            self::LaravelVersion => 'Laravel version',
            self::PhpVersion => 'PHP version',
            self::Frontend => 'Frontend',
            self::Database => 'Database',
            self::Infrastructure => 'Infrastructure',
            self::Package => 'Packages',
        };
    }

    /**
     * Whether a project may declare more than one technology from this
     * group. A project runs on exactly one Laravel and one PHP version.
     */
    public function allowsMultiple(): bool
    {
        return $this !== self::LaravelVersion && $this !== self::PhpVersion;
    }
}
