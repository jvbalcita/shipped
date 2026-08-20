# Laravel Cloud Verification Review Fixes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the six actionable review findings in the Laravel Cloud URL verification change without weakening the registry's identity, SSRF, migration, rate-limit, or rollout guarantees.

**Architecture:** Keep the current direct URL probe as the verification mechanism, but make verification a two-part decision: the submitted Cloud origin must match the project's normalized live hostname, and the origin must pass the existing DNS/HTTP probe. Harden the probe's address policy and fallback body handling, put a named rate limiter in front of the synchronous endpoint, and make the legacy-to-URL migration fail closed until every legacy project has been migrated or manually remediated.

**Tech Stack:** Laravel 13, PHP 8.5, Laravel HTTP client/Guzzle, Inertia v3, Vue 3, Pest 4, Laravel Sail, MySQL-compatible production migrations.

**Spec:** `docs/adr/0001-verify-projects-through-laravel-cloud.md`; the older MVP plan at `docs/superpowers/plans/2026-07-13-laravel-cloud-verification-mvp.md` is historical context for public-state and rollout rules, while the ADR is authoritative for the active direct-URL contract.

## Global Constraints

- Preserve ADR 0001's exact normalized-host comparison between `Project::live_url` and the Cloud evidence domain. A reachable but unrelated `*.laravel.cloud` host is never sufficient evidence.
- Normalize hostnames by lowercasing, removing one leading `www.`, and removing a trailing dot; ignore URL scheme, path, query, fragment, and port.
- A failed, stale, mismatched, unverified, or legacy-pending project is private and cannot be published. Successful verification never republishes automatically.
- The probe must never follow redirects, send credentials/cookies, request private or special-use address space, or buffer an unbounded fallback response.
- Use no new dependencies. Keep validation in Form Requests, authorization in existing policies, and the controller thin.
- The verification endpoint is expensive and must be limited per authenticated creator/project before the probe runs.
- The pending schema migration must be safely resumable after a partial MySQL DDL failure; validate it on the production database driver.
- Existing legacy Cloud connection/environment records remain available until the URL cutover is complete. Projects without URL evidence must fail closed rather than remain silently verified.
- Use `vendor/bin/sail` for Laravel commands, update focused Pest coverage, run Pint for PHP changes, and report unrelated frontend tooling failures separately.

## Review Finding Map

| Finding | Severity | Implementation task |
| --- | --- | --- |
| Any reachable Cloud URL can verify an unrelated project | P1 | Task 1: restore exact live-host binding |
| The additive migration is not resumable after partial MySQL DDL | P1 | Task 2: guard columns and index independently |
| PHP accepts RFC 6598 shared and RFC 2544 benchmarking addresses | P1 | Task 3: complete the non-public address policy |
| The fallback GET is buffered before the 64 KiB drain | P2 | Task 4: stream and close the response at the ceiling |
| Synchronous probes have no endpoint throttle | P2 | Task 5: add a named per-user/project limiter |
| Daily rechecks skip legacy token-backed projects | P1 | Task 6: fail-closed migration and cutover |

## File Map

| Path | Responsibility in this plan |
| --- | --- |
| `app/Services/LaravelCloud/ProjectVerificationService.php` | Reject mismatched Cloud/live hosts before probing; apply the same invariant during scheduled refresh. |
| `app/Services/LaravelCloud/HostnameNormalizer.php` | Remain the shared normalization boundary for project URLs and Cloud hosts. |
| `app/Services/LaravelCloud/LaravelCloudUrlProbe.php` | Reject missed special-use ranges and make the 405/501 fallback body genuinely streamed and bounded. |
| `database/migrations/2026_08_20_140022_add_cloud_url_verification_to_projects_table.php` | Make each additive column/index operation independently resumable. |
| `app/Providers/AppServiceProvider.php` | Register the named verification rate limiter. |
| `routes/web.php` | Apply the limiter before the verification controller. |
| `app/Console/Commands/RefreshCloudVerifications.php` | Include legacy evidence in the safety scan and invalidate projects that have not migrated. |
| `app/Console/Commands/BackfillCloudVerificationUrls.php` | Make `--apply` fail closed and make `--verify` the only path that restores verified state. |
| `tests/Feature/ProjectVerificationTest.php` | Cover identity binding, throttling, and legacy scheduled behavior. |
| `tests/Feature/BackfillCloudVerificationUrlsTest.php` | Cover safe backfill state transitions and cutover behavior. |
| `tests/Unit/Services/LaravelCloud/LaravelCloudUrlProbeTest.php` | Cover the complete address policy and bounded streamed fallback. |
| `tests/Feature/CloudUrlVerificationMigrationTest.php` | Reproduce a partial additive schema state and verify the migration can resume. |
| `tests/Feature/CloudConnectionTest.php` | Retain the existing hostname-normalization coverage and extend it only where the identity contract needs another case. |
| `docs/adr/0001-verify-projects-through-laravel-cloud.md` | Keep the verification contract and migration behavior explicit. |
| `README.md` | Document the required backfill/cutover commands and the fail-closed legacy outcome. |

