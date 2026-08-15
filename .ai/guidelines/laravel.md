# Laravel 13 + Inertia/Vue Project Guidelines

Use these rules for Laravel 13 projects using Inertia, Vue 3, TypeScript, Tailwind CSS, shadcn-vue, and Laravel Sail.

Keep code boring, explicit, testable, secure, and aligned with Laravel conventions.

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
