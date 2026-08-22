# Laravel Cloud Verification MVP Implementation Plan

> Historical: this plan is the original API-token verification MVP. Current vision and the live verification contract are in [ADR 0001](../../adr/0001-verify-projects-through-laravel-cloud.md).

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make Shipped a verified Laravel Cloud registry: creators connect one Laravel Cloud API token, bind a Project to a Cloud environment, verify its hostname, and publish only after a published Release and current verification exist.

**Architecture:** Add a one-to-one `CloudConnection` owned by `User` and a `ConnectedEnvironment` catalog owned by that connection. `Project` selects one connected environment; a read-only Laravel Cloud HTTP client validates the token and refreshes environment domains. Verification state is enforced at every public query and publication boundary, while scheduled commands refresh verification daily and publish due Releases only when verification remains current.

**Tech Stack:** Laravel 13, PHP 8.5, Inertia v3, Vue 3 `<script setup>`, Tailwind v4, shadcn-vue primitives, PostgreSQL-compatible migrations, Laravel HTTP client, Laravel scheduler, Pest 4.

## Global Constraints

- Laravel Cloud integration is read-only: list and inspect applications/environments/domains only; never deploy, restart, or mutate Cloud resources.
- Store a Creator's API token only through Laravel's `encrypted` cast; never serialize, log, or return it to the browser after submission.
- A Creator owns exactly one Cloud Connection; a Project selects at most one Connected Environment.
- A Project is publicly discoverable only when `is_public` is true, it has a published Release, and its Verification State is `verified`.
- Normalize hostnames by lowercasing and removing scheme, path, query, fragment, port, trailing dot, and one leading `www.` before exact comparison.
- A changed live URL or Connected Environment, failed recheck, stale recheck, or disconnect makes affected Projects private. Never republish automatically after verification returns.
- Due scheduled Releases may publish only when their Project remains verified; otherwise retain the release and leave the Project private.
- Local/test Demo Launches use a distinct `demo` Verification State and **Demo record** UI label. Production has no demo seed data and accepts only verified Projects.
- Use existing policies, Form Requests, Wayfinder routes, Inertia `useForm`, and customized shadcn-vue controls. Add no dependencies.

---

## File Map

| Path | Responsibility |
| --- | --- |
| `database/migrations/*_create_cloud_connections_table.php` | One encrypted Laravel Cloud credential per Creator and connection health metadata. |
| `database/migrations/*_create_connected_environments_table.php` | API-derived application/environment IDs, labels, normalized domains, and sync metadata. |
| `database/migrations/*_add_verification_fields_to_projects_table.php` | Project-to-environment binding, verification check timestamp/reason, and development-only demo marker. |
| `app/Models/CloudConnection.php` | One-to-one user connection; encrypted, hidden token and connected environments relation. |
| `app/Models/ConnectedEnvironment.php` | API-derived evidence record and projects relation. |
| `app/Services/LaravelCloud/LaravelCloudClient.php` | Read-only HTTP access to `/applications`, `/applications/{id}/environments`, and `/environments/{id}`. |
| `app/Services/LaravelCloud/HostnameNormalizer.php` | Canonical host comparison shared by verification and tests. |
| `app/Services/LaravelCloud/ProjectVerificationService.php` | Bind/reverify, status transitions, visibility withdrawal, and environment synchronization. |
| `app/Http/Controllers/CloudConnectionController.php` | Connect, replace token, and disconnect endpoints. |
| `app/Http/Controllers/ProjectVerificationController.php` | Bind/reverify a Project against a Connected Environment. |
| `app/Http/Controllers/ReleaseController.php` | Public, scoped standalone Release page. |
| `app/Http/Requests/StoreCloudConnectionRequest.php` | Token validation without exposing its value. |
| `app/Http/Requests/StoreProjectVerificationRequest.php` | Selected connected-environment validation and ownership authorization. |
| `app/Console/Commands/RefreshCloudVerifications.php` | Daily connection/environment refresh and verification recheck. |
| `app/Console/Commands/PublishScheduledReleases.php` | Every-minute release publication guarded by current verification. |
| `routes/web.php`, `routes/console.php` | Authenticated connection/verification routes, public release route, and schedules. |
| `resources/js/pages/Projects/Index.vue` | Creator Studio Cloud Connection panel and status/recovery action. |
| `resources/js/pages/Projects/Edit.vue` | Project Verification block, environment selection, reverify feedback, and publish gating. |
| `resources/js/pages/Releases/Show.vue` | Standalone public Release record. |
| `resources/js/pages/Projects/Show.vue` | Direct links to public Releases and verified/demo status presentation. |
| `resources/js/components/shipped/CloudConnectionPanel.vue` | Shared shadcn-vue connection/reconnect/disconnect UI. |
| `resources/js/components/shipped/VerificationPanel.vue` | Shared bound-environment selector and verification result UI. |
| `database/seeders/DatabaseSeeder.php` | Local/test-only Demo Launch policy; no production fake registry data. |
| `tests/Feature/CloudConnectionTest.php` | Token, environment catalog, disconnect, and authorization tests. |
| `tests/Feature/ProjectVerificationTest.php` | Host matching, state transitions, visibility rules, and discovery exclusions. |
| `tests/Feature/PublicReleaseTest.php` | Scoped public Release URLs and private/scheduled access boundaries. |
| `tests/Feature/ScheduledPublicationTest.php` | Due release publication and verification-required scheduler behavior. |
| `README.md`, `CONTEXT.md`, `docs/adr/0001-verify-projects-through-laravel-cloud.md` | Operations, glossary, and decision record. |