---

### Task 1: Restore the project-to-Cloud identity binding

**Files:**
- Modify: `app/Services/LaravelCloud/ProjectVerificationService.php:34-89`
- Modify: `app/Services/LaravelCloud/HostnameNormalizer.php:7-58` only if a normalization edge case is missing
- Modify: `tests/Feature/ProjectVerificationTest.php:15-38, 286-311, 338-385`
- Modify: `tests/Feature/CloudConnectionTest.php:247-254` if the existing normalizer coverage needs another identity-normalization case
- Modify: `resources/js/components/shipped/VerificationPanel.vue` and `resources/js/pages/Projects/Edit.vue` only for copy/error presentation that currently promises unrelated Cloud evidence is valid
- Modify: `docs/adr/0001-verify-projects-through-laravel-cloud.md` if the implementation exposes a previously undocumented constraint

**Interfaces:**
- Consumes: `Project::$live_url`, `LaravelCloudUrl::host()`, `HostnameNormalizer::normalize()`.
- Produces: `ProjectVerificationService::verify()` and `refresh()` refuse to probe or accept a Cloud origin whose normalized host differs from the project's normalized live host.

- [x] **Step 1: Replace the permissive feature test with a failing mismatch test.**

Use a reachable fake Cloud URL, an unrelated project live URL, and assert that no outbound probe is sent and that the project remains private and non-verified:

```php
test('a reachable Cloud URL must match the project live hostname', function () {
    Http::preventStrayRequests();
    Http::fake(['https://example-main.laravel.cloud' => Http::response('', 200)]);

    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create([
        'live_url' => 'https://example.com',
        'laravel_cloud_url' => null,
    ]);

    $this->actingAs($creator)
        ->post(route('projects.verification.store', $project), [
            'laravel_cloud_url' => 'https://example-main.laravel.cloud',
        ])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh())
        ->verification_status->toBe('failed')
        ->is_public->toBeFalse()
        ->verification_failure_reason->toBe('The Laravel Cloud URL does not match the project live URL.');

    Http::assertSentCount(0);
});
```

Retain or add the positive case with equivalent `www`, casing, trailing dot, path, query, and fragment variations. Remove the current test that makes a custom `https://example.com` project discoverable from unrelated `example-main.laravel.cloud` evidence.

- [x] **Step 2: Run the focused tests and confirm the new mismatch test fails for the current implementation.**

Run:

```bash
vendor/bin/sail artisan test --compact tests/Feature/ProjectVerificationTest.php tests/Feature/CloudConnectionTest.php
```

Expected: the new mismatch test currently reports `verified` because the service only checks reachability.

- [x] **Step 3: Add the identity check before probing.**

In `ProjectVerificationService::verify()`, normalize the project's current `live_url` and the submitted Cloud URL host before calling `LaravelCloudUrlProbe::probe()`:

```php
$projectHost = $project->live_url === null
    ? null
    : HostnameNormalizer::normalize($project->live_url);

if ($projectHost === null || $projectHost !== HostnameNormalizer::normalize($url->host())) {
    return $this->transition($project, [
        'laravel_cloud_url' => $url->url(),
        'verification_method' => 'cloud_url',
        'verification_status' => 'failed',
        'verification_checked_at' => now(),
        'verification_failure_reason' => 'The Laravel Cloud URL does not match the project live URL.',
        'is_public' => false,
    ]);
}
```

Apply the same comparison in `refresh()` before probing the stored URL so a scheduled recheck cannot be bypassed by stale data or a missed controller invalidation. Keep the existing reachability result handling unchanged after the identity check passes.

- [x] **Step 4: Align user-facing copy and tests with the contract.**

