<?php

namespace App\Enums;

enum OAuthProvider: string
{
    case Google = 'google';
    case GitHub = 'github';

    /**
     * @return array<int, self>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