---

### Task 1: Establish Cloud evidence persistence and model relationships

**Files:**
- Create: `database/migrations/*_create_cloud_connections_table.php`
- Create: `database/migrations/*_create_connected_environments_table.php`
- Create: `database/migrations/*_add_verification_fields_to_projects_table.php`
- Create: `app/Models/CloudConnection.php`
- Create: `app/Models/ConnectedEnvironment.php`
- Modify: `app/Models/User.php`
- Modify: `app/Models/Project.php`
- Modify: `database/factories/ProjectFactory.php`
- Create: `database/factories/CloudConnectionFactory.php`
- Create: `database/factories/ConnectedEnvironmentFactory.php`
- Test: `tests/Feature/CloudConnectionTest.php`

**Interfaces:**
- Produces `User::cloudConnection(): HasOne`.
- Produces `CloudConnection::connectedEnvironments(): HasMany` with a unique `user_id` row.
- Produces `Project::connectedEnvironment(): BelongsTo` and the states `unverified`, `verified`, `failed`, `stale`, and `demo`.
- Produces `Project::isPubliclyDiscoverable(): bool` and `Project::withdrawFromPublicRegistry(): void`.

- [ ] **Step 1: Write the failing relationship and state test.**

```php
test('a creator owns one cloud connection and projects select connected environments', function () {
    $creator = User::factory()->create();
    $connection = CloudConnection::factory()->for($creator)->create();
    $environment = ConnectedEnvironment::factory()->for($connection)->create();
    $project = Project::factory()->for($creator, 'creator')->create([
        'connected_environment_id' => $environment->id,
        'verification_status' => 'verified',
        'verified_at' => now(),
    ]);

    expect($creator->cloudConnection->is($connection))->toBeTrue()
        ->and($project->connectedEnvironment->is($environment))->toBeTrue();
});
```

- [ ] **Step 2: Run the focused test and confirm it fails because the models and tables do not exist.**

Run: `php artisan test --compact tests/Feature/CloudConnectionTest.php`

Expected: failure referencing missing `CloudConnection`/`ConnectedEnvironment` classes or tables.

- [ ] **Step 3: Add PostgreSQL-compatible storage.**

Create `cloud_connections` with `user_id` unique foreign key, nullable encrypted-token column (`api_token`), string `status` defaulting to `disconnected`, nullable `last_validated_at`, nullable `last_error`, and timestamps. Create `connected_environments` with a cascade-deleting `cloud_connection_id`, external `application_id` and `environment_id`, application/environment names, `domains` JSON, nullable `synced_at`, timestamps, and a unique connection/environment pair. Add nullable `connected_environment_id` with `nullOnDelete()`, nullable `verification_checked_at`, nullable `verification_failure_reason`, and boolean `is_demo` default `false` to `projects`.

