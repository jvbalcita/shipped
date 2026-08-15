# README Verification Badge — Implementation Plan

**Date:** 2026-08-15
**Author:** Jarvis, for Jack (Artisan Stack IT Solutions)
**Source:** shipped-community-vision.md — Phase 4 economy, README badge; immediate-next-steps #3
**Status:** Approved (grilled with Jack 14:58–15:04)
**Branch:** feat/readme-verification-badge (implement after follow-activity-feed lands)

## Goal

Give every verified ship a tiny, on-brand proof-of-life badge that creators drop
into their READMEs. Every badge is a billboard: "this project is verified live on
Laravel Cloud, and Shipped is watching." Cheapest viral loop on the roadmap —
zero new infrastructure, reuses the OG SVG engine pattern.

## Product Decisions (locked)

1. **Self-hosted SVG** endpoint — no shields.io dependency. Design control stays
   with Shipped; freshness/cache stays in our control.
2. **Two-segment Swiss print badge:** left "SHIPPED" wordmark, right status
   segment. Compact, shields-style proportions, monochrome-friendly.
3. **Status states (live from `verification_status`):**
   - `verified` → **VERIFIED LIVE** (green)
   - `stale` → **STALE** (amber)
   - `failed` → **VERIFICATION FAILED** (red)
   - `unverified` → **UNVERIFIED** (gray)
4. **Privacy:** private/unpublished projects return 404 — same rule as the OG
   images (`abort_unless discoverable`). No information leak via badges.
5. **Placement:** "Copy badge" affordance in Creator Studio, per project.
   Public project pages unchanged.
6. **URL shape:** `GET /badges/{project:slug}.svg` — no creator prefix,
   README-friendly. Markdown built from the app URL config (domain-agnostic).

## Global Constraints

- Laravel 13 / PHP 8.4 / Blade SVG views (pattern: `resources/views/og/project.blade.php`)
- Pest 4 + PHPStan level 7 + Pint + ESLint green (CI: tests.yml, lint.yml)
- Wayfinder/route conventions; Swiss industrial print design system
- Cache-Control: `public, max-age=300` (same as cover plate)

## File Map

- `app/Http/Controllers/BadgeController.php` (or extend `OgController` with `badge()`)
- `resources/views/badges/project.blade.php` (SVG template)
- `routes/web.php`: `GET /badges/{project:slug}.svg` → `BadgeController@show`
- `resources/js/pages/settings/…` or Studio project view: "Copy badge" button +
  markdown snippet (clipboard via `navigator.clipboard`, fallback textarea select)
- Tests: `tests/Feature/BadgeTest.php`
- Docs: `docs/adr/0006-readme-verification-badge.md`

## Task Breakdown

### Task 1: Badge endpoint + SVG view
- `BadgeController@show(Project $project)`: `abort_unless(discoverable)` (404 for
  private/unpublished), render `badges.project` with status + label, headers
  `Content-Type: image/svg+xml` + `Cache-Control: public, max-age=300`.
- SVG template: two segments — SHIPPED wordmark (Archivo Black / current
  brand type) + status label (IBM Plex Mono technical-label style). Status
  colors: green `#16a34a`-family, amber `#d97706`, red `#dc2626`, gray
  `#6b7280` — tuned to the existing design tokens. No external assets in the
  SVG (self-contained, like the OG cards).
- Route: `Route::get('/badges/{project:slug}.svg', …)` public.

### Task 2: Creator Studio "Copy badge"
- Per-project action/button in the Studio project surface: copies
  `[![Shipped]({{ config('app.url') }}/badges/{slug}.svg)](https://…/{creator}/{project})`
  (badge links to the launch page — badge → harbor loop).
- Clipboard UX: toast on copy (vue-sonner already in the stack); graceful
  fallback (select text).
- Only shown to the project's creator (auth-gated, Studio context).

### Task 3: Verify + docs
- Pest: each status renders the right label/color; private project 404s;
  cache headers present; markdown snippet uses config URL + slug; guest cannot
  reach the Studio affordance.
- Full suite green; PHPStan/Pint/lint/build green.
- Write `docs/adr/0006-readme-verification-badge.md`.
- Commit on `feat/readme-verification-badge`; PR against main.

## Out of Scope (v1)

- shields.io JSON endpoint compatibility, dynamic badge API
- Badge click analytics / impression counting
- Custom badge colors or user-configured labels
- Badge for releases/versions (badge reflects verification only)

## Self-Review

### Spec coverage
- Endpoint + states + privacy + cache: Task 1. Copy UX: Task 2. Quality gates +
  ADR: Task 3. All locked decisions covered.

### Placeholder scan
- Domain via `config('app.url')` — no hardcoded host. Status set is exactly the
  four `verification_status` values; no invented states.

### Type consistency
- Route model binding by slug; Blade view typed props (status, label, colors);
  PHPStan L7 bar.
