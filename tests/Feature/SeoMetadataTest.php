<?php

use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('home exposes canonical registry metadata and a site share card', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('seo.title', 'Shipped — The verified launch registry for Laravel projects')
            ->where('seo.description', 'Discover verified Laravel projects and the people who ship them.')
            ->where('seo.canonical', route('home'))
            ->where('seo.robots', 'index,follow')
            ->where('seo.image', url('/og/site.svg'))
            ->where('seo.imageAlt', 'Shipped — The verified launch registry for Laravel projects')
            ->where('seo.jsonLd.0.@type', 'WebSite')
            ->where('seo.jsonLd.0.url', route('home')));
});

test('base discover is indexable while filtered discover is canonicalized and noindexed', function () {
    $this->get(route('discover'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Discover/Index')
            ->where('seo.title', 'Discover verified Laravel projects — Shipped')
            ->where('seo.canonical', route('discover'))
            ->where('seo.robots', 'index,follow'));

    $this->get(route('discover', ['q' => 'queues']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Discover/Index')
            ->where('seo.canonical', route('discover'))
            ->where('seo.robots', 'noindex,follow'));
});

test('a creator profile is indexable only when it has discoverable work', function () {
    $emptyCreator = User::factory()->create(['username' => 'empty_creator']);

    $this->get(route('creators.show', $emptyCreator))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo.robots', 'noindex,follow')
            ->where('seo.canonical', route('creators.show', $emptyCreator)));

    $creator = User::factory()->create(['username' => 'public_builder']);
    $project = Project::factory()->public()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()->subMinute()]);

    $this->get(route('creators.show', $creator))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo.title', ''.$creator->name.' (@public_builder) — Shipping Profile — Shipped')
            ->where('seo.robots', 'index,follow')
            ->where('seo.canonical', route('creators.show', $creator))
            ->where('seo.image', route('og.creator', $creator))
            ->where('seo.jsonLd.0.@type', 'ProfilePage'));
});

test('a discoverable project exposes record-backed metadata and structured data', function () {
    $creator = User::factory()->create(['username' => 'project_builder']);
    $project = Project::factory()->public()->for($creator, 'creator')->create([
        'name' => 'Queue Pilot',
        'tagline' => 'A calm command centre for Laravel queues.',
    ]);
    Release::factory()->for($project)->create(['published_at' => now()->subMinute()]);

    $this->get(route('projects.show', ['creator' => $creator, 'project' => $project]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo.title', 'Queue Pilot by @project_builder — Shipped')
            ->where('seo.description', 'A calm command centre for Laravel queues.')
            ->where('seo.canonical', route('projects.show', ['creator' => $creator, 'project' => $project]))
            ->where('seo.robots', 'index,follow')
            ->where('seo.image', route('og.project', ['creator' => $creator, 'project' => $project]))
            ->where('seo.jsonLd.0.@type', 'BreadcrumbList')
            ->where('seo.jsonLd.1.@type', 'SoftwareApplication'));
});

test('a published release exposes its own canonical record metadata', function () {
    $creator = User::factory()->create(['username' => 'release_builder']);
    $project = Project::factory()->public()->for($creator, 'creator')->create([
        'name' => 'Release Atlas',
    ]);
    $release = Release::factory()->for($project)->create([
        'title' => 'The first public dispatch',
        'notes' => 'A release record with a clear public changelog.',
        'published_at' => now()->subMinute(),
    ]);

    $routeParameters = [
        'creator' => $creator,
        'project' => $project,
        'release' => $release,
    ];

    $this->get(route('releases.show', $routeParameters))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo.title', 'The first public dispatch — Release Atlas — Shipped')
            ->where('seo.description', 'A release record with a clear public changelog.')
            ->where('seo.canonical', route('releases.show', $routeParameters))
            ->where('seo.image', url("/og/@release_builder/{$project->slug}/releases/{$release->id}.svg"))
            ->where('seo.jsonLd.0.@type', 'BreadcrumbList'));
});

test('the initial HTML head exposes canonical robots and structured data metadata', function () {
    $home = $this->get(route('home'));

    expect($home->getContent())
        ->toContain('name="description" content="Discover verified Laravel projects and the people who ship them."')
        ->toContain('name="robots" content="index,follow"')
        ->toContain('rel="canonical" href="'.route('home').'"')
        ->toContain('property="og:url" content="'.route('home').'"')
        ->toContain('<title data-inertia="">Shipped — The verified launch registry for Laravel projects</title>')
        ->toContain('type="application/ld+json"')
        ->toContain('"@type":"WebSite"');

    $filteredDiscover = $this->get(route('discover', ['q' => 'queues']));

    expect($filteredDiscover->getContent())
        ->toContain('name="robots" content="noindex,follow"')
        ->toContain('rel="canonical" href="'.route('discover').'"');
});
