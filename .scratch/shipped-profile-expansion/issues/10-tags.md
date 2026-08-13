# 10 — Tags

**What to build:** A creator can attach free-form tags to a project via a comma-separated input on Create/Edit. Tags are normalized into a `tags` table and linked through a `project_tag` pivot. Suggested sample chips (from a fixed seed list) help the creator pick common tags. Tags render on the Show page and Discover cards and are distinct from the single curated Category.

**Blocked by:** None — can start immediately.

**Status:** ready-for-agent

- [ ] `tags` table (`id`, `name`, `slug` unique, timestamps) and `project_tag` pivot (`project_id` FK cascade, `tag_id` FK cascade, UNIQUE composite, timestamps)
- [ ] Tag model and Project `belongsToMany` tags relationship
- [ ] Create/Edit accept comma-separated tag input; Form Request parses, slugifies, dedupes, and syncs
- [ ] Fixed seed list of suggestion chips (e.g. laravel, vue, api, indie, open-source, tailwind, pest, inertia) available to the UI
- [ ] Show page and Discover cards render project tags
- [ ] Feature tests cover create/update with tags, dedupe, empty tags, and public rendering
