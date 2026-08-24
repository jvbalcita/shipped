<laravel-boost-guidelines>
=== .ai/laravel rules ===

# Laravel 13 + Inertia/Vue Project Guidelines

Use these rules for Laravel 13 projects using Inertia, Vue 3, TypeScript, Tailwind CSS, shadcn-vue, and Laravel Sail.

Keep code boring, explicit, testable, secure, and aligned with Laravel conventions.

## Agent skills

### Issue tracker

Issues and specs for this repo live as GitHub issues in `jvbalcita/shipped`. See `docs/agents/issue-tracker.md`.

### Triage labels

Use the default five labels: `needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, and `wontfix`. See `docs/agents/triage-labels.md`.

### Domain docs

This is a single-context repo using root `CONTEXT.md` and `docs/adr/`. See `docs/agents/domain.md`.

---

## Commands

This project runs inside Laravel Sail. Prefix commands with `vendor/bin/sail`.

```bash
vendor/bin/sail artisan make:* --no-interaction
vendor/bin/sail artisan make:test ExampleTest --pest --no-interaction
vendor/bin/sail bin pint --dirty --format agent
vendor/bin/sail artisan test --compact
```

Use relevant test filters while developing.

```bash
vendor/bin/sail artisan test --filter=ProjectTest
```

---

## Core Principles

- Prefer Laravel built-ins before custom abstractions: Form Requests, Policies, Jobs, Events, Notifications, Resources, Casts, Observers.
- Keep controllers thin: validate/authorize, coordinate, return response.
- Prefer resourceful CRUD controllers using `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`.
- Do not add custom controller methods like `approve`, `publish`, `archive`, `restore`, `cancel`, or `sync`.
- For non-CRUD workflows, model the workflow as its own resource and use `store`, `update`, or `destroy`.
- Use Actions/Services only when logic is reusable, complex, or has real domain meaning.
- Avoid new base folders, architecture layers, or patterns unless the codebase already uses them.

Example workflow routes:

```php
Route::post('/posts/{post}/publication', [PostPublicationController::class, 'store'])
    ->name('posts.publication.store');

Route::delete('/posts/{post}/publication', [PostPublicationController::class, 'destroy'])
    ->name('posts.publication.destroy');

Route::patch('/orders/{order}/status', [OrderStatusController::class, 'update'])
    ->name('orders.status.update');