Use these model contracts:

```php
// CloudConnection casts
protected function casts(): array
{
    return [
        'api_token' => 'encrypted',
        'last_validated_at' => 'datetime',
    ];
}

// Project casts
protected function casts(): array
{
    return [
        'is_public' => 'boolean',
        'is_demo' => 'boolean',
        'verified_at' => 'datetime',
        'verification_checked_at' => 'datetime',
    ];
}
```

Keep `api_token` in `$hidden`; do not add it to any Inertia response. Add `cloudConnection()` to `User`, `connectedEnvironments()` to `CloudConnection`, and `connectedEnvironment()` to `Project`.

- [ ] **Step 4: Add the public-state guard on Project.**

```php
public function isPubliclyDiscoverable(): bool
{
    return $this->is_public
        && $this->verification_status === 'verified'
        && $this->releases()->published()->exists();
}

public function withdrawFromPublicRegistry(): void
{
    $this->forceFill(['is_public' => false])->save();
}
```

Use a query scope for production discovery that requires `is_public = true`, `verification_status = verified`, and a published release. Keep demo handling in the seeder/controller boundary, never in production scope.

- [ ] **Step 5: Run focused tests and migrations.**

Run:

```bash
php artisan migrate:fresh --seed
php artisan test --compact tests/Feature/CloudConnectionTest.php
```

Expected: migrations succeed and the new relation/state test passes.

- [ ] **Step 6: Commit the persistence slice.**

```bash
git add app/Models database/factories database/migrations tests/Feature/CloudConnectionTest.php
git commit -m "feat(cloud): add connection and environment evidence models"
```

### Task 2: Build the read-only Laravel Cloud client and deterministic hostname verification

**Files:**
- Create: `app/Services/LaravelCloud/LaravelCloudClient.php`
- Create: `app/Services/LaravelCloud/HostnameNormalizer.php`
- Create: `app/Services/LaravelCloud/CloudEnvironmentData.php`
- Test: `tests/Feature/CloudConnectionTest.php`

**Interfaces:**
- Consumes an unencrypted token only inside service methods.
- Produces `listApplications(string $token): array<int, array{id:string,name:string}>` and `listEnvironments(string $token, string $applicationId): array<int, CloudEnvironmentData>`.
- Produces `HostnameNormalizer::matches(string $projectUrl, array $domains): bool`.

- [ ] **Step 1: Write failing HTTP-faked tests.**

```php
Http::fake([
    'https://cloud.laravel.com/api/applications' => Http::response([
        'data' => [['id' => 'app-1', 'attributes' => ['name' => 'Shipped']]],
    ]),
]);

expect(app(LaravelCloudClient::class)->listApplications('secret-token'))
    ->toBe([['id' => 'app-1', 'name' => 'Shipped']]);

expect(HostnameNormalizer::matches(
    'https://www.shipped.test/pricing?source=registry',
    ['shipped.test'],
))->toBeTrue();
```

- [ ] **Step 2: Run the focused test and confirm it fails.**

Run: `php artisan test --compact --filter='Laravel Cloud client|hostname'`

Expected: failure because the client and normalizer are absent.

- [ ] **Step 3: Implement the read-only client.**

Use Laravel's HTTP client with `baseUrl('https://cloud.laravel.com/api')`, `acceptJson()`, and `withToken($token)`. Make only these GET requests:

```php
GET /applications
GET /applications/{application}/environments?include=primaryDomain
GET /environments/{environment}?include=primaryDomain
```

Map JSON:API resources into plain data values. Build each environment's domain array from its primary-domain relationship plus `attributes.vanity_domain` when present. On `401`/`403`, throw a dedicated `InvalidCloudToken` exception. On connection, timeout, `429`, or `5xx` failures, throw a retryable `CloudApiUnavailable` exception. Never include token values in exception messages.

