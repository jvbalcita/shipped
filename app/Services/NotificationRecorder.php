<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Single writer for in-app notifications, symmetric with the
 * ActivityRecorder. Enforces the two v1 guards in one place: never notify
 * someone about their own action, and one notification per event per
 * recipient (re-cheers and retries are silently skipped).
 */
final class NotificationRecorder
{
    public const TYPES = ['follow', 'cheer', 'review', 'comment', 'reply'];

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(User $recipient, string $type, ?User $actor, ?Model $subject = null, array $data = []): ?Notification
    {
        assert(in_array($type, self::TYPES, true));

        if ($actor !== null && $actor->is($recipient)) {
            return null;
        }

        return Notification::query()->firstOrCreate(
            [
                'user_id' => $recipient->id,
                'type' => $type,
                'actor_type' => $actor?->getMorphClass(),
                'actor_id' => $actor?->id,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
            ],
            [
                'data' => $data === [] ? null : $data,
            ],
        );
    }
}
