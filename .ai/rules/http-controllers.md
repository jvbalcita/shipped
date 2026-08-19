---
paths:
  - 'app/Http/Controllers/**'
---

# Http Controllers

## Use Inertia::location() for external redirects from Inertia-handled routes
Returning a plain 302 to an external URL (Socialite authorize, payment providers, etc.) from a route hit by an Inertia visit silently dies: the XHR follows the redirect and is blocked by CORS (inertia-laravel does not auto-convert external redirects). Wrap the redirect in Inertia::location() — Inertia requests get 409 + X-Inertia-Location and the client performs window.location; non-Inertia callers still receive the 302. Covered by test 'link initiation answers inertia requests with an external location redirect'.
