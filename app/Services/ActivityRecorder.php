<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Single writer for the activity feed log. Every feed event goes through
 * record() so idempotency is enforced in one place: a duplicate (same actor,
 * verb, subject, occurred_at) is silently skipped, which lets observers fire
 * freely on retries and scheduled rechecks.
 */
final class ActivityRecorder
{
    public const VERBS = ['launched', 'released', 'reviewed', 'cheered', 'verified'];

    /**
     * @param  array<string, mixed>  $meta
     */
    public function record(string $verb, ?User $actor, Model $subject, ?CarbonInterface $occurredAt = null, array $meta = []): Activity
    {
        assert(in_array($verb, self::VERBS, true));

        return Activity::query()->firstOrCreate(
            [
                'actor_type' => $actor?->getMorphClass(),
                'actor_id' => $actor?->id,
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'verb' => $verb,
                'occurred_at' => ($occurredAt ?? now())->utc(),
            ],
            [
                'meta' => $meta === [] ? null : $meta,
            ],
        );
    }

    /**
     * Fire-once variant used for "launched": dedupe on (verb, subject)
     * regardless of timestamp so re-verifications never duplicate it.
     *
     * @param  array<string, mixed>  $meta
     */
    public function recordOnce(string $verb, ?User $actor, Model $subject, ?CarbonInterface $occurredAt = null, array $meta = []): ?Activity
    {
        assert(in_array($verb, self::VERBS, true));

        $exists = Activity::query()
            ->where('verb', $verb)
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->exists();

        if ($exists) {
            return null;
        }

        return $this->record($verb, $actor, $subject, $occurredAt, $meta);
    }
}
