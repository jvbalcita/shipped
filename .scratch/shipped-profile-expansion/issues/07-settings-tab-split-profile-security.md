# 07 — Settings tab split — Profile + Security

**What to build:** Settings is cleanly split into two tabs. **Profile** owns public identity: name, username, title, bio, location, links, avatar. **Security** owns private credentials: email, password, 2FA, passkeys, OAuth link/unlink (Google + GitHub), and delete creator profile. Email handling moves from the Profile controller/page into Security. The OAuth link/unlink UI on Security lets an authenticated creator connect or disconnect Google/GitHub providers against their existing account (the path blocked by email-collision refusal in ticket 05).

**Blocked by:** 03 — Profile fields — title, location, links; 05 — Socialite + oauth_accounts + Google/GitHub login

**Status:** ready-for-agent

- [ ] Profile settings page/controller only exposes public identity fields (name, username, title, bio, location, links, avatar)
- [ ] Security settings page/controller owns email, password, 2FA, passkeys, OAuth link/unlink, and delete
- [ ] Email update moves from Profile to Security (Form Request + controller + UI)
- [ ] Security page shows linked OAuth providers and allows link/unlink for Google and GitHub
- [ ] Linking an OAuth provider from Security attaches an `oauth_accounts` row to the authenticated creator
- [ ] Unlinking removes the `oauth_accounts` row without deleting the creator
- [ ] Settings nav reflects the two-tab split; no "Account" term is introduced (glossary avoids it)
- [ ] Feature tests cover the moved email flow, OAuth link, OAuth unlink, and authorization on both tabs
