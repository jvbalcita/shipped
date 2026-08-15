<?php

namespace App\Enums;

enum ProjectPricing: string
{
    case Free = 'free';
    case Freemium = 'freemium';
    case Paid = 'paid';
    case OpenSource = 'open_source';
    case Subscription = 'subscription';
    case OneTime = 'one_time';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Freemium => 'Freemium',
            self::Paid => 'Paid',
            self::OpenSource => 'Open Source',
            self::Subscription => 'Subscription',
            self::OneTime => 'One-Time',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