Make the verification panel explain that the submitted Cloud origin must be the same normalized hostname as the project's live URL. If the product still wants custom live URLs verified by a separate `*.laravel.cloud` origin, stop at this step and update ADR 0001 with a different ownership-proof contract; do not silently reintroduce the permissive behavior.

- [x] **Step 5: Run the identity regression slice.**

Run:

```bash
vendor/bin/sail artisan test --compact tests/Feature/ProjectVerificationTest.php tests/Feature/CloudConnectionTest.php tests/Unit/Services/LaravelCloud/LaravelCloudUrlTest.php
```

Expected: matching normalized hosts verify, mismatched hosts fail without an HTTP request, and a successful retry still does not republish.

- [x] **Step 6: Commit the identity-bound verification slice.**

```bash
git add app/Services/LaravelCloud/ProjectVerificationService.php app/Services/LaravelCloud/HostnameNormalizer.php tests/Feature/ProjectVerificationTest.php tests/Feature/CloudConnectionTest.php resources/js/components/shipped/VerificationPanel.vue resources/js/pages/Projects/Edit.vue docs/adr/0001-verify-projects-through-laravel-cloud.md
git commit -m "fix(cloud): bind verification to project host"
```

### Task 2: Make the additive verification migration resumable

**Files:**
- Modify: `database/migrations/2026_08_20_140022_add_cloud_url_verification_to_projects_table.php:14-39`
- Test: `tests/Feature/CloudUrlVerificationMigrationTest.php`
- Validate: disposable MySQL database through Laravel Sail

**Interfaces:**
- Consumes: the existing `projects` table and explicit index name `projects_verification_method_status_idx`.
- Produces: `up()` and `down()` that can be retried after either column statement or the index statement has already succeeded.

- [x] **Step 1: Define the partial-state checks before changing the migration.**

Verify the migration must handle these states independently:

1. neither new column nor index exists;
2. `laravel_cloud_url` exists but `verification_method` and the index do not;
3. both columns exist but the index does not;
4. all additions exist.

The retry must never issue `ADD COLUMN` for an existing column or recreate the named index.

- [x] **Step 2: Make each column and the index conditional.**

Use schema introspection around each additive operation rather than one combined `Schema::table()` call:

```php
if (! Schema::hasColumn('projects', 'laravel_cloud_url')) {
    Schema::table('projects', function (Blueprint $table): void {
        $table->string('laravel_cloud_url')->nullable()->after('live_url');
    });
}

if (! Schema::hasColumn('projects', 'verification_method')) {
    Schema::table('projects', function (Blueprint $table): void {
        $table->string('verification_method', 32)->nullable()->after('verification_status');
    });
}

$hasVerificationIndex = collect(Schema::getIndexes('projects'))
    ->contains(fn (array $index): bool => $index['name'] === 'projects_verification_method_status_idx');

if (! $hasVerificationIndex) {
    Schema::table('projects', function (Blueprint $table): void {
        $table->index(['verification_method', 'verification_status'], 'projects_verification_method_status_idx');
    });
}
```

Guard the reverse operations with the same checks. Keep the explicit index name and do not edit the already-deployed older migrations.

- [x] **Step 3: Run syntax and migration checks.**

Run:

```bash
php -l database/migrations/2026_08_20_140022_add_cloud_url_verification_to_projects_table.php
vendor/bin/sail artisan migrate:status
```

Expected: syntax is valid and the migration status is readable before any retry.

- [x] **Step 4: Exercise the partial MySQL states on a disposable database.**

Using the production-compatible MySQL Sail connection, apply the migration once, inspect the columns and index, then repeat the migration against a schema where only the first column addition has been applied and against one where both columns exist without the index. Confirm each retry completes and the final schema contains exactly one copy of each column and the named index. Do not use the shared development database for the partial-state simulation.

- [x] **Step 5: Run the normal migration/test lane.**

Run:

```bash
vendor/bin/sail artisan migrate:fresh --seed
vendor/bin/sail artisan test --compact tests/Feature/ProjectVerificationTest.php tests/Feature/BackfillCloudVerificationUrlsTest.php
```

Expected: a fresh schema still works, and no application test depends on the migration having run in only one statement.

- [x] **Step 6: Commit the migration slice.**

```bash
git add database/migrations/2026_08_20_140022_add_cloud_url_verification_to_projects_table.php
git commit -m "fix(database): make cloud verification migration resumable"
```

### Task 3: Close the public-address allowlist gaps

