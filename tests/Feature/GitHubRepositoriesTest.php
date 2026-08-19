<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use App\Services\GitHub\Exceptions\GitHubApiUnavailable;
use App\Services\GitHub\GitHubClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function githubRepoPayload(): array
{
    return [
        ['full_name' => 'maker/queue-pilot', 'html_url' => 'https://github.com/maker/queue-pilot'],
        ['full_name' => 'maker/field-notes', 'html_url' => 'https://github.com/maker/field-notes'],
    ];
}

function githubLinkedCreator(): User
{
    $creator = User::factory()->create();
    $creator->oauthAccounts()->create([
        'provider' => 'github',
        'provider_id' => '123',
        'provider_token' => 'ghp_token',
        'linked_at' => now(),
    ]);

    return $creator;
}

/**
 * Headers for a real Inertia first-load request.
 *
 * @return array<string, string>
 */
function inertiaHeaders(): array
{
    $version = (new HandleInertiaRequests)->version(new Request);

    return ['X-Inertia' => 'true', 'X-Inertia-Version' => $version];
}

/**
 * The partial-reload headers the client uses to fetch the deferred
 * githubRepos prop after first paint.
 *
 * @return array<string, string>
 */
function composerPartialHeaders(): array
{
    return inertiaHeaders() + [
        'X-Inertia-Partial-Component' => 'Projects/Create',
        'X-Inertia-Partial-Data' => 'githubRepos',
    ];
}

test('the github client maps the creators public repositories', function () {
    Http::fake([
        'https://api.github.com/user/repos*' => Http::response(githubRepoPayload()),
    ]);

    $repositories = app(GitHubClient::class)->listRepositories('ghp_token');

    expect($repositories)->toBe([
        ['name' => 'maker/queue-pilot', 'url' => 'https://github.com/maker/queue-pilot'],
        ['name' => 'maker/field-notes', 'url' => 'https://github.com/maker/field-notes'],
    ]);

    Http::assertSent(fn ($request): bool => $request->hasHeaders(['Authorization' => 'Bearer ghp_token'])
        && $request->url() === 'https://api.github.com/user/repos?visibility=public&affiliation=owner,collaborator,organization_member&sort=pushed&direction=desc&per_page=100');
});

test('the github client treats api failures as unavailable', function (int $status) {
    Http::fake([
        'https://api.github.com/user/repos*' => Http::response(status: $status),
    ]);

    expect(fn () => app(GitHubClient::class)->listRepositories('ghp_token'))
        ->toThrow(GitHubApiUnavailable::class);
})->with([[401], [403], [429], [500]]);

test('the launch composer receives the linked creators repositories', function () {
    Http::fake([
        'https://api.github.com/user/repos*' => Http::response(githubRepoPayload()),
    ]);

    $creator = githubLinkedCreator();

    $this->actingAs($creator)
        ->get(route('projects.create'), inertiaHeaders())
        ->assertOk()
        ->assertJsonPath('props.githubLinked', true)
        ->assertJsonPath('deferredProps.default.0', 'githubRepos');

    $this->actingAs($creator)
        ->get(route('projects.create'), composerPartialHeaders())
        ->assertOk()
        ->assertJsonPath('props.githubRepos', [
            ['name' => 'maker/queue-pilot', 'url' => 'https://github.com/maker/queue-pilot'],
            ['name' => 'maker/field-notes', 'url' => 'https://github.com/maker/field-notes'],
        ]);
});

test('repository lists are cached per creator between composer visits', function () {
    Http::fake([
        'https://api.github.com/user/repos*' => Http::response(githubRepoPayload()),
    ]);

    $creator = githubLinkedCreator();

    $this->actingAs($creator)->get(route('projects.create'), composerPartialHeaders())->assertOk();
    $this->actingAs($creator)->get(route('projects.create'), composerPartialHeaders())->assertOk();

    Http::assertSentCount(1);
});

test('the launch composer hides the picker when github is not linked', function () {
    $creator = User::factory()->create();

    $this->actingAs($creator)
        ->get(route('projects.create'), inertiaHeaders())
        ->assertOk()
        ->assertJsonPath('props.githubLinked', false);

    $this->actingAs($creator)
        ->get(route('projects.create'), composerPartialHeaders())
        ->assertOk()
        ->assertJsonPath('props.githubRepos', null);
});

test('the launch composer survives an unavailable github api', function () {
    Http::fake([
        'https://api.github.com/user/repos*' => Http::response(status: 500),
    ]);

    $this->actingAs(githubLinkedCreator())
        ->get(route('projects.create'), composerPartialHeaders())
        ->assertOk()
        ->assertJsonPath('props.githubRepos', null);
});
