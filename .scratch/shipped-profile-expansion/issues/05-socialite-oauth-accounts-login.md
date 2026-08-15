# 05 — Socialite + oauth_accounts + Google/GitHub login

**What to build:** A visitor can register or log in with Google or GitHub via Laravel Socialite, alongside the existing Fortify password/passkey flows. Linked provider identities live on a dedicated `oauth_accounts` table. Email collision with an existing user that has no matching provider link refuses to auto-merge — the visitor must authenticate to their existing account and link the provider from Settings > Security (link/unlink UI lands in ticket 07; this ticket only implements the login/registration path and the refusal).

**Blocked by:** None — can start immediately.

**Status:** ready-for-agent

- [ ] `laravel/socialite` installed and configured for Google and GitHub
- [ ] `oauth_accounts` table: `user_id` FK cascade, `provider` enum(google, github), `provider_id`, encrypted `provider_token` + `provider_refresh_token` (nullable), `token_expires_at` (nullable), `linked_at`, timestamps; UNIQUE composite on `(provider, provider_id)`
- [ ] OAuth redirect + callback routes and controller for Google and GitHub
- [ ] Login and Register pages show Google and GitHub buttons
- [ ] New OAuth user creates a User + OAuth Account; existing matching provider link logs the user in
- [ ] Email collision with an existing user that has no matching provider link refuses auto-merge (no account takeover)
- [ ] Feature tests cover new-user registration, existing-link login, and email-collision refusal
- [ ] Follows ADR 0002 (Socialite + Fortify coexistence)
