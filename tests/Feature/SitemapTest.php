<?php

use App\Models\Project;
use App\Models\Release;
use App\Models\User;

test('the sitemap contains only canonical public record URLs', function () {
    $creator = User::factory()->create(['username' => 'sitemap_builder']);
    $project = Project::factory()->public()->for($creator, 'creator')->create([
        'slug' => 'public-sitemap-project',
    ]);
    $release = Release::factory()->for($project)->create(['published_at' => now()->subMinute()]);
    $releaseRoute = route('releases.show', [
        'creator' => $creator,
        'project' => $project,
        'release' => $release,
    ]);

    $privateCreator = User::factory()->create(['username' => 'private_builder']);
    $privateProject = Project::factory()->for($privateCreator, 'creator')->create([
        'slug' => 'private-sitemap-project',
    ]);
    Release::factory()->for($privateProject)->create(['published_at' => now()->subMinute()]);

    $response = $this->get('/sitemap.xml')
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    $document = new DOMDocument;

    expect($document->loadXML((string) $response->getContent()))->toBeTrue();

    $response->assertSee('<urlset', false)
        ->assertSee(route('home'), false)
        ->assertSee(route('discover'), false)
        ->assertSee(route('creators.show', $creator), false)
        ->assertSee(route('projects.show', ['creator' => $creator, 'project' => $project]), false)
        ->assertSee($releaseRoute, false)
        ->assertDontSee(route('creators.show', $privateCreator), false)
        ->assertDontSee($privateProject->slug, false);
});

test('robots advertises the absolute sitemap URL', function () {
    $response = $this->get('/robots.txt')
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('User-agent: *', false)
        ->assertSee('Disallow:', false)
        ->assertSee('Sitemap: '.url('/sitemap.xml'), false);

    expect($response->headers->get('Cache-Control'))
        ->toContain('public')
        ->toContain('max-age=300')
        ->toContain('must-revalidate');
});