- [ ] **Step 4: Implement exact normalized hostname matching.**

`HostnameNormalizer::normalize(string $urlOrHost): ?string` must use `parse_url`, lower-case the host, strip a single leading `www.`, strip a trailing dot, and return `null` for invalid/missing hosts. `matches()` returns true only when the normalized project host equals one normalized Cloud domain; subdomains do not match parent domains implicitly.

- [ ] **Step 5: Run focused client tests.**

Run: `php artisan test --compact tests/Feature/CloudConnectionTest.php`

Expected: application mapping, domain extraction, `www` equivalence, mismatch, invalid-token, and unavailable-API tests pass.

- [ ] **Step 6: Commit the client slice.**

```bash
git add app/Services tests/Feature/CloudConnectionTest.php
git commit -m "feat(cloud): add read-only Laravel Cloud client"
```

### Task 3: Add Cloud Connection and Connected Environment Studio workflow

**Files:**
- Create: `app/Http/Controllers/CloudConnectionController.php`
- Create: `app/Http/Controllers/ConnectedEnvironmentController.php`
- Create: `app/Http/Requests/StoreCloudConnectionRequest.php`
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/ProjectController.php`
- Create: `resources/js/components/shipped/CloudConnectionPanel.vue`
- Modify: `resources/js/pages/Projects/Index.vue`
- Modify: `resources/js/types/index.ts`
- Regenerate: Wayfinder route and action types
- Test: `tests/Feature/CloudConnectionTest.php`

**Interfaces:**
- `POST /cloud-connection` validates token, stores it encrypted, and syncs Connected Environments.
- `DELETE /cloud-connection` disconnects and withdraws all affected Projects.
- `GET /cloud-connection/environments` returns only the authenticated Creator's environment choices.
- Dashboard props expose connection health and safe environment metadata, never `api_token`.

- [ ] **Step 1: Write failing connection tests.**

```php
test('a creator can connect Laravel Cloud only after the token validates', function () {
    Http::fake(['https://cloud.laravel.com/api/applications' => Http::response(['data' => []])]);

    $this->actingAs(User::factory()->create())
        ->post(route('cloud-connection.store'), ['api_token' => 'cloud-token'])
        ->assertRedirect(route('dashboard'));

    expect(CloudConnection::firstOrFail()->api_token)->toBe('cloud-token');
});

test('disconnect withdraws affected projects and deletes cloud evidence', function () {
    // Create verified public project bound to the creator's connection.
    // Delete the connection route and assert project is private/unverified.
});
```

- [ ] **Step 2: Run the focused tests and confirm they fail.**

Run: `php artisan test --compact tests/Feature/CloudConnectionTest.php`

Expected: missing routes/controllers.

- [ ] **Step 3: Implement server-side connection actions.**

`StoreCloudConnectionRequest` validates `api_token` as required string with a safe maximum length. `CloudConnectionController::store()` calls `LaravelCloudClient::listApplications()` before creating/updating the connection. On success, persist the encrypted token, status `connected`, and `last_validated_at`; sync all available application environments through `updateOrCreate()` using external environment ID. On invalid token, return a `ValidationException` on `api_token`; on unavailable API, return a connection-level error without persisting a replacement token.

For disconnect, run one transaction: set every Project joined through the connection's environments to `is_public = false`, `verification_status = unverified`, `verified_at = null`, `verification_checked_at = now()`, and `verification_failure_reason = 'Laravel Cloud connection removed.'`; then delete the connection so token and environments cascade-delete. Preserve Projects and Releases.

- [ ] **Step 4: Expose safe Studio props and build the shadcn-vue panel.**

Add `cloudConnection` and `connectedEnvironments` to `ProjectController::index()`. `CloudConnectionPanel.vue` uses `useForm`, shadcn `Field`, `Input`, `Alert`, `Button`, and `AlertDialog`:

```ts
const form = useForm({ api_token: '' });

