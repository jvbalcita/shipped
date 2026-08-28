---
status: accepted
---

# Record product evidence in a first-party product_events table

## Context

The product roadmap's decision rule requires observable evidence — funnel
conversion, verification completion, launch-kit asset use — before any feature
advances, but the app had no instrumentation at all: no events, no analytics,
no page views. We decided to record an append-only `product_events` table
written through a single `ProductEventRecorder` service, with the event names
defined by a `ProductEventName` enum and the client allowed to record only a
whitelisted subset via `POST /product-events`.

## Considered options

- **Third-party analytics (Plausible/PostHog)** — instant dashboards, but an
  external dependency, per-event cost, and the roadmap's privacy contract
  ("collect no more personal data than the experiment requires") would leave
  our control.
- **Hand-run SQL over the `activities` table** — no new schema, but that table
  is a user-facing feed covering only publish-side moments (launched, released,
  reviewed, cheered, verified); it cannot measure funnels, copies, or shares.
- **First-party table (chosen)** — boring Laravel, queryable with plain SQL for
  cohort reviews, data never leaves the product.

## Consequences

- v1 records **authenticated events only** (actor is the acting creator).
  Anonymous funnel steps (landing views) are a documented gap until an
  experiment needs them.
- Daily scheduled Cloud rechecks deliberately do **not** record events: a cron
  recheck is freshness maintenance, not builder behavior. Only
  creator-initiated verification is recorded.
- The client never sends free-form properties; the request validates a fixed
  set (network name, project id resolved server-side against the actor's own
  projects).
- There is no dashboard; cohort reviews query the table directly.
