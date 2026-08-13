# 08 — Project pricing + logo + launch_date

**What to build:** A creator can set a project's commercial model (pricing enum), upload a square logo, and record a user-selected launch date. These appear on the Create/Edit forms, the public project Show page, and Discover cards/filters. Launch date is display/filter metadata only — no scheduling or visibility gating.

**Blocked by:** None — can start immediately.

**Status:** ready-for-agent

- [ ] `projects` table gains `pricing` (enum: Free, Freemium, Paid, Open Source, Subscription, One-Time; default Free; indexed), `logo_path` (nullable string), `launch_date` (nullable date) — edit existing migration; `migrate:fresh` is acceptable
- [ ] Project model casts pricing to a TitleCase enum and launch_date to date
- [ ] Store/Update project Form Requests validate pricing against the enum, logo as square PNG/JPG/WebP min 256×256 max 6 MB (randomized filename, Laravel Storage), and launch_date as a valid date
- [ ] Create and Edit forms expose pricing select, logo uploader, and launch date picker
- [ ] Show page and Discover cards render pricing, logo, and launch date
- [ ] Discover can filter/sort by pricing and launch date
- [ ] Feature tests cover validation success/failure and public rendering