**Files:**
- Modify: `app/Services/LaravelCloud/LaravelCloudUrlProbe.php:26-36, 118-166`
- Modify: `tests/Unit/Services/LaravelCloud/LaravelCloudUrlProbeTest.php:140-162`

**Interfaces:**
- Consumes: all DNS answers returned by `CloudHostResolver`.
- Produces: `LaravelCloudUrlProbe::probe()` returns `DefinitiveFailure` with `non_public_address` and sends zero HTTP requests whenever any answer is non-public, shared, benchmarking, documentation, mapped-private, or otherwise special-use.

- [x] **Step 1: Add failing cases for ranges PHP currently accepts.**

Extend the existing data provider with at least:

```php
'RFC 6598 shared IPv4' => [['100.64.0.1']],
'RFC 2544 benchmarking IPv4' => [['198.18.0.1']],
'IPv4-mapped private IPv6' => [['::ffff:10.0.0.1']],
'IPv4-mapped loopback IPv6' => [['::ffff:127.0.0.1']],
```

Keep the existing private, link-local, unique-local, documentation, multicast, and poisoned multi-answer cases. Assert `Http::assertSentCount(0)` for every case.

- [x] **Step 2: Run the probe tests and confirm the new cases fail.**

Run:

```bash
vendor/bin/sail artisan test --compact tests/Unit/Services/LaravelCloud/LaravelCloudUrlProbeTest.php
```

Expected: the current implementation treats `100.64.0.1` and `198.18.0.1` as public and reaches the HTTP client.

- [x] **Step 3: Implement an explicit non-global address policy.**

Retain the existing `FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE` check, then explicitly reject the ranges that those flags allow but this feature must not contact, including `100.64.0.0/10`, `198.18.0.0/15`, the IPv4 documentation blocks, multicast, and the IPv6 documentation/non-unicast blocks already covered by the class.

Before applying IPv4 range checks, detect IPv4-mapped IPv6 addresses (`::ffff:0:0/96`), extract the embedded IPv4 address, and run the same IPv4 policy against it. Do not treat a mapped private address as a valid 16-byte public address merely because its binary length differs from an IPv4 CIDR.

- [x] **Step 4: Add policy-boundary tests.**

Cover one accepted public IPv4 address, one accepted public IPv6 address, every newly rejected range, and a mixed answer list containing one public and one newly rejected address. Confirm the mixed list is rejected before any request is sent.

- [x] **Step 5: Run the complete probe slice and formatter.**

Run:

```bash
vendor/bin/sail artisan test --compact tests/Unit/Services/LaravelCloud/LaravelCloudUrlProbeTest.php tests/Feature/ProjectVerificationTest.php
vendor/bin/sail bin pint --dirty --format agent
```

Expected: all probe classifications pass and Pint reports no PHP formatting changes remaining.

- [x] **Step 6: Commit the address-policy slice.**

```bash
git add app/Services/LaravelCloud/LaravelCloudUrlProbe.php tests/Unit/Services/LaravelCloud/LaravelCloudUrlProbeTest.php
git commit -m "fix(security): reject non-global cloud probe addresses"
```

### Task 4: Enforce the fallback response ceiling during transfer

**Files:**
- Modify: `app/Services/LaravelCloud/LaravelCloudUrlProbe.php:56-104`
- Modify: `tests/Unit/Services/LaravelCloud/LaravelCloudUrlProbeTest.php:46-64`

**Interfaces:**
- Consumes: a 405/501 HEAD response followed by a possible GET response.
- Produces: the fallback GET is issued with a streaming option, its body is read only until the 64 KiB ceiling (with at most one bounded overflow read), and its stream is closed immediately. Status classification remains based on the response status, not the body contents.

- [x] **Step 1: Add a regression test for an oversized fallback response.**

Use a response body larger than `MAX_RESPONSE_BYTES` and assert that the fallback still returns the HTTP status while the bounded body reader stops at the ceiling and closes the stream. Keep the existing assertion that only 405 and 501 trigger GET.

The test must observe the stream reads, not only the final verification status. Use a small test PSR-7 stream wrapper or an extracted bounded-drain collaborator so the test can assert the number of bytes read and that `close()` was called.

- [x] **Step 2: Run the probe test and confirm the current implementation cannot prove the transfer bound.**

Run:

```bash
vendor/bin/sail artisan test --compact tests/Unit/Services/LaravelCloud/LaravelCloudUrlProbeTest.php
```

