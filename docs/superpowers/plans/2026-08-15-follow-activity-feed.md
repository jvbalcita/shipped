# Follow + Activity Feed — Implementation Plan

**Date:** 2026-08-15
**Author:** Jarvis, for Jack (Artisan Stack IT Solutions)
**Source:** shipped-community-vision.md — Phase 1 community core, immediate-next-steps #2
**Status:** Approved (grilled with Jack 14:04–14:29)
**Branch:** feat/follow-activity-feed

## Goal

Turn Shipped from a registry into a place people return to. Members follow
creators and projects; a private `/feed` page surfaces what those targets are
doing — launching, releasing, reviewing, cheering, verifying — in reverse
chronological order. This is the highest-leverage community feature: it creates
the retention loop (follow → feed → discover → follow).

## Product Decisions (locked)

1. **Follow targets:** creators (users) and projects. One follow per target per user.
2. **Feed events (v1):** launched, released, reviewed, cheered, verified.
   Excluded: follow events, comments, badges, collections.
3. **Feed semantics:** union of (actions by followed creators) ∪ (events on
   followed projects), deduplicated — an event matching both sides appears once.
4. **Destination:** authenticated `/feed` page, private. Nav item "Following".
   No public feed in v1.
5. **Follow buttons:** on creator profile page (`/@username`) and project page
   (`/@creator/project`). Optimistic Follow ↔ Following toggle. Follower counts
   shown on profiles.
6. **Follows are public** (count + follower identities), matching the
   integrity-first community stance.
7. **No self-follow.** Following your own project is allowed (no special case).
8. **Empty state:** first-run nudge — "Follow creators to see their ships."

## Global Constraints

- Laravel 13 / PHP 8.4 / Inertia 3 / Vue 3 / TS / Tailwind 4 / shadcn-vue (repo conventions).
- Pest 4 + PHPStan level 7 + Pint + ESLint must stay green (CI: tests.yml, lint.yml).
- Follow existing patterns: ADR 0003 polymorphic Cheers, `App\Concerns\*`,
  wayfinder routes (`import { feed } from '@/routes'`), Swiss industrial print UI.
- Queues: DB queue acceptable; observers must not block the request path for
  cheap writes. No fan-out in v1 (read-time assembly) — revisit only at scale.
- Scheduled releases publish via the existing `shipped:publish-scheduled-releases`
  command; a "released" activity fires only when a release actually publishes.

## Data Model

### `follows` (polymorphic, mirrors `cheers`)

| column | type | notes |
|---|---|---|
| id | bigint PK | |
| user_id | FK users | follower; cascade delete |
| followable_type | string | `App\Models\User` \| `App\Models\Project` |
| followable_id | bigint | |
| created_at | timestamp | |
| updated_at | timestamp | |

- Unique index `(user_id, followable_type, followable_id)`.
- Index `(followable_type, followable_id)` for follower counts.
- `App\Concerns\Followable` (morphMany follows + `followersCount` helper) and
  `App\Concerns\Follows` (morphMany on user for followings).
- Policy `FollowPolicy`: create only when authenticated, target not self
  (users), target exists. No rate limits v1 (note in ADR if abuse appears).

### `activities` (feed event log)

| column | type | notes |
|---|---|---|
| id | bigint PK | |
| actor_type / actor_id | nullable morph | who did it (user); null-safe (deleted users keep the event) |
| subject_type / subject_id | morph | what it happened to (project, release, review, comment-target) |
| verb | string | `launched` \| `released` \| `reviewed` \| `cheered` \| `verified` |
| occurred_at | timestamp | when the domain event happened (published_at for releases) |
| meta | json nullable | snapshot: rating for reviews, cheer target kind, etc. |
| created_at / updated_at | timestamp | |

- Index `(occurred_at desc)` for feed ordering; index on `(subject_type, subject_id)`.
- Written by model observers / domain event listeners (see Task 4). Idempotency:
  `occurred_at` + verb + subject unique-ish guard in the writer (e.g., dedupe on
  (subject, verb, occurred_at) for same actor) to survive re-verifications and
  scheduled-publish retries.
- Deleted subjects: activity rows are NOT cascade-deleted (feed is a log);
  rendering null-guards missing subjects ("a ship that has sailed").

## Feed Query (read-time, no fan-out)

```
events where
  (actor in (SELECT followable_id FROM follows WHERE user_id = me AND followable_type = 'user'))
  OR
  (subject in (SELECT followable_id FROM follows WHERE user_id = me AND followable_type = 'project') AND subject_type = 'project')
order by occurred_at desc
```

- Implemented as a single query with `whereIn` subselects on the `activities`
  table; `distinct` on id for dedupe (an event is one row, so dedupe is free).
- Cursor pagination on `occurred_at`/`id` (Laravel cursor paginator), 20/page.
- Props to the page: `activities` (typed shape), `followedCreators`,
  `followedProjects` counts, `empty` flag.

## File Map

