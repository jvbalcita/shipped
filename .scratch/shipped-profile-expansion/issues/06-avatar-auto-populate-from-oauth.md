# 06 — Avatar auto-populate from OAuth on first registration

**What to build:** When a creator registers via Google or GitHub for the first time and has no avatar yet, Shipped downloads the provider's avatar into Laravel Storage and sets `users.avatar_path`. Subsequent OAuth logins never overwrite a user-set (or previously imported) avatar.

**Blocked by:** 04 — Avatar upload; 05 — Socialite + oauth_accounts + Google/GitHub login

**Status:** ready-for-agent

- [ ] First OAuth registration downloads the provider avatar into Storage and sets `users.avatar_path`
- [ ] Subsequent OAuth logins do not overwrite `avatar_path` (user uploads and prior imports win)
- [ ] Failure to download the provider avatar is non-fatal (registration still succeeds; avatar stays null)
- [ ] Feature tests cover first-registration populate, subsequent-login no-overwrite, and download-failure resilience
