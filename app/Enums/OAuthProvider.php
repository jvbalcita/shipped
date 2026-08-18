<?php

namespace App\Enums;

enum OAuthProvider: string
{
    case Google = 'google';
    case GitHub = 'github';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Provider values that have credentials configured and can be offered in the UI.
     *
     * @return array<int, string>
     */
    public static function configured(): array
    {
        return array_values(array_filter(
            self::values(),
            fn (string $provider): bool => (bool) config("services.{$provider}.client_id"),
        ));
    }

    public function isConfigured(): bool
    {
        return (bool) config("services.{$this->value}.client_id");
    }
}