function connect(): void {
    form.post(store().url, { onSuccess: () => form.reset() });
}
```

The disconnected state explains where to create a Laravel Cloud API token and offers Connect. The connected state shows last validation time, environment count, Replace token, and a destructive Disconnect confirmation. Do not prefill or render the token.

- [ ] **Step 5: Regenerate Wayfinder and verify UI types.**

Run:

```bash
php artisan wayfinder:generate --with-form --no-interaction
npm run types:check
```

Expected: generated routes/actions exist and Vue type checking passes.

- [ ] **Step 6: Commit the Studio connection slice.**

```bash
git add app/Http app/Models resources/js routes tests/Feature
git commit -m "feat(cloud): connect Laravel Cloud in Creator Studio"
```

### Task 4: Bind Projects to environments, verify them, and enforce verified-only publication

**Files:**
- Create: `app/Http/Controllers/ProjectVerificationController.php`
- Create: `app/Http/Requests/StoreProjectVerificationRequest.php`
- Create: `app/Services/LaravelCloud/ProjectVerificationService.php`
- Modify: `app/Http/Controllers/ProjectController.php`
- Modify: `app/Http/Controllers/ProjectVisibilityController.php`
- Modify: `app/Http/Controllers/DiscoverController.php`
- Modify: `app/Http/Controllers/CreatorController.php`
- Modify: `app/Models/Project.php`
- Modify: `routes/web.php`
- Create: `resources/js/components/shipped/VerificationPanel.vue`
- Modify: `resources/js/pages/Projects/Edit.vue`
- Modify: `resources/js/pages/Projects/Show.vue`
- Modify: `resources/js/components/shipped/ProjectCard.vue`
- Test: `tests/Feature/ProjectVerificationTest.php`

**Interfaces:**
- `POST /projects/{project}/verification` accepts `connected_environment_id` owned by the Project creator's Cloud Connection.
- `ProjectVerificationService::verify(Project $project, ConnectedEnvironment $environment): Project` is the only write path for verified/failed/stale state transitions.
- Public discovery, creator profiles, and public project pages use the same `Project::scopeDiscoverable()` predicate.

- [ ] **Step 1: Write failing verification and visibility tests.**

```php
test('a matching Cloud hostname verifies a project but does not publish it', function () {
    Http::fake(['https://cloud.laravel.com/api/environments/env-1*' => Http::response(environmentPayload(['shipped.test']))]);

    $this->actingAs($creator)
        ->post(route('projects.verification.store', $project), [
            'connected_environment_id' => $environment->id,
        ])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh())
        ->verification_status->toBe('verified')
        ->is_public->toBeFalse();
});

test('an unverified project cannot become public even with a published release', function () {
    $this->actingAs($creator)
        ->patch(route('projects.visibility.update', $project), ['is_public' => true])
        ->assertSessionHasErrors('is_public');
});
```

- [ ] **Step 2: Run the verification file and confirm failure.**

Run: `php artisan test --compact tests/Feature/ProjectVerificationTest.php`

Expected: missing verification route/service and public-visibility assertion failure.

- [ ] **Step 3: Implement `ProjectVerificationService`.**

The service must load the environment using the connection token, refresh its stored domains, and compare them with `project.live_url`. Status transition rules:

```php
// Match
['verification_status' => 'verified', 'verified_at' => now(), 'verification_checked_at' => now(), 'verification_failure_reason' => null]

// Host mismatch
['is_public' => false, 'verification_status' => 'failed', 'verification_checked_at' => now(), 'verification_failure_reason' => 'The live URL does not match the selected Laravel Cloud environment.']

