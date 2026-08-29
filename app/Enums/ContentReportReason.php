<?php

namespace App\Enums;

/**
 * Controlled vocabulary for why a signed-in builder is reporting a piece of
 * registry content. The curated list keeps the reports queue answerable —
 * free-form reasons would make triage and roadmap evidence impossible.
 */
enum ContentReportReason: string
{
    case BrokenLink = 'broken_link';
    case NotLaravel = 'not_laravel';
    case Misleading = 'misleading';
    case Spam = 'spam';
    case Inappropriate = 'inappropriate';
    case Duplicate = 'duplicate';
    case Ownership = 'ownership';
    case Other = 'other';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $reason): array => [$reason->value => $reason->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::BrokenLink => __('Broken or dead link'),
            self::NotLaravel => __('Not actually built with Laravel'),
            self::Misleading => __('Misleading content'),
            self::Spam => __('Spam'),
            self::Inappropriate => __('Inappropriate content'),
            self::Duplicate => __('Duplicate project'),
            self::Ownership => __('Ownership or attribution dispute'),
            self::Other => __('Something else'),
        };
    }
}
