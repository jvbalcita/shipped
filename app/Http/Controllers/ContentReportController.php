<?php

namespace App\Http\Controllers;

use App\Enums\ContentReportResolution;
use App\Enums\ProductEventName;
use App\Http\Requests\StoreContentReportRequest;
use App\Http\Requests\UpdateContentReportRequest;
use App\Models\Comment;
use App\Models\ContentReport;
use App\Models\Project;
use App\Models\Review;
use App\Services\ProductEventRecorder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContentReportController extends Controller
{
    /**
     * How many excerpt characters the queue shows per reported subject.
     */
    private const int EXCERPT_LENGTH = 180;

    public function __construct(private readonly ProductEventRecorder $productEvents) {}

    /**
     * Store a builder's report against a project, comment, or review.
     * The queue is the record — reports are never shown to the reported
     * creator and carry no automatic enforcement.
     */
    public function store(StoreContentReportRequest $request): RedirectResponse
    {
        $reportable = $request->reportable();
        abort_unless($reportable !== null, 404);

        $report = ContentReport::query()->create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => $reportable->getMorphClass(),
            'reportable_id' => $reportable->getKey(),
            'reason' => $request->validated('reason'),
            'note' => $request->validated('note'),
        ]);

        $this->productEvents->record(
            ProductEventName::ContentReportSubmitted,
            $request->user(),
            $report,
            ['reason' => $report->reason->value, 'subject' => $report->reportable_type],
        );

        return back();
    }

    /**
     * The curator queue: every open report with enough context to decide —
     * what was reported, why, by whom, and where it lives publicly.
     */
    public function index(): Response
    {
        $reports = ContentReport::query()
            ->with(['reportable' => fn ($morphTo) => $morphTo->morphWith([
                Project::class => ['creator'],
                Comment::class => ['project.creator'],
                Review::class => ['project.creator'],
            ]), 'reporter:id,username'])
            ->open()
            ->oldest()
            ->get()
            ->map(fn (ContentReport $report): array => $this->shapeForQueue($report))
            ->values()
            ->all();

        return Inertia::render('Reports/Index', [
            'reports' => $reports,
        ]);
    }

    /**
     * Resolve a report. Resolution is explicit — dismissal and action taken
     * are different outcomes — but it never auto-enforces anything; the
     * curator acts on the underlying content with the existing tools.
     */
    public function update(UpdateContentReportRequest $request, ContentReport $report): RedirectResponse
    {
        // Rule::enum validated data can be a string or the enum instance
        // depending on validation path; normalize before it reaches JSON.
        $resolution = $request->validated('resolution');
        $resolutionValue = $resolution instanceof ContentReportResolution
            ? $resolution->value
            : (string) $resolution;

        $report->update([
            'resolution' => $resolutionValue,
            'resolution_note' => $request->validated('resolution_note'),
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        $this->productEvents->record(
            ProductEventName::ContentReportResolved,
            $request->user(),
            $report,
            ['resolution' => $resolutionValue, 'reason' => $report->reason->value],
        );

        return back();
    }

    /**
     * One queue row: the report plus the subject context a curator needs
     * without leaving the page.
     *
     * @return array<string, mixed>
     */
    private function shapeForQueue(ContentReport $report): array
    {
        $subject = $report->reportable;

        return [
            'id' => $report->id,
            'reason' => $report->reason->value,
            'reason_label' => $report->reason->label(),
            'note' => $report->note,
            'created_at' => $report->created_at?->toIso8601String(),
            'reporter_username' => $report->reporter->username,
            'subject' => $this->shapeSubject($report, $subject),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function shapeSubject(ContentReport $report, Project|Comment|Review|null $subject): array
    {
        if ($subject instanceof Project) {
            return [
                'type' => 'project',
                'title' => $subject->name,
                'excerpt' => $subject->tagline,
                'url' => $subject->is_public
                    ? route('projects.show', [$subject->creator, $subject])
                    : null,
                'author_username' => $subject->creator->username,
                'context' => null,
                'live' => $subject->is_public,
            ];
        }

        if ($subject instanceof Comment) {
            $project = $subject->project;

            return [
                'type' => 'comment',
                'title' => $project->name,
                'excerpt' => str($subject->body)->limit(self::EXCERPT_LENGTH),
                'url' => route('projects.show', [$project->creator, $project]),
                'author_username' => $subject->user?->username,
                'context' => __('Comment on').' '.$project->name,
                'live' => ! $subject->isDeleted(),
            ];
        }

        if ($subject instanceof Review) {
            $project = $subject->project;

            return [
                'type' => 'review',
                'title' => $project->name,
                'excerpt' => str((string) $subject->body)->limit(self::EXCERPT_LENGTH),
                'url' => route('projects.show', [$project->creator, $project]),
                'author_username' => $subject->user?->username,
                'context' => __('Review of').' '.$project->name,
                'live' => true,
            ];
        }

        return [
            'type' => $report->reportable_type,
            'title' => __('Removed content'),
            'excerpt' => null,
            'url' => null,
            'author_username' => null,
            'context' => null,
            'live' => false,
        ];
    }
}