// API unavailable
['is_public' => false, 'verification_status' => 'stale', 'verification_checked_at' => now(), 'verification_failure_reason' => 'Laravel Cloud could not be reached.']
```

On a changed `live_url` or `connected_environment_id` in `UpdateProjectRequest` handling, call a single `invalidate(Project $project, string $reason): void` method that sets `is_public` false, state `unverified`, clears `verified_at`, and records the reason. Do this after a successful project update; do not invalidate for unrelated title/category/cover edits.

- [ ] **Step 4: Enforce publication and public reads.**

`ProjectVisibilityController::update()` must require both `releases()->published()->exists()` and `verification_status === 'verified'` before setting `is_public` true. Replace ad-hoc public filters in Discover, Creator, and Project show with a shared discoverable scope. The owner may still view their own non-public project in Studio; guests receive 404 for a non-discoverable Project.

- [ ] **Step 5: Implement the Verification panel.**

Render the Creator-owned environment options from server props. Use shadcn `Select`, `Field`, `Alert`, `Button`, and `Toast` feedback. Labels must be exact:

- disconnected: `Connect Laravel Cloud to verify this project.`
- unverified: `Select an environment, then verify the live URL.`
- verified: `Verified against {environment_name} on {verified_at}.`
- failed: show the stored mismatch reason and `Verify again`.
- stale: show the connection failure reason and `Reconnect Cloud` / `Verify again`.

Disable **Publish project** unless a published release exists and the state is `verified`.

- [ ] **Step 6: Run focused tests and frontend checks.**

Run:

```bash
php artisan test --compact tests/Feature/ProjectVerificationTest.php
npm run lint:check
npm run types:check
```

Expected: match/mismatch, invalidation, unauthorized environment selection, verified-only discovery, and publication gating pass.

- [ ] **Step 7: Commit the verification slice.**

```bash
git add app/Http app/Models app/Services resources/js routes tests/Feature
git commit -m "feat(verification): require Cloud evidence for publication"
```

### Task 5: Add standalone public Release records

**Files:**
- Create: `app/Http/Controllers/ReleaseController.php`
- Modify: `routes/web.php`
- Create: `resources/js/pages/Releases/Show.vue`
- Modify: `resources/js/pages/Projects/Show.vue`
- Regenerate: Wayfinder routes/actions
- Test: `tests/Feature/PublicReleaseTest.php`

**Interfaces:**
- Produces public GET route `/@{creator:handle}/{project:slug}/releases/{release}` with scoped binding.
- `ReleaseController::show(User $creator, Project $project, Release $release): Response` returns a public Release only when the Project is discoverable and the Release is published.

- [ ] **Step 1: Write failing public-release tests.**

```php
test('a published release has a creator-scoped public record', function () {
    $this->get(route('releases.show', compact('creator', 'project', 'release')))
        ->assertSuccessful()
        ->assertSee($release->title);
});

test('a scheduled release and a release from another project return 404', function () {
    $this->get(route('releases.show', compact('creator', 'project', 'scheduledRelease')))->assertNotFound();
    $this->get(route('releases.show', ['creator' => $creator, 'project' => $project, 'release' => $otherRelease]))->assertNotFound();
});
```

- [ ] **Step 2: Run and confirm the route is missing.**

Run: `php artisan test --compact tests/Feature/PublicReleaseTest.php`

Expected: route-not-found failure.

- [ ] **Step 3: Implement the scoped controller and page.**

Place the three-segment release route before the two-segment public project route and call `scopeBindings()`. In the controller, abort 404 unless `$project->creator->is($creator)`, `$release->project->is($project)`, `$project->isPubliclyDiscoverable()`, and `$release->published_at?->isPast()`.

`Releases/Show.vue` uses `PublicShell`, shows the Project title/category, release date/title/notes, creator link, project link, live product link, and a clear **Back to project** action. In `Projects/Show.vue`, make each release title a Wayfinder `<Link>` to its public record.

- [ ] **Step 4: Regenerate routes and run focused tests.**

Run:

```bash
php artisan wayfinder:generate --with-form --no-interaction
php artisan test --compact tests/Feature/PublicReleaseTest.php
```

Expected: scoped route types generate; public, private, scheduled, and mismatched-project boundaries pass.

- [ ] **Step 5: Commit the release-page slice.**

```bash
git add app/Http/Controllers resources/js/pages/Releases resources/js/pages/Projects/Show.vue routes tests/Feature
git commit -m "feat(releases): add shareable public release records"
```

### Task 6: Automate daily rechecks and verified scheduled publication

**Files:**
- Create: `app/Console/Commands/RefreshCloudVerifications.php`
- Create: `app/Console/Commands/PublishScheduledReleases.php`
- Modify: `routes/console.php`
- Modify: `app/Services/LaravelCloud/ProjectVerificationService.php`
- Test: `tests/Feature/ScheduledPublicationTest.php`
- Test: `tests/Feature/ProjectVerificationTest.php`

**Interfaces:**
- `shipped:refresh-cloud-verifications` rechecks active connections daily.
- `shipped:publish-scheduled-releases` runs every minute and publishes only due Releases whose Projects are verified.

- [ ] **Step 1: Write failing scheduler behavior tests.**

```php
test('a due scheduled release publishes a verified project', function () {
    $this->artisan('shipped:publish-scheduled-releases')->assertSuccessful();

    expect($project->fresh()->is_public)->toBeTrue();
});