- `database/migrations/xxxx_create_follows_table.php`
- `database/migrations/xxxx_create_activities_table.php`
- `app/Models/Follow.php`
- `app/Models/Activity.php`
- `app/Concerns/Followable.php` (projects, users)
- `app/Concerns/Follows.php` (user followings)
- `app/Concerns/RecordsActivity.php` (observers glue)
- `app/Observers/ReleaseObserver.php`, `ReviewObserver.php`, `CheerObserver.php`,
  `ProjectObserver.php` (or one `ActivityRecorder` service + model events)
- `app/Policies/FollowPolicy.php`
- `app/Http/Controllers/FeedController.php`
- `app/Http/Controllers/FollowController.php` (store/destroy)
- `routes/web.php`: `GET /feed` (auth), `POST /follows`, `DELETE /follows/{follow}` (or `follows.store`/`follows.destroy` by target)
- `resources/js/pages/Feed/Index.vue`
- `resources/js/components/shipped/FollowButton.vue`
- `resources/js/components/shipped/ActivityItem.vue` (verb-styled rows, Swiss print)
- Nav: add "Following" link (dashboard shell / app layout)
- Tests: `tests/Feature/FollowTest.php`, `tests/Feature/ActivityFeedTest.php`
- Docs: `docs/adr/0005-follow-activity-feed.md` (short ADR capturing the decisions)

## Task Breakdown

### Task 1: Follows migration + model + concern
- Migrations as above; `Follow` model (morphTo `followable`, belongsTo `user`).
- `Followable`/`Follows` concerns; register `FollowPolicy`.
- Route model binding; `follows.store`/`follows.destroy` accept
  `followable_type` + `followable_id` (validated against an allowlist of
  `App\Models\User` / `App\Models\Project`), or explicit `user`/`project` routes
  (prefer explicit: `POST /users/{user}/follow`, `POST /projects/{project}/follow`,
  `DELETE ...` — simpler controllers, cleaner wayfinder types).
- Tests: follow/unfollow, unique constraint, self-follow 403, public reads OK.

### Task 2: Follower counts + profile/project Follow button
- `FollowButton.vue` with optimistic toggle (local state + Inertia post/delete,
  rollback on error), rendered on CreatorController show page and Project show page.
- Follower count on creator profile (`X followers`); count on project page compact.
- Include `followers_count` + `is_followed_by_viewer` in the page props
  (withCount + exists subquery).
- Tests: button state round-trips, counts update, guest sees Follow button
  (redirects to login on click) or hidden (decide: visible, click → login).

### Task 3: Activities table + recorder
- Migration as above. `Activity` model (morphTo actor/subject, casts meta).
- One `ActivityRecorder::record(verb, actor, subject, occurredAt, meta)` service.
- Hook points:
  - **launched** — project transitions to `verification_status = verified` AND
    has a published release, first time public (mirror `isPubliclyDiscoverable`);
    fire once (guard: only on transition to verified).
  - **released** — release `published_at` set/backfilled (immediate + scheduled);
    observer on Release saved/updated when published_at becomes non-null.
  - **reviewed** — Review created (meta: rating).
  - **cheered** — Cheer created (meta: target kind project/comment; feed shows
    the subject project).
  - **verified** — project verification transition to `verified` (covers
    re-verification after failure; distinct from `launched` which also requires
    a published release).
- Idempotency guard in recorder; cheap writes (no queue needed for v1 volume —
  note: if a queue is preferred, DB queue is fine).
- Tests: each verb records once and only on the right transition (scheduled
  publish included), no dupes on daily recheck.

### Task 4: Feed page
- `FeedController` with the read-time query + cursor pagination.
- `Feed/Index.vue`: reverse-chron rows via `ActivityItem.vue` (verb-styled:
  "🚢 X launched Y", "📦 X released v1.2 of Y", "⭐ Z reviewed Y (4/5)",
  "👏 Z cheered Y", "✅ Y passed verification"); each links to its target;
  missing subjects render gracefully.
- Nav "Following" item; empty state nudge with CTA to /discover.
- Tests: feed assembles correctly for creator-follows, project-follows, both
  (dedupe), auth gating (guest → login), pagination.

### Task 5: Verify + docs
- Pest full suite green; PHPStan level 7 clean; Pint clean; ESLint/build green.
- Write `docs/adr/0005-follow-activity-feed.md` (decisions + rationale,
  including public-follows stance and no-fan-out choice).
- Commit on `feat/follow-activity-feed`; open PR against main.

## Out of Scope (v1)

- Notifications (in-app/email) — next spec, builds on `activities`.
- Badges/achievements, weekly digest, collections/boards, @mentions.
- Public "all activity" feed, follow recommendations, follower lists page
  (only counts), rate limits, fan-out/streams.

## Self-Review

### Spec coverage
- Follow targets/buttons/counts: covered (Tasks 1–2).
- Five event types + idempotency + scheduled releases: covered (Task 3).
- Feed semantics, dedupe, pagination, empty state: covered (Task 4).
- CI quality gates: covered (Task 5).

### Placeholder scan
- No placeholder names, routes, or states remain; explicit allowlist for
  followable types; explicit transition rules for launched/verified.

### Type consistency
- Wayfinder generates `feed`, `follows.*` route helpers; TS props typed for
  `ActivityItem`; PHPStan level 7 is the bar — no `mixed` leaks in feed mapping.
