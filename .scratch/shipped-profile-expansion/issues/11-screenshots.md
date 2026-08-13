# 11 — Screenshots

**What to build:** A creator can attach up to five screenshots to a project, each with an optional caption and a sort order. Images are validated jpg/png/webp up to 5 MB each, stored via Laravel Storage, and rendered as a gallery on the public Show page. The five-image limit is enforced both client-side (UI blocks a 6th upload) and server-side (Form Request array-size).

**Blocked by:** 08 — Project pricing + logo + launch_date

**Status:** ready-for-agent

- [ ] `project_screenshots` table: `id`, `project_id` FK cascade, `path`, `caption` nullable, `sort_order` unsigned, timestamps
- [ ] ProjectScreenshot model and Project `hasMany` screenshots relationship ordered by `sort_order`
- [ ] Create/Edit multi-upload UI with optional caption per image and reorder support
- [ ] Client blocks adding a 6th image; server Form Request enforces max 5 and per-image jpg/png/webp max 5 MB
- [ ] Filenames randomized; stored via Laravel Storage
- [ ] Show page renders an ordered gallery with captions
- [ ] Feature tests cover max-5 enforcement (client-facing API), validation failure, reorder, and public gallery render
