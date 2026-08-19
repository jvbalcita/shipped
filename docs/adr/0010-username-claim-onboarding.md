# Provider sign-ups claim their username via an onboarding picker

When a creator signs up through a provider, their handle is seeded from the provider nickname/email and suffixed on collision (e.g. `jvbalcita_1`). Two decisions govern what happens next:

**No merging on username match.** A same-username OAuth login is never merged into an existing account. Usernames are public, globally contested, and renameable on the provider side — matching on them would let a renamed provider account take over a local account. Email remains the only join key, and even same-email logins are refused in favor of the explicit Settings > Security link (see ADR 0002). This is a confirmed stance, not new machinery.

**The creator picks their handle.** Auto-suffixing (`_1`, or word-pairs) was rejected as the final word: on a creator platform where the username is the public URL, the person should choose it. New provider sign-ups land on a one-time "Claim your username" step (`welcome/username`), prefilled with the generated handle and skippable. `users.username_claimed_at` records whether the creator has ever chosen a handle — set at manual registration, set on claim, and the claim endpoint closes once set. First claims intentionally skip the username-change cooldown and the old-name reservation: the previous handle was auto-generated, never chosen, so squat-protection for it is noise. Later changes go through the normal change flow with full protections.

The generator was hardened in passing: it now skips reserved usernames and pads seeds shorter than the 3-character minimum.
