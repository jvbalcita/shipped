# Ship Manifest v1 — Implementation Plan

**Date:** 2026-08-15
**Author:** Jarvis, for Jack (Artisan Stack IT Solutions)
**Source:** shipped-community-vision.md — Phase 5 signature experiences #1 (Ship Manifest); immediate-next-steps #4
**Status:** Approved (grilled with Jack 15:04–15:20)
**Branch:** feat/ship-manifest (implement after badge; sequence: follow/feed → badge → manifest)

## Goal

Give every verified launch a collectible, auto-generated launch artifact — the
"Spotify Wrapped of launching software." One self-contained SVG card that
creators and readers screenshot, share, and link. The Manifest is the artifact
that makes a Shipped launch unmistakable.

## Product Decisions (locked)

1. **Self-contained SVG launch card** — richer than the OG card, same engine
   pattern (`resources/views/og/*` Blade views, `image/svg+xml`).
2. **Route:** `GET /manifests/{creator}/{project}.svg` — mirrors the OG route
   shape (creator + slug).
3. **Privacy:** discoverable-only; private/unpublished → 404 (same rule as OG
   images and the README badge).
4. **Contents (top to bottom):**
   - SHIPPED wordmark header
   - Project name (display type) + tagline
   - Crew line: creator @username + stack tags (top 3)
   - Docket: launch date + filed serial (`DISPATCH 0042` style)
   - Stamp: **VERIFIED LIVE** + verification date
   - First cheer: "First cheer from @user" when one exists
   - Footer: app wordmark
   All data pulled live from the project record — no new data collection.
5. **Surface:** "Save manifest" button on the public launch page (inline render,
   browser-savable) + copy-link option. No Creator Studio surface in v1.
6. **Cache:** `Cache-Control: public, max-age=300` (consistent with cover/badge).

## Global Constraints

- Laravel 13 / PHP 8.4 / Blade SVG views (OG engine pattern)
- Pest 4 + PHPStan level 7 + Pint + ESLint green
- Swiss industrial print design system; self-contained SVG (no external assets)
- Stack tags: derive from the existing `tags` relationship (top 3 by
  `pivot.sort_order` if present, else insertion order)

## File Map

- `app/Http/Controllers/ManifestController.php` (or extend `OgController`)
- `resources/views/manifests/project.blade.php` (SVG template)
- `routes/web.php`: `GET /manifests/{creator:username}/{project:slug}.svg`
- `resources/js/pages/Projects/Show.vue`: "Save manifest" button + copy-link
- Tests: `tests/Feature/ManifestTest.php`
- Docs: `docs/adr/0007-ship-manifest.md`

## Task Breakdown

### Task 1: Manifest endpoint + SVG view
- Controller: `abort_unless(discoverable)`; load `creator`, `tags`, `releases`
  (for launch date fallback), `cheers` (first cheer: earliest `cheers` on the
  project, actor username); render view; headers `image/svg+xml` +
  `Cache-Control: public, max-age=300`.
- Template: manifest card layout — wordmark header, hero name/tagline, crew
  line with tags, docket row (date + DISPATCH serial via `filed_serial`),
  VERIFIED LIVE stamp (`verified_at`), first cheer line, footer. Typography
  from the existing design tokens (Archivo Black display, IBM Plex Mono
  technical labels); fixed card ratio (e.g., 1200×630) for share-friendliness.

### Task 2: Launch page surface
- "Save manifest" button on the public project page: links to the SVG URL
  (`download` attribute where supported) + "copy link" affordance with toast.
- Render condition: only when the project is discoverable (the 404 rule keeps
  the button honest — hide it for private/draft views).

### Task 3: Verify + docs
- Pest: manifest renders with all sections; first cheer absent when none;
  private project 404s; cache headers present; route shape matches OG.
- Full suite green; PHPStan/Pint/lint/build green.
- Write `docs/adr/0007-ship-manifest.md`.
- Commit on `feat/ship-manifest`; PR against main.

## Out of Scope (v1)

- PNG/JPEG raster export (v2 — needs rasterization service or headless render)
- Animated/gif variants, custom creator-styled manifests
- Manifest gallery/archive page, badge analytics
- Multiple manifests per project (one per launch)

## Self-Review

### Spec coverage
- Endpoint + contents + privacy + cache: Task 1. Surface: Task 2. Gates + ADR:
  Task 3. All locked decisions covered.

### Placeholder scan
- No placeholder routes/state; serial via existing `filed_serial`; date from
  `launch_date` (fallback `filed_at`); first cheer from the `cheers` relation.

### Type consistency
- Route model binding (creator + slug); view props typed; PHPStan L7 bar.
