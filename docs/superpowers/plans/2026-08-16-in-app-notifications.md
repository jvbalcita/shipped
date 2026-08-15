# In-App Notifications v1 — Implementation Plan

**Date:** 2026-08-16
**Author:** Jarvis, for Jack (Artisan Stack IT Solutions)
**Source:** shipped-community-vision.md — Phase 1 community core #5 (Notifications)
**Status:** Approved (grilled with Jack 2026-08-15 15:20–16:14; Q1–Q3 explicit)
**Branch:** feat/in-app-notifications (implement after badge + manifest)

## Goal

Turn "something happened" into "someone told you." In-app notifications for the
five events that are *about you or your stuff* — the pull that brings members
back between feed visits. Email is deliberately deferred to the Weekly Digest
feature (next in the lineup).

## Product Decisions (locked)

1. **In-app only.** No email, no push. Email rides in with the Weekly Digest.
2. **Five triggers** (all recipient-targeted, no feed-style events):
   - Someone **follows** you
   - Someone **cheers** your project
   - Someone **reviews** your project
   - Someone **comments** on your project
   - Someone **replies** to your comment
3. **Surface:** bell icon with unread-count badge in the app nav → dedicated
   `/notifications` page (auth-only, cursor-paginated).
4. **Read state:** viewing the page marks items read; "mark all read" action.
5. **Rows:** individual — `@someone cheered your project` — with a link to the
   target. No grouping in v1.
6. **Privacy:** notifications are private; only the recipient sees them.

## Global Constraints

- Laravel 13 / PHP 8.4 / Inertia 3 / Vue 3 / TS / Tailwind 4
- Pest 4 + PHPStan level 7 + Pint + ESLint green
- Reuses the observer wiring from the follow/feed `activities` feature — the
  `ActivityRecorder` hooks are the same trigger points (ADRs 0003/0005 patterns)
- No fan-out: notifications written per-recipient at event time (recipient sets
  are small: project owner(s), comment author, follower)

## Data Model

### `notifications` table

| column | type | notes |
|---|---|---|
| id | bigint PK | |
| user_id | FK users | recipient; cascade delete |
| type | string | `follow` \| `cheer` \| `review` \| `comment` \| `reply` |
| actor_type / actor_id | morph nullable | who triggered it (null-safe) |
| subject_type / subject_id | morph nullable | target (project, comment, review) |
| data | json nullable | snapshot: rating, verb context |
| read_at | timestamp nullable | null = unread |
| created_at / updated_at | timestamp | |

- Index `(user_id, read_at desc)` for the unread badge + paginated list.
- Written by the same observer points as `activities`; guard against
  self-notification (you don't get notified about your own actions — e.g.,
  cheering your own project, following is already self-blocked).
- One notification per event per recipient (unique-ish on
  (user_id, type, actor, subject) — dedupe re-cheers via the same idempotency
  discipline as activities).

## Feed Query

- Badge count: `notifications where user_id = me and read_at is null` — count.
- Page: cursor pagination on `(read_at desc, id desc)` — unread float to the
  top (order: unread first, then recency; cursor on id within the unread/read
  split is fine at v1 scale — simplest: order by `read_at is null desc, id desc`).

## File Map

- `database/migrations/xxxx_create_notifications_table.php`
- `app/Models/Notification.php` (user-scoped, casts data, morphTo actor/subject)
- `app/Notifications/…` — not Laravel's Notification system; a plain
  `app/Services/NotificationRecorder.php` mirroring `ActivityRecorder` (keeps
  the two writers symmetric; Laravel Notifications can be adopted later if email arrives)
- Observers: extend the existing recorder hooks (follows, cheers, reviews,
  comments, replies) to also write notifications
- `app/Http/Controllers/NotificationController.php` (index, markAllRead)
- `routes/web.php`: `GET /notifications`, `POST /notifications/read-all`
- `resources/js/components/shipped/NotificationBell.vue` (badge, polled/refreshed)
- `resources/js/pages/Notifications/Index.vue`
- Nav: bell in the app shell (unread badge from a lightweight prop/endpoint)
- Tests: `tests/Feature/NotificationTest.php`
- Docs: `docs/adr/0008-in-app-notifications.md`

## Task Breakdown

### Task 1: Migration + model + recorder
- Table/model as above; `NotificationRecorder::record(recipient, type, actor,
  subject, data)` with self-notification guard + dedupe.
- Wire into the existing observer points (Follow created, Cheer created, Review
  created, Comment created incl. replies — reply notifies the parent comment
  author, comment notifies the project owner; exclude your own actions).

### Task 2: Bell + unread badge
- `NotificationBell.vue` in the app shell: badge with unread count; refresh on
  navigation (Inertia shared prop `unreadNotificationsCount` or lightweight
  poll — choose shared-prop + event refresh; no polling in v1).
- Link to `/notifications`.

### Task 3: Notifications page
- `Notifications/Index.vue`: rows (icon by type, actor, verb, target link,
  relative time), unread styling, auto mark-read on view (POST on mount or
  Inertia visit), "mark all read" button.
- Empty state: "No notifications yet."

### Task 4: Verify + docs
- Pest: each trigger writes one notification; self-actions don't notify; reply
  notifies parent author; unread badge counts; mark-all-read transitions; auth
  gating (guest → login); pagination.
- Full suite green; PHPStan/Pint/lint/build green.
- Write `docs/adr/0008-in-app-notifications.md`.
- Commit on `feat/in-app-notifications`; PR against main.

## Out of Scope (v1)

- Email/push delivery (Weekly Digest feature owns email)
- Grouping/consolidation ("X and 3 others"), per-type toggles/settings
- Notification preferences UI, digests of missed notifications

## Self-Review

### Spec coverage
- Triggers + recorder + guards: Task 1. Bell/badge: Task 2. Page + read state:
  Task 3. Gates + ADR: Task 4. All locked decisions covered.

### Placeholder scan
- Recipient sets explicit per trigger (project owner, parent comment author,
  follower); no invented triggers beyond the five.

### Type consistency
- Wayfinder `notifications.*` helpers; typed props for rows; PHPStan L7 bar.
