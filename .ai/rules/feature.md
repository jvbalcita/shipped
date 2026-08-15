---
paths:
  - 'tests/Feature/**'
---

# Feature

## Reading Inertia JSON props in Pest feature tests
Asserting raw JSON props (e.g. cursor pagination cursors) needs headers `['X-Inertia' => 'true', 'X-Inertia-Version' => $version]` where `$version = (new HandleInertiaRequests)->version(new \Illuminate\Http\Request)`. Without them the response is SSR HTML (data_get returns null); with X-Inertia but a stale version you get a 409. `assertInertia()` on the SSR HTML response works fine — only raw ->json() reads need the headers.
