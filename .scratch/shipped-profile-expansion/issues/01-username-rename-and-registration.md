# 01 — Username rename + user-chosen at registration

**What to build:** A creator picks their public username at registration instead of having it slugified from their display name. The existing `handle` column is renamed to `username` (edit the existing migration; `migrate:fresh` is acceptable). Routes become `/@{creator:username}`, the User route key becomes `username`, and Fortify's registration flow accepts and validates a unique username matching `^[a-z0-9_]{3,30}$`. Username is immutable post-signup in this slice — change-with-reservation lands in ticket 02.

**Blocked by:** None — can start immediately.

**Status:** ready-for-agent

- [ ] Existing `handle` column renamed to `username` in the users migration; `migrate:fresh` succeeds
- [ ] User model route key is `username`; public routes resolve via `/@{creator:username}`
- [ ] Fortify registration accepts a user-chosen `username` field
- [ ] Username validated unique and matching `^[a-z0-9_]{3,30}$`
- [ ] Registration UI shows a username input
- [ ] All existing references to `handle` (factories, tests, Vue pages, controllers, seeders) updated to `username`
- [ ] Feature tests cover unique constraint, format validation, and successful registration with a chosen username
