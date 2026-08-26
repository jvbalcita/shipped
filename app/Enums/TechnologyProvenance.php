<?php

namespace App\Enums;

enum TechnologyProvenance: string
{
    case Declared = 'declared';
    case Observed = 'observed';
    case Reviewed = 'reviewed';

    public function label(): string
    {
        return match ($this) {
            self::Declared => 'Declared by the creator',
            self::Observed => 'Observed by Shipped',
            self::Reviewed => 'Reviewed by Shipped',
        };
    }
}