Expected: the new stream-observation test fails because the Laravel/Guzzle response is received with the default buffered body before `discardBody()` reads it.

- [x] **Step 3: Request the fallback body as a stream.**

Make the probe client accept a streaming flag and use it only for the fallback GET:

```php
private function client(bool $stream = false): PendingRequest
{
    $request = Http::withUserAgent(self::USER_AGENT)
        ->connectTimeout(3)
        ->timeout(8)
        ->withoutRedirecting();

    return $stream ? $request->withOptions(['stream' => true]) : $request;
}
```

Call `get()` with `client(stream: true)`. Update the body drainer to read no more than `MAX_RESPONSE_BYTES + 1`, stop as soon as the ceiling is exceeded, and close the PSR-7 stream in a `finally` block. Never parse, concatenate, or log the body.

- [x] **Step 4: Verify the actual Guzzle transfer behavior.**

Run the stream-observation test and an integration check against a disposable endpoint that returns a body larger than 64 KiB. Confirm the client does not wait for or persist the complete body after the bounded reader closes the stream. Confirm a normal small GET still yields `Reachable` and a 405/501 fallback still performs exactly one GET.

- [x] **Step 5: Run the probe and feature regression slices.**

Run:

```bash
vendor/bin/sail artisan test --compact tests/Unit/Services/LaravelCloud/LaravelCloudUrlProbeTest.php tests/Feature/ProjectVerificationTest.php
vendor/bin/sail bin pint --dirty --format agent
```

- [x] **Step 6: Commit the bounded-transfer slice.**

```bash
git add app/Services/LaravelCloud/LaravelCloudUrlProbe.php tests/Unit/Services/LaravelCloud/LaravelCloudUrlProbeTest.php
git commit -m "fix(security): stream bounded cloud probe responses"
```

### Task 5: Rate-limit synchronous verification probes

**Files:**
- Modify: `app/Providers/AppServiceProvider.php` in the existing `boot()` method
- Modify: `routes/web.php:45-57`
- Modify: `tests/Feature/ProjectVerificationTest.php`

**Interfaces:**
- Consumes: the authenticated user and route-bound `Project`.
- Produces: named middleware `throttle:project-verification` that limits each user/project pair to five attempts per minute before `ProjectVerificationController::store()` can invoke the outbound probe.

- [x] **Step 1: Add the failing throttle feature test.**

Mock `LaravelCloudUrlProbe` so the test cannot make a real request, submit the same valid verification six times, and assert the sixth response is `429` and the probe was called only five times:

```php
test('verification probes are rate limited per creator and project', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create([
        'live_url' => 'https://my-app-main.laravel.cloud',
    ]);

    $this->mock(LaravelCloudUrlProbe::class, function (MockInterface $mock): void {
        $mock->shouldReceive('probe')->times(5)->andReturn(
            new CloudUrlProbeResult(CloudUrlProbeOutcome::Reachable, 200, null, 1),
        );
    });

    foreach (range(1, 5) as $_) {
        $this->actingAs($creator)
            ->post(route('projects.verification.store', $project), [
                'laravel_cloud_url' => 'https://my-app-main.laravel.cloud',
            ])
            ->assertRedirect();
    }

    $this->actingAs($creator)
        ->post(route('projects.verification.store', $project), [
            'laravel_cloud_url' => 'https://my-app-main.laravel.cloud',
        ])
        ->assertTooManyRequests();
});
```

Use a unique creator/project for this test so other feature tests do not consume the same limiter key.

- [x] **Step 2: Run the test and confirm the sixth request currently reaches the controller.**

Run:

```bash
vendor/bin/sail artisan test --compact tests/Feature/ProjectVerificationTest.php --filter='rate limited per creator'
```

Expected: failure because the route has no named throttle.

- [x] **Step 3: Register the named limiter.**

In the existing provider registration, add a per-user/project key without including the URL or any other user input:

```php
RateLimiter::for('project-verification', function (Request $request): Limit {
    $project = $request->route('project');

    return Limit::perMinute(5)
        ->by($request->user()->getAuthIdentifier().':'.($project instanceof Project ? $project->getKey() : 'unknown'));
});
```

Use the repository's existing provider style and imports. Do not rate-limit by the submitted hostname, because an attacker can rotate hostnames while retaining the same expensive endpoint.

- [x] **Step 4: Apply the limiter before the verification controller.**

Change the route to:

