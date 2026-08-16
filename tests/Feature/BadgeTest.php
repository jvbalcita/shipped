<?php

use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

function badgeableDemoProject(string $verificationStatus): Project
{
    test()->assertNotNull($verificationStatus);

    $project = Project::factory()
        ->demo()
        ->for(User::factory(), 'creator')
        ->create([
            'is_public' => true,
            'verification_status' => $verificationStatus,
        ]);

    Release::factory()->for($project)->create(['published_at' => now()]);

    return $project;
}

test('a discoverable verified project serves a verified-live badge as SVG', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->public()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()]);

    $response = $this->get(route('badges.show', $project));

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml');

    // Symfony normalizes the directive order; assert both tokens exist.
    expect($response->headers->get('Cache-Control'))
        ->toContain('public')
        ->toContain('max-age=300');

    expect($response->getContent())
        ->toContain('VERIFIED LIVE')
        ->toContain('#16a34a')
        ->toContain('SHIPPED');
});

test('each verification status renders its own badge label and color', function (string $status, string $label, string $color) {
    $project = badgeableDemoProject($status);

    $content = $this->get(route('badges.show', $project))->getContent();

    expect($content)->toContain($label)->toContain($color);
})->with([
    'stale' => ['stale', 'STALE', '#d97706'],
    'failed' => ['failed', 'VERIFICATION FAILED', '#dc2626'],
    'unverified' => ['unverified', 'UNVERIFIED', '#6b7280'],
]);

test('private and unpublished projects return 404 for their badge', function () {
    $creator = User::factory()->create();

    $private = Project::factory()->verified()->for($creator, 'creator')->create(['is_public' => false]);
    $unpublished = Project::factory()->for($creator, 'creator')->create(['is_public' => true, 'verification_status' => 'verified']);

    $this->get(route('badges.show', $private))->assertNotFound();
    $this->get(route('badges.show', $unpublished))->assertNotFound();
});

test('an unknown slug returns 404', function () {
    $this->get('/badges/does-not-exist.svg')->assertNotFound();
});

test('creator studio exposes the badge markdown snippet built from the app URL', function () {
    $creator = verifiedUser();
    $project = Project::factory()->public()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()]);

    $this->actingAs($creator)
        ->get(route('projects.edit', $project))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where(
                'badgeMarkdown',
                sprintf(
                    '[![Shipped](%s/badges/%s.svg)](%s/@%s/%s)',
                    rtrim(config('app.url'), '/'),
                    $project->slug,
                    rtrim(config('app.url'), '/'),
                    $creator->username,
                    $project->slug,
                ),
            ));
});

test('creator studio hides the badge snippet for non-discoverable projects', function () {
    $creator = verifiedUser();
    $project = Project::factory()->for($creator, 'creator')->create(['is_public' => false]);

    $this->actingAs($creator)
        ->get(route('projects.edit', $project))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('badgeMarkdown', null));
});

test('a guest cannot reach the studio badge affordance', function () {
    $project = Project::factory()->public()->for(User::factory(), 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()]);

    $this->get(route('projects.edit', $project))
        ->assertRedirect(route('login'));
});
