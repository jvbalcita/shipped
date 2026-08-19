---
paths:
  - 'tests/Feature/**'
---

# Feature

## Reading Inertia JSON props in Pest feature tests
Asserting raw JSON props (e.g. cursor pagination cursors) needs headers `['X-Inertia' => 'true', 'X-Inertia-Version' => $version]` where `$version = (new HandleInertiaRequests)->version(new \Illuminate\Http\Request)`. Without them the response is SSR HTML (data_get returns null); with X-Inertia but a stale version you get a 409. `assertInertia()` on the SSR HTML response works fine — only raw ->json() reads need the headers.

## Never assertSee on Inertia SSR output — assert props instead
assertSee/assertDontSee on Inertia page responses only work when SSR is available — locally that means the Vite dev server is running (it serves SSR on the fly), so such tests can pass on a dev machine and fail in CI, which builds client-only (`npm run build`, no `--ssr`, no dev server) and renders the non-SSR shell. Assert server-driven contracts via assertInertia props (they parse the data-page JSON present in both modes) and reserve assertSee for Blade-rendered pages.

## Asserting deferred Inertia props needs the partial-reload request
Inertia::defer() props are NOT in the first-load payload (they appear only as deferredProps metadata, e.g. deferredProps.default.0 = "githubRepos") and are also omitted from non-Inertia SSR GETs. To assert their value, send a second request with the X-Inertia headers from feature.md PLUS X-Inertia-Partial-Component (the page component) and X-Inertia-Partial-Data (the deferred prop name) — only that partial response contains the resolved value. Non-deferred props (e.g. githubLinked) are assertable on the first-load request only; partial responses return just the requested props.
