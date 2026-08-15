# 03 — Profile fields — title, location, links

**What to build:** A creator can set a self-described title (defaults to "Creator"), an optional free-text location, and a typed collection of external links (website, github, twitter, linkedin) on their profile. These fields appear on the Profile settings form and render on the public creator page.

**Blocked by:** None — can start immediately.

**Status:** ready-for-agent

- [ ] `users` table gains `title` (string, default "Creator"), `location` (nullable string, max 80), and `links` (JSON, nullable) columns — edit existing migration; `migrate:fresh` is acceptable
- [ ] User model casts `links` to array; title defaults to "Creator" on registration
- [ ] ProfileUpdateRequest validates title (max 50), location (max 80, nullable), and links as a typed array with allowed types `website|github|twitter|linkedin` and valid URLs
- [ ] Profile settings UI exposes title, location, and links inputs
- [ ] Public creator page renders title, location, and links (with icons)
- [ ] Feature tests cover validation success/failure and public rendering
