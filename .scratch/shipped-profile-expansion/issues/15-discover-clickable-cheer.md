# 15 — Discover clickable cheer

**What to build:** Each project card on Discover exposes a clickable cheer control. Authenticated creators toggle cheer optimistically (Inertia v3 with automatic rollback on failure); guests see the always-visible count and icon and are prompted to log in with a redirect back. No full page reload.

**Blocked by:** 14 — Comments + polymorphic Cheers migration

**Status:** ready-for-agent

- [ ] ProjectCard on Discover shows always-visible cheer count + icon
- [ ] Authenticated click toggles cheer optimistically via the existing project-cheer route; failure rolls back the UI
- [ ] Guest click routes to login with a redirect back to Discover
- [ ] No full page reload on toggle
- [ ] Feature/browser tests cover authenticated toggle, guest redirect, and optimistic rollback path (or equivalent feature coverage)
