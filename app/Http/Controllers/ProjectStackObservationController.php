<?php

namespace App\Http\Controllers;

use App\Enums\ProductEventName;
use App\Http\Requests\StoreProjectStackObservationRequest;
use App\Models\Project;
use App\Models\User;
use App\Services\GitHub\Exceptions\GitHubApiUnavailable;
use App\Services\GitHub\StackObservationService;
use App\Services\ProductEventRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class ProjectStackObservationController extends Controller
{
    public function __construct(private readonly ProductEventRecorder $productEvents) {}

    public function store(
        StoreProjectStackObservationRequest $request,
        Project $project,
        StackObservationService $observation,
    ): RedirectResponse {
        $this->productEvents->record(ProductEventName::StackObservationStarted, $request->user(), $project);

        try {
            $result = $observation->observe($project);
        } catch (GitHubApiUnavailable) {
            $this->recordFailure($request->user(), $project, 'github_unavailable');

            throw ValidationException::withMessages([
                'github' => __('GitHub could not be reached. Try again in a moment.'),
            ]);
        }

        if (! $result->succeeded()) {
            $this->recordFailure($request->user(), $project, (string) $result->failureReason?->value);

            throw ValidationException::withMessages([
                'github' => $result->failureReason?->label() ?? __('The repository could not be observed.'),
            ]);
        }

        $this->productEvents->record(
            ProductEventName::StackObserved,
            $request->user(),
            $project,
            ['observed' => $result->observed],
        );

        return back();
    }

    private function recordFailure(?User $user, Project $project, string $reason): void
    {
        $this->productEvents->record(ProductEventName::StackObservationFailed, $user, $project, [
            'reason' => $reason,
        ]);
    }
}
