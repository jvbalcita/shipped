# Cheer Wall v1 — the launch-page kudos wall

- **Status:** locked spec · ready for implementation
- **Date:** 2026-08-17
- **Source:** community vision §Community layer item 8 ("Cheer walls"), scoped down to the project-level wall (community ranking + Hall of Fame deferred to v2)

## Summary

Every public launch page gets a wall of its supporters — everyone who cheered, in
chronological order, with the first supporter celebrated as the **First Mate**.
The wall owns the cheer action, so social proof and conversion live in one place.

## Decisions (grilled with Jack)

| # | Decision | Choice |
|---|----------|--------|
| Q1 | Scope | **A — project cheer wall** (community/seasonal wall + Hall of Fame deferred to v2) |
| Q2 | Order + card | Oldest-first (origin story); avatar, name, @username, "cheered on {date}"; **First Mate ⚓** marker on the first cheer; **no cap** (show all) |
| Q3 | Placement | Own section on the launch page, **below the launch story, above the reviews** ("Supporters" / "The Crew Who Cheered") |
| Q4 | CTA | The wall **owns the cheer button**. Empty state: "No cheers yet — be the First Mate ⚓" + cheer CTA. Populated: cheer button at the top of the section ("Join the wall") |
| Q5 | Visibility | Fully public on **publicly discoverable** projects (logged-out visitors see the wall). Private/draft projects: no wall, no cheer data exposed (same rule as manifest + badge). Viewing open; **cheering requires auth** (existing behavior, relocated). No per-cheerer opt-out in v1 — cheers are public endorsements by design; uncheer already exists |
| Q6 | Tech shape | Existing polymorphic `Cheer` relation, eager-loaded with user (avatar, name, username), `created_at ASC`; first row = First Mate. `ProjectController@show` passes `cheers` + current-user cheer state. `CheerWall.vue` section. No pagination (small community) |

## Tasks

1. **Data + controller:** `ProjectController@show` passes `cheers` (avatar, name,
   username, cheered_at) + `hasCheered`/`canCheer` state — **only when the
   project is publicly discoverable**. Keep the existing cheer action working
   (same endpoint); it just gets re-homed in the UI.
2. **UI:** `CheerWall.vue` section on the launch page below the story, above
   reviews — grid of cheer cards (avatar, name, @username, date), First Mate
   marker on the first, cheer count header, empty state with CTA, cheer button
   in the section. Same design language as the rest of the launch page.
3. **Tests (Pest):** wall renders with cheers in oldest-first order + First Mate
   marker; empty state + CTA for zero cheers; wall + cheer data absent on
   private projects; cheering from the wall works (auth required → login
   redirect when logged out).
4. **Verify all gates via Sail** (`./vendor/bin/sail ...`): `sail test`,
   `sail composer types:check`, `sail composer lint:check`,
   `sail npm run lint:check`, `sail npm run build` — all green.

## Out of scope (v2)

- Community/seasonal cheer wall + quarterly Hall of Fame (vision item 8 / 9.6)
- Pagination / infinite scroll for large walls
- Cheer reactions/emotes beyond the existing one-cheer-per-member

## Acceptance

- Public launch page shows the wall with supporters oldest-first, First Mate on top row
- Empty wall converts: CTA invites "be the First Mate"
- Private/draft projects leak zero cheer data
- All quality gates green; conventional commits; no PR (Jack merges)