test('a due scheduled release leaves an unverified project private', function () {
    $this->artisan('shipped:publish-scheduled-releases')->assertSuccessful();

    expect($project->fresh()->is_public)->toBeFalse();
});
```

- [ ] **Step 2: Run and confirm commands do not exist.**

Run: `php artisan test --compact tests/Feature/ScheduledPublicationTest.php`

Expected: command-not-found failure.

- [ ] **Step 3: Implement recheck command.**

Select non-demo Cloud Connections with a token and iterate in chunks. Refresh their environments and call `ProjectVerificationService::verify()` for each Project bound to those environments. Invalid tokens set the connection `invalid` and make all bound Projects `failed` and private. Retryable API failures set bound Projects `stale` and private, preserving their `verified_at` timestamp. Continue processing other connections after an individual failure.

- [ ] **Step 4: Implement scheduled publication command.**

Select private Projects whose latest eligible Release has `published_at <= now()` and whose verification state is `verified`. Set only `is_public = true`; never change state or create a Release. For all other due projects, leave them private. Make the operation idempotent so repeated scheduler invocations do not change already-public Projects.

- [ ] **Step 5: Register scheduler frequency.**

In `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('shipped:refresh-cloud-verifications')->daily();
Schedule::command('shipped:publish-scheduled-releases')->everyMinute();
```

- [ ] **Step 6: Run scheduler tests.**

Run:

```bash
php artisan test --compact tests/Feature/ScheduledPublicationTest.php
php artisan test --compact tests/Feature/ProjectVerificationTest.php
```

Expected: verified due release publishes once; unverified/mismatched/stale projects remain private; daily recheck transitions are deterministic under `Http::fake()`.

- [ ] **Step 7: Commit automation.**

```bash
git add app/Console app/Services routes/console.php tests/Feature
git commit -m "feat(scheduler): refresh verification and publish due releases"
```

### Task 7: Make demo data and deployment behavior honest

**Files:**
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `app/Models/Project.php`
- Modify: `app/Http/Controllers/DiscoverController.php`
- Modify: `app/Http/Controllers/CreatorController.php`
- Modify: `app/Http/Controllers/ProjectController.php`
- Modify: `resources/js/components/shipped/ProjectCard.vue`
- Modify: `resources/js/pages/Projects/Show.vue`
- Modify: `README.md`
- Test: `tests/Feature/ProjectVerificationTest.php`

**Interfaces:**
- Development/test discovery may return explicitly marked `demo` Projects.
- Production discovery and public routes return only `verified` Projects.
- Deployment instructions seed neither fictional creators nor Demo Launches in production.

- [ ] **Step 1: Write failing environment-boundary tests.**

```php
test('production discovery excludes demo launches', function () {
    $this->app['env'] = 'production';
    $demo = Project::factory()->create([
        'is_public' => true,
        'is_demo' => true,
        'verification_status' => 'demo',
    ]);

    $this->get(route('discover'))->assertDontSee($demo->name);
});
```

- [ ] **Step 2: Run and confirm current public queries do not distinguish demos.**

Run: `php artisan test --compact --filter='production discovery excludes demo launches'`

Expected: failure because the existing public scope only checks `is_public`.

- [ ] **Step 3: Implement production-safe demo behavior.**

Add `Project::scopeDiscoverable(Builder $query): Builder`. In production, require `verification_status = verified` and `is_demo = false`. In local/test, permit `verification_status = demo` only where `is_demo = true`. Use this scope in Discover, Creator, project show, and public release show. Demo cards/pages must say **Demo record** and must not render `ShieldCheck` or “Verified Laravel Cloud.”

Seed Demo Launches only when `app()->environment(['local', 'testing'])`; set `is_demo = true`, `verification_status = demo`, and a published Release. Update production deployment documentation from `php artisan migrate --force --seed` to `php artisan migrate --force`; local setup retains `migrate --seed`.

- [ ] **Step 4: Run focused tests and manual seed checks.**

Run:

```bash
php artisan test --compact tests/Feature/ProjectVerificationTest.php
php artisan migrate:fresh --seed
php artisan db:seed --env=production --force
```

Expected: local seed shows clearly marked demos; production seed creates no fictional registry entries.

- [ ] **Step 5: Commit the honesty/deployment slice.**

```bash
git add app database resources/js README.md tests/Feature
git commit -m "fix(registry): isolate demo launches from production verification"
```

### Task 8: Run complete verification and prepare Laravel Cloud operations

**Files:**
- Modify: `README.md`
- Modify: `docs/adr/0001-verify-projects-through-laravel-cloud.md` only if implementation differs from the accepted decision

**Interfaces:**
- Deployment documentation instructs a Laravel Cloud scheduled task for `php artisan schedule:run` every minute and a worker only if queued work is introduced later.

- [ ] **Step 1: Document required runtime configuration.**

In `README.md`, document `APP_KEY`, `APP_ENV=production`, `APP_URL`, `DB_*`, `FILESYSTEM_DISK`, public object-storage configuration, and the Laravel Cloud scheduled task:

```bash
php artisan schedule:run
```

Configure it to run every minute so due releases publish on time; the daily Cloud recheck is dispatched by Laravel's scheduler. Do not document the Cloud token as an application `.env` value because it belongs to an encrypted creator-owned database record.

- [ ] **Step 2: Run all automated checks.**

Run:

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact
npm run lint:check
npm run types:check
npm run format:check
npm run build
git diff --check
```