```php
Route::post('projects/{project}/verification', [ProjectVerificationController::class, 'store'])
    ->middleware('throttle:project-verification')
    ->name('projects.verification.store');
```

Keep the existing authenticated/verified middleware group and project authorization path intact.

- [x] **Step 5: Run authorization, validation, and throttle coverage.**

Run:

```bash
vendor/bin/sail artisan test --compact tests/Feature/ProjectVerificationTest.php tests/Feature/CloudConnectionTest.php
vendor/bin/sail bin pint --dirty --format agent
```

Expected: unauthorized creators remain rejected, validation failures remain normal validation responses, and the sixth expensive attempt is rejected before the probe.

- [x] **Step 6: Commit the rate-limit slice.**

```bash
git add app/Providers/AppServiceProvider.php routes/web.php tests/Feature/ProjectVerificationTest.php
git commit -m "fix(cloud): throttle verification probes"
```

### Task 6: Preserve legacy safety through the URL cutover

**Files:**
- Modify: `app/Console/Commands/RefreshCloudVerifications.php:18-49`
- Modify: `app/Console/Commands/BackfillCloudVerificationUrls.php:39-83`
- Modify: `tests/Feature/ProjectVerificationTest.php:338-385`
- Modify: `tests/Feature/BackfillCloudVerificationUrlsTest.php`
- Modify: `tests/Feature/ScheduledPublicationTest.php` if needed for the existing scheduled-publish coverage
- Modify: `README.md`
- Modify: `docs/adr/0001-verify-projects-through-laravel-cloud.md`

**Interfaces:**
- Consumes: URL-backed projects, legacy projects with `connected_environment_id`, and the existing backfill command.
- Produces: a scheduled command that never leaves legacy verified evidence untouched; `--apply` makes legacy projects private until `--verify` confirms the new evidence; unresolved legacy projects remain manual and private.

- [x] **Step 1: Add failing tests for the stale-legacy scenario.**

Create a legacy project with `verification_status = verified`, `is_public = true`, a `connected_environment_id`, and no `laravel_cloud_url`. Run `shipped:refresh-cloud-verifications` and assert it is no longer public/verified and receives a migration-required failure reason. Assert the command reports the legacy-pending count and does not send an HTTP probe for a missing URL.

Add a backfill regression test proving `--apply` without `--verify` does not preserve public verified state:

```php
expect($project->fresh())
    ->is_public->toBeFalse()
    ->verification_status->toBe('unverified')
    ->verification_failure_reason->toBe('Legacy verification requires a Laravel Cloud URL recheck.');
```

Retain the existing successful `--apply --verify` test, but make it pass through the host-binding check from Task 1.

- [x] **Step 2: Run the legacy tests and confirm the current command skips the project.**

Run:

```bash
vendor/bin/sail artisan test --compact tests/Feature/ProjectVerificationTest.php tests/Feature/BackfillCloudVerificationUrlsTest.php
```

Expected: the current daily command reports only URL-backed projects and leaves the legacy project unchanged; `--apply` can write a URL without first withdrawing the old verified state.

- [x] **Step 3: Make the daily command fail closed for legacy evidence.**

Select both URL-backed and legacy-connected projects:

```php
->where(function ($query): void {
    $query
        ->whereNotNull('laravel_cloud_url')
        ->orWhereNotNull('connected_environment_id');
})
```

Before calling `ProjectVerificationService::refresh()`, invalidate any selected project whose `cloudUrl()` is `null`, increment a `legacy_pending` counter, and continue. Use a stable creator-facing reason such as `Legacy verification requires a Laravel Cloud URL recheck.`. Do not treat the project as checked/verified merely because its legacy status was previously `verified`.

Update command output and structured logs to include `legacy_pending`. Keep demo projects excluded.

- [x] **Step 4: Make backfill application safe and verification explicit.**

When `BackfillCloudVerificationUrls --apply` finds one canonical candidate, first write the URL while setting the project private and unverified. Only `--verify` may call `ProjectVerificationService::refresh()` and restore `verified` after both host matching and reachability pass.

When zero or multiple candidates exist and `--apply` is used, also withdraw public visibility and mark the project unverified with the manual-remediation reason. Keep the project ID in the manual-required report. Dry-run remains non-mutating.

The resulting transition must be equivalent to:

```php
$project->forceFill([
    'is_public' => false,
    'verification_status' => 'unverified',
    'verified_at' => null,
    'verification_checked_at' => now(),
    'verification_failure_reason' => 'Legacy verification requires a Laravel Cloud URL recheck.',
])->save();
```

