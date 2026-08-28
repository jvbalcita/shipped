<?php

namespace App\Enums;

/**
 * The roadmap's product event vocabulary. Server-side lifecycle moments are
 * recorded by controllers and services; client-recordable names are the only
 * values POST /product-events accepts.
 */
enum ProductEventName: string
{
    case SubmissionStarted = 'submission_started';
    case SubmissionPublished = 'submission_published';
    case VerificationStarted = 'verification_started';
    case VerificationPassed = 'verification_passed';
    case VerificationFailed = 'verification_failed';
    case ShipStoryPublished = 'ship_story_published';
    case LaunchKitViewed = 'launch_kit_viewed';
    case BadgeSnippetCopied = 'badge_snippet_copied';
    case CardLinkCopied = 'card_link_copied';
    case ManifestLinkCopied = 'manifest_link_copied';
    case ShareTextCopied = 'share_text_copied';
    case ShareIntentClicked = 'share_intent_clicked';

    public function canBeRecordedByClient(): bool
    {
        return match ($this) {
            self::BadgeSnippetCopied,
            self::CardLinkCopied,
            self::ManifestLinkCopied,
            self::ShareTextCopied,
            self::ShareIntentClicked => true,
            default => false,
        };
    }
}
