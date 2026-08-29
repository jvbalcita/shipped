<?php

use App\Enums\ProductEventName;
use App\Models\Comment;
use App\Models\ContentReport;
use App\Models\ProductEvent;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Response;
use Inertia\Testing\AssertableInertia as Assert;

function reportableProject(): Project
{
    return Project::factory()->public()->for(User::factory()->create(), 'creator')->create();
}

function reportPayload(array $overrides = []): array
{
    return array_merge([
        'reportable_type' => 'project',
        'reportable_id' => reportableProject()->id,
        'reason' => 'broken_link',
        'note' => 'The live URL no longer resolves.',
    ], $overrides);
}

test('guests cannot file reports', function () {
    $project = reportableProject();

    $this->post(route('reports.store'), reportPayload(['reportable_id' => $project->id]))
        ->assertRedirect(route('login'));
});

test('a signed-in builder can report a project and the report lands in the queue', function () {
    $project = reportableProject();
    $reporter = verifiedUser();

    $this->actingAs($reporter)
        ->post(route('reports.store'), reportPayload(['reportable_id' => $project->id]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $report = ContentReport::query()->sole();

    expect($report->reporter_id)->toBe($reporter->id)
        ->and($report->reportable_id)->toBe($project->id)
        ->and($report->reportable_type)->toBe((new Project)->getMorphClass())
        ->and($report->reason->value)->toBe('broken_link')
        ->and($report->isOpen())->toBeTrue();
});

test('reporting records a server-side product event', function () {
    $this->actingAs(verifiedUser())
        ->post(route('reports.store'), reportPayload());

    expect(ProductEvent::query()->where('name', ProductEventName::ContentReportSubmitted->value)->count())->toBe(1);
});

test('a project report includes queue context for curators', function () {
    $project = reportableProject();
    $this->actingAs(verifiedUser())
        ->post(route('reports.store'), reportPayload(['reportable_id' => $project->id]));

    actingAsCurator()
        ->get(route('reports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Index')
            ->has('reports', 1)
            ->where('reports.0.reason', 'broken_link')
            ->where('reports.0.subject.type', 'project')
            ->where('reports.0.subject.title', $project->name)
        );
});

test('each reported content type surfaces its own context', function () {
    $project = reportableProject();
    $comment = Comment::factory()->for($project)->for(User::factory()->create(), 'user')->create();
    $review = Review::factory()->for($project)->for(User::factory()->create(), 'user')->create();
    $reporter = verifiedUser();

    $this->actingAs($reporter)
        ->post(route('reports.store'), reportPayload([
            'reportable_type' => 'comment',
            'reportable_id' => $comment->id,
            'reason' => 'spam',
            'note' => null,
        ]));

    $this->actingAs($reporter)
        ->post(route('reports.store'), reportPayload([
            'reportable_type' => 'review',
            'reportable_id' => $review->id,
            'reason' => 'inappropriate',
            'note' => null,
        ]));

    actingAsCurator()
        ->get(route('reports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('reports', 2)
            ->where('reports.0.subject.type', 'comment')
            ->where('reports.0.subject.title', $project->name)
            ->where('reports.1.subject.type', 'review')
        );
});

test('the reasons list is enforced', function () {
    $this->actingAs(verifiedUser())
        ->post(route('reports.store'), reportPayload(['reason' => 'because']))
        ->assertSessionHasErrors('reason');
});

test('a note is required only for the other reason', function () {
    $this->actingAs(verifiedUser())
        ->post(route('reports.store'), reportPayload(['reason' => 'other', 'note' => null]))
        ->assertSessionHasErrors('note');

    $project = reportableProject();

    $this->actingAs(verifiedUser())
        ->post(route('reports.store'), reportPayload([
            'reportable_id' => $project->id,
            'reason' => 'spam',
            'note' => null,
        ]))
        ->assertSessionHasNoErrors();
});

test('reports target only known content types', function () {
    $this->actingAs(verifiedUser())
        ->post(route('reports.store'), reportPayload(['reportable_type' => 'user']))
        ->assertSessionHasErrors('reportable_type');
});

test('reporting missing or hidden content fails validation', function () {
    $this->actingAs(verifiedUser())
        ->post(route('reports.store'), reportPayload(['reportable_id' => 99999]))
        ->assertSessionHasErrors('reportable_id');

    $private = Project::factory()->create(['is_public' => false]);

    $this->actingAs(verifiedUser())
        ->post(route('reports.store'), reportPayload(['reportable_id' => $private->id]))
        ->assertSessionHasErrors('reportable_id');

    $comment = Comment::factory()->for(reportableProject())->for(User::factory()->create(), 'user')->create(['deleted_at' => now()]);

    $this->actingAs(verifiedUser())
        ->post(route('reports.store'), reportPayload([
            'reportable_type' => 'comment',
            'reportable_id' => $comment->id,
        ]))
        ->assertSessionHasErrors('reportable_id');
});

test('builders cannot report their own content', function () {
    $creator = verifiedUser();
    $project = Project::factory()->public()->for($creator, 'creator')->create();

    $this->actingAs($creator)
        ->post(route('reports.store'), reportPayload(['reportable_id' => $project->id]))
        ->assertForbidden();

    expect(ContentReport::query()->count())->toBe(0);
});

test('a reporter gets one open report per subject but may report again after resolution', function () {
    $project = reportableProject();
    $reporter = verifiedUser();
    $payload = reportPayload(['reportable_id' => $project->id]);

    $this->actingAs($reporter)->post(route('reports.store'), $payload)->assertRedirect();

    $this->actingAs($reporter)
        ->post(route('reports.store'), $payload)
        ->assertSessionHasErrors('reportable_id');

    $report = ContentReport::query()->sole();
    $report->update(['resolution' => 'no_action', 'resolved_at' => now(), 'resolved_by' => curator()->id]);

    $this->actingAs($reporter)
        ->post(route('reports.store'), $payload)
        ->assertSessionHasNoErrors();

    expect(ContentReport::query()->count())->toBe(2);
});

test('the reports queue is curator-only', function () {
    $this->actingAs(verifiedUser())
        ->get(route('reports.index'))
        ->assertForbidden();

    expect(actingAsCurator()->get(route('reports.index'))->status())->toBe(Response::HTTP_OK);
});

test('a curator resolves a report with an explicit outcome and event', function () {
    $this->actingAs(verifiedUser())->post(route('reports.store'), reportPayload());
    $report = ContentReport::query()->sole();

    actingAsCurator()
        ->patch(route('reports.update', $report), [
            'resolution' => 'action_taken',
            'resolution_note' => 'Project withdrawn pending review.',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $report->refresh();

    expect($report->resolution?->value)->toBe('action_taken')
        ->and($report->resolved_at)->not->toBeNull()
        ->and($report->isOpen())->toBeFalse()
        ->and(ProductEvent::query()->where('name', ProductEventName::ContentReportResolved->value)->count())->toBe(1);
});

test('resolution requires a known outcome and is curator-only', function () {
    $this->actingAs(verifiedUser())->post(route('reports.store'), reportPayload());
    $report = ContentReport::query()->sole();

    $this->actingAs(verifiedUser())
        ->patch(route('reports.update', $report), ['resolution' => 'no_action'])
        ->assertForbidden();

    actingAsCurator()
        ->patch(route('reports.update', $report), ['resolution' => 'whatever'])
        ->assertSessionHasErrors('resolution');
});

test('curators can remove reported comments and reviews through the existing flows', function () {
    $project = reportableProject();
    $author = verifiedUser();
    $comment = Comment::factory()->for($project)->for($author, 'user')->create();
    $review = Review::factory()->for($project)->for($author, 'user')->create();

    actingAsCurator()
        ->delete(route('projects.comments.destroy', [$project, $comment]))
        ->assertRedirect();

    expect($comment->fresh())->toBeNull();

    actingAsCurator()
        ->delete(route('projects.reviews.destroy', [$project, $review]))
        ->assertRedirect();

    expect(Comment::query()->count())->toBe(0)
        ->and(Review::query()->count())->toBe(0);
});

test('report filing is rate limited', function () {
    $reporter = verifiedUser();
    $payloadFor = fn (): array => reportPayload();

    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($reporter)
            ->post(route('reports.store'), $payloadFor())
            ->assertRedirect();
    }

    $this->actingAs($reporter)
        ->post(route('reports.store'), $payloadFor())
        ->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
});
