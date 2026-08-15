<?php

namespace App\Observers;

use App\Models\Review;
use App\Services\ActivityRecorder;

class ReviewObserver
{
    public function __construct(private ActivityRecorder $activities) {}

    public function created(Review $review): void
    {
        $this->activities->record('reviewed', $review->user, $review->project, $review->created_at, [
            'rating' => $review->rating,
        ]);
    }
}
