<?php

namespace App\Observers;

use App\Models\Review;
use App\Services\ActivityRecorder;
use App\Services\NotificationRecorder;

class ReviewObserver
{
    public function __construct(
        private ActivityRecorder $activities,
        private NotificationRecorder $notifications,
    ) {}

    public function created(Review $review): void
    {
        $this->activities->record('reviewed', $review->user, $review->project, $review->created_at, [
            'rating' => $review->rating,
        ]);

        $this->notifications->record(
            $review->project->creator,
            'review',
            $review->user,
            $review->project,
            ['rating' => $review->rating],
        );
    }
}