- [x] **Step 5: Define and document the cutover sequence.**

Update the README and ADR with this operational order:

```bash
vendor/bin/sail artisan migrate:status
vendor/bin/sail artisan shipped:backfill-cloud-verification-urls --dry-run
vendor/bin/sail artisan shipped:backfill-cloud-verification-urls --apply --verify
vendor/bin/sail artisan shipped:refresh-cloud-verifications
```

Document that projects reported as manual-required or failed remain private and require the creator to submit a matching Cloud URL. Do not delete `cloud_connections` or `connected_environments` until the backfill output has been reviewed and the cutover is complete. Deploy the additive migration before restarting schedulers/workers that query the new columns.

- [x] **Step 6: Run scheduled-publication regression coverage.**

Run:

```bash
vendor/bin/sail artisan test --compact tests/Feature/ProjectVerificationTest.php tests/Feature/BackfillCloudVerificationUrlsTest.php tests/Feature/ScheduledPublicationTest.php
```

Expected: URL-backed projects are rechecked, legacy projects are not silently retained as verified, unresolved projects cannot be published, and a later successful verification still requires explicit republishing.

- [x] **Step 7: Commit the cutover-safety slice.**

```bash
git add app/Console/Commands/RefreshCloudVerifications.php app/Console/Commands/BackfillCloudVerificationUrls.php tests/Feature/ProjectVerificationTest.php tests/Feature/BackfillCloudVerificationUrlsTest.php tests/Feature/ScheduledPublicationTest.php README.md docs/adr/0001-verify-projects-through-laravel-cloud.md
git commit -m "fix(cloud): fail closed during verification cutover"
```

## Final Verification

- [x] Run the complete affected PHP suite:

```bash
vendor/bin/sail artisan test --compact \
    tests/Feature/ProjectVerificationTest.php \
    tests/Feature/BackfillCloudVerificationUrlsTest.php \
    tests/Feature/CloudUrlVerificationMigrationTest.php \
    tests/Feature/ScheduledPublicationTest.php \
    tests/Unit/Services/LaravelCloud/LaravelCloudUrlTest.php \
    tests/Unit/Services/LaravelCloud/LaravelCloudUrlProbeTest.php \
    tests/Feature/CloudConnectionTest.php
```

- [x] Run PHP syntax, formatting, and diff checks:

```bash
find app database/migrations tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l
vendor/bin/sail bin pint --dirty --format agent
git diff --check
```

- [x] Run frontend validation if the verification copy/components were changed:

```bash
npm run lint:check
npm run types:check
```

Record any known environment-level `unrs-resolver` failure or unrelated existing TypeScript errors separately; do not hide task-related errors behind those failures.

- [x] Verify the production-driver migration and rollout state:

```bash
vendor/bin/sail artisan migrate:status
vendor/bin/sail artisan shipped:backfill-cloud-verification-urls --dry-run
```

- [x] Review the final diff for these acceptance conditions:

  - A reachable unrelated Cloud host cannot verify a project.
  - A partial migration retry cannot duplicate columns or the named index.
  - Shared, benchmarking, mapped-private, documentation, and other special-use DNS answers never receive HTTP requests.
  - A large 405/501 fallback body is streamed, bounded, and closed.
  - The sixth verification submission in a minute is rejected before probing.
  - Legacy verified projects are either rechecked through migrated URL evidence or made private with a visible remediation path.
  - No secret, token, URL payload, DNS answer, or response body is logged.

## Self-Review Notes

- The existing custom-live-URL feature test conflicts with ADR 0001. The recommended implementation follows the ADR. If product intent differs, update the ADR and design an explicit ownership proof before implementing a different rule.
- The rollout intentionally prefers temporary privacy for unresolved legacy projects over silently retaining stale public verification. This is reversible through the creator's URL verification flow and explicit republishing.
- The migration is still an additive pending migration in the current uncommitted worktree. If production has already recorded it as applied, do not edit it; create a new forward repair migration after inspecting the actual schema and migration table.
- The partial-state migration regression was exercised on both the isolated SQLite test database and a disposable MySQL 8.4 database; the temporary MySQL container was removed after verification.
- The focused migration regression runs against the repository's isolated SQLite test database; a disposable MySQL partial-state run remains a deployment-environment check because this Sail stack currently exposes PostgreSQL rather than MySQL.
