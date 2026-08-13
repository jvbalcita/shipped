# 09 — More categories seed

**What to build:** The categories list grows from the current six to fifteen by adding Library, Plugin, Theme, Mobile App, Desktop App, AI Tool, Boilerplate, Course, and Community via the existing DatabaseSeeder. Categories remain seeder-managed (no admin UI).

**Blocked by:** None — can start immediately.

**Status:** ready-for-agent

- [ ] DatabaseSeeder seeds the nine new categories alongside the existing six (total 15), using firstOrCreate on slug
- [ ] Discover category filter surfaces all fifteen after a fresh seed
- [ ] Feature or seeder test asserts the full set of category names/slugs is present after seeding
