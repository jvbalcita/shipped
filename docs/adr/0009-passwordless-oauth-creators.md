# Represent OAuth-only creators with a NULL password

Creators registered through a provider (Google/GitHub) have no password they know. The original implementation stored `Str::random(40)` as an unknowable placeholder, which silently broke three flows: `RequirePassword` middleware demanded a password confirmation they could never pass (locking them out of Settings > Security), the password-update form required `current_password` just to set a first password (chicken-and-egg), and nothing distinguished a "random placeholder" from a real password.

OAuth sign-up now stores `users.password = NULL` — the standard passwordless representation. A `users.password` nullable migration supports it. Three consequences follow:

- `App\Http\Middleware\RequirePassword` extends Laravel's and lets passwordless users through: their authenticated OAuth session stands in for password confirmation. (Email/username/profile pages are not confirmation-gated anyway, so this adds no meaningful new session-hijack surface.)
- `PasswordUpdateRequest` only demands `current_password` when a password exists; the Security page renders a "Set a password" heading without the current-password field for these creators.
- `Settings\OAuthController::unlink()` refuses to remove a creator's last sign-in method (no password, no passkeys, no other linked provider), preventing permanent lockout.

Password sign-in attempts for a NULL password fail bcrypt comparison naturally — no special casing. The alternative (email-based set-password links) was rejected as a heavier flow that still leaves security settings unreachable until completed.
