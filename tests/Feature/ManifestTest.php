<?php

use App\Models\Cheer;
use App\Models\Project;
use App\Models\Release;
use App\Models\Tag;
use App\Models\User;

function manifestableProject(): Project
{
    $project = Project::factory()
        ->filed()
        ->public()
        ->for(User::factory(), 'creator')
        ->create(['launch_date' => '2026-08-01']);
    Release::factory()->for($project)->create(['published_at' => now()]);
    $project->tags()->sync(Tag::factory()->count(4)->create()->pluck('id'));

    return $project;
}

test('a discoverable project serves its manifest as a self-contained SVG', function () {
    $project = manifestableProject();
    $cheerer = User::factory()->create();
    Cheer::factory()->create([
        'user_id' => $cheerer->id,
        'cheerable_type' => 'project',
        'cheerable_id' => $project->id,
    ]);

    $response = $this->get(route('manifests.show', [$project->creator, $project]));

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml');

    // Symfony normalizes the directive order; assert both tokens exist.
    expect($response->headers->get('Cache-Control'))
        ->toContain('public')
        ->toContain('max-age=300');

    $svg = $response->getContent();
    expect($svg)
        ->toContain('SHIPPED')
        ->toContain(strtoupper(mb_substr($project->name, 0, 30)))
        ->toContain(mb_substr($project->tagline, 0, 110))
        ->toContain('@'.strtoupper($project->creator->username))
        ->toContain('VERIFIED LIVE')
        ->toContain('LAUNCHED')
        ->toContain('FIRST CHEER FROM @'.strtoupper($cheerer->username));
});

test('the manifest shows the docket serial and at most three stack tags', function () {
    $project = manifestableProject();
    $project->forceFill(['filed_number' => 42, 'filed_at' => now()])->save();

    $svg = $this->get(route('manifests.show', [$project->creator, $project]))->getContent();

    expect($svg)->toContain('DISPATCH 0042');

    $renderedTags = collect($project->tags->take(3))
        ->map(fn (Tag $tag) => '+ '.strtoupper(mb_substr($tag->name, 0, 16)));
    foreach ($renderedTags as $rendered) {
        expect($svg)->toContain($rendered);
    }
    $fourthTag = strtoupper(mb_substr($project->tags->get(3)->name, 0, 16));
    expect($svg)->not->toContain('+ '.$fourthTag);
});

test('the manifest omits the first cheer line when no cheers exist', function () {
    $project = manifestableProject();

    $svg = $this->get(route('manifests.show', [$project->creator, $project]))->getContent();

    expect($svg)->not->toContain('FIRST CHEER FROM');
});

test('private and unpublished projects return 404 for their manifest', function () {
    $creator = User::factory()->create();

    $private = Project::factory()->verified()->filed()->for($creator, 'creator')->create(['is_public' => false]);
    $unpublished = Project::factory()->verified()->filed()->for($creator, 'creator')->create();

    $this->get(route('manifests.show', [$private->creator, $private]))->assertNotFound();
    $this->get(route('manifests.show', [$unpublished->creator, $unpublished]))->assertNotFound();
});

test('a manifest for another creators slug combination returns 404', function () {
    $project = manifestableProject();
    $other = User::factory()->create();

    $this->get(route('manifests.show', [$other, $project]))->assertNotFound();
});