Expected: all commands exit zero.

- [ ] **Step 3: Perform manual acceptance checks.**

1. Register or sign in as a Creator, connect a real Laravel Cloud API token, and confirm the token never appears after submit.
2. Select a real environment for a Project whose live hostname matches; verify, add a published Release, and publish it.
3. Confirm Discover, creator profile, project dossier, and standalone Release URL show the verified launch.
4. Change the Project live URL to a different hostname; confirm the Project immediately disappears from public views.
5. Reverify, then explicitly publish again.
6. Disconnect Cloud; confirm affected Projects become private while Studio retains their releases.
7. Test keyboard navigation and mobile layouts at 375px, 768px, 1024px, and 1440px.

- [ ] **Step 4: Commit documentation and final verification.**

```bash
git add README.md docs
git commit -m "docs(cloud): document verified registry operations"
```

## Self-Review

### Spec coverage

- Laravel Cloud project connection: Tasks 1–3.
- Verified Laravel Cloud badge: Task 4.
- Verified-only public/private visibility: Tasks 4 and 6.
- Community discovery, categories, Cheers, creator profile, search, live link, and cover image: retained; Task 4 upgrades their eligibility predicate without replacing their UI.
- Latest release page: Task 5.
- Scheduling and Laravel Cloud deployment readiness: Tasks 6 and 8.
- Fictional seed honesty: Task 7.

### Placeholder scan

The plan names all files, routes, state values, transition rules, commands, test targets, and verification commands. No implementation step relies on a future unspecified decision.

### Type consistency

`CloudConnection`, `ConnectedEnvironment`, `ProjectVerificationService`, `verification_status`, `connected_environment_id`, `scopeDiscoverable()`, `RefreshCloudVerifications`, and `PublishScheduledReleases` use the same names across persistence, controllers, UI, commands, and tests.