```

---

## Naming

- Use domain- or behavior-based names.
- Avoid milestone or temporary names: `phase`, `mvp`, `v2`, `final`, `temp`.
- Avoid vague names like `Helper`, `Manager`, `Processor`, or `Handler` unless the purpose is very clear.

Good:

```txt
LaunchReadinessTest
DiscoveryFiltersTest
PublishPost
ImportEmployeesJob
```

Bad:

```txt
PhaseOneController
FinalService
TempUserTest
MvpDashboard
```

---

## Routes

Split routes by area, not by model:

```txt
web.php
auth.php
admin.php
settings.php
api.php
console.php
```

Rules:

- Group by `middleware`, `prefix`, and `name`.
- Prefer `Route::resource()` or `Route::apiResource()`.
- Always use named routes.
- Do not hard-code application URLs in Vue or TypeScript.
- Use Wayfinder-generated helpers where available.

---

## Requests, Authorization, and Security

- Always use Form Requests for validation.
- Never use inline controller validation.
- Use Policies/Gates for authorization.
- Authorize every state-changing action.
- Never trust hidden frontend fields for ownership, role, tenant, status, or permission checks.
- Never pass raw `$request->all()` into `create()`, `update()`, `fill()`, or services.
- Use `$request->validated()` or `$request->safe()`.
- Never log secrets, tokens, passwords, or sensitive payloads.
- Use rate limiting for auth, public forms, imports, exports, webhooks, and expensive endpoints.

Preferred:

```php
Project::query()->create($request->validated());
```

Avoid:

```php
Project::query()->create($request->all());
```

---

## Eloquent and Database

- Prefer Eloquent relationships over manual joins.
- Avoid `DB::` unless truly necessary.
- Prevent N+1 queries using eager loading.
- Use scopes for reusable query constraints only; no business logic in scopes.
- Group `orWhere` clauses in closures.
- Use transactions for multi-step writes that must succeed or fail together.
- Add indexes for foreign keys and frequently filtered columns.
- Use foreign key constraints unless there is a clear reason not to.
- Do not edit shared/deployed migrations; create a new migration.
- When modifying a column, include all existing column attributes or they may be dropped.
- Use soft deletes only when the business needs recovery/history, not as a status replacement.
- Define casts using `casts()` unless the codebase uses the property convention.

Grouped `orWhere` example:

```php
$query
    ->where('team_id', $teamId)
    ->where(function ($query) use ($term) {
        $query
            ->where('name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%");
    });
```

---

## Migrations

- Treat migration identifiers as cross-database contracts. MySQL/MariaDB limit identifiers to 64 characters; PostgreSQL limits them to 63. Do not rely on Laravel's generated names when a table or column name is long.
- Always provide a short, explicit, descriptive name for composite indexes, unique constraints, foreign keys, and checks. Keep names below 60 characters so the same migration works on every supported driver.

```php
$table->index(
    ['catalog_item_variant_id', 'branch_id', 'price_type'],
    'catalog_item_prices_variant_scope_idx',
);
```

- Test schema migrations on the production database driver whenever the local driver differs, especially for identifier limits, foreign keys, JSON, partial indexes, and column alterations.
- A production migration that fails partway through may have applied some DDL without being recorded in Laravel's `migrations` table. Inspect the schema before retrying; make the pending migration safely resumable only when its partial state is known and controlled.
- Once a migration has completed in production, do not edit it. Repair it with a new forward migration. If it failed before completion, document the exact partial state and deliver the smallest safe retry repair in a new commit.
- Deploy additive schema changes before application code or workers query the new column. Verify `php artisan migrate:status` before restarting long-running workers.

---

## Mass Assignment

This project uses `Model::unguard()` globally.

Because of that:

- Do not rely on `$fillable` or `$guarded`.
- Only write validated/safe data.
- Never mass-assign sensitive fields unless intentionally controlled.

Sensitive examples:

```txt
role
role_id
user_id
team_id
is_admin
email_verified_at
password
remember_token
```

---

## Actions, Jobs, and Events

Use Actions/Services for meaningful domain operations.

Good cases:

```txt
PublishPost
GenerateReport
ProcessImport
SyncExternalAccount
```

Jobs:

- Use queued jobs for expensive work, integrations, imports, exports, notifications, and image/file processing.
- Keep jobs small.
- Put domain work in an Action/Service and call it from the job.
- Prefer idempotent jobs.

Events:

- Use events for side effects: audit logs, notifications, cache invalidation, integrations.
- Keep listeners small.
- Do not hide core business logic in events.

---

## Inertia, Vue, and TypeScript

- Use Vue 3 Composition API with `<script setup lang="ts">`.
- Use TypeScript for props, forms, shared data, and reusable components.
- Keep pages in `resources/js/pages`.
- Keep reusable components in `resources/js/components`.
- Keep composables in `resources/js/composables`.
- Keep shared types in `resources/js/types`.
- Do not put business rules in Vue.
- Use Inertia forms for server-backed forms.
- Laravel validation is the source of truth.
- Shape Inertia props intentionally; do not expose full models when only specific fields are needed.
- Keep shared props minimal.
- Use deferred props/lazy loading for expensive non-critical data.
- Add `data-test` attributes to important UI actions.

---

## UI

- Use shadcn-vue primitives consistently.
- Use Tailwind utilities only; avoid custom `<style>` blocks unless already used by the project.
- Use @lucide/vue for icons.
- Provide loading, empty, success, and error states.
- Use dialogs for small forms.
- Use sheets for larger create/edit flows.
- Use alert dialogs for destructive confirmations.
- Use semantic HTML and accessible labels.
- Do not rely on color alone to communicate status.

---

## Config, Cache, Files, and Logs

Config:

- Never call `env()` outside config files.
- Use `config()` in application code.

Timestamps:

- Store real event timestamps such as `created_at`, `updated_at`, `completed_at`, `voided_at`, `received_at`, and `last_login_at` in UTC.
- Serialize event timestamps to the frontend as timezone-aware ISO 8601 strings. Do not mix raw `Y-m-d H:i:s` database strings with ISO payloads in the same domain.
- Convert event timestamps only at the display or reporting boundary using the tenant or user timezone.
- For report filters, interpret date-only selections in the tenant timezone first, then convert that local day window back to UTC for SQL queries and exports.
- Keep wall-clock schedule fields such as bookings separate from event timestamps. Preserve the local scheduled datetime the user selected instead of serializing it as a timezone-shiftable instant.
- Do not “fix” timestamp bugs by changing stored historical values unless the task explicitly includes a reviewed data migration plan.

Cache:

- Use clear keys and TTLs.
- Scope cache keys by user/team/tenant when needed.
- Invalidate cache near the write that changes the data.
- Do not cache sensitive user data unless properly scoped.

Files:

- Validate file type, size, and structure.
- Use Laravel Storage.
- Never trust original filenames.
- Use queues for large imports, exports, and file processing.
- Use signed URLs for temporary private access.

Logs:

- Log meaningful failures and domain events.
- Use structured context arrays.
- Do not log full model payloads or sensitive data.

---

## Packages

### spatie/laravel-activitylog

- Use activity logging only where auditability has product, compliance, or operational value.
- Use `logOnly`, `logOnlyDirty`, and `dontSubmitEmptyLogs`.
- Never log sensitive fields.

```php
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['name', 'email'])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
}
```

### spatie/laravel-permission

- `User` carries `HasRoles`.
- Use permissions for capabilities and roles for grouping.
- Do not hard-code role checks in Vue.
- Prefer Policies/Gates for backend enforcement.

Good permission names:

```txt
projects.view
projects.create
projects.update
projects.delete
reports.export
```

---

## Comments and PHPDoc

- Prefer clear names and strict types over comments.
- Comment why, not what.
- Use PHPDoc for relationship return types, complex array shapes, and non-obvious scopes/attributes.
- If a method needs a long explanation, extract an Action/Service and test it.

---

## Testing

- Every meaningful change needs tests.
- Pest is the default.
- Prefer feature tests for user-facing behavior.
- Test success, validation failure, and authorization failure.
- Test important policies, scopes, and actions.
- Add regression tests for bug fixes.
- Use factories.
- Use `RefreshDatabase` unless there is a specific reason not to.
- Follow `.ai/guidelines/testing.md` for repo-specific lane policy, heavy-test classification, local workflow, and shared test helper usage.

---

## PR Checklist

Before finalizing:

- [ ] Controllers are thin and resourceful.
- [ ] Validation uses Form Requests.
- [ ] Authorization is enforced.
- [ ] Data writes use validated/safe data.
- [ ] Queries avoid N+1 issues.
- [ ] Routes are named.
- [ ] Vue/TypeScript has no hard-coded backend URLs.
- [ ] Inertia props are intentionally shaped.
- [ ] UI has loading/empty/error states where needed.
- [ ] Sensitive data is not logged or exposed.
- [ ] Relevant tests pass.
- [ ] Pint was run.

```bash
vendor/bin/sail bin pint --dirty --format agent
vendor/bin/sail artisan test --compact
```

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.5. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>
