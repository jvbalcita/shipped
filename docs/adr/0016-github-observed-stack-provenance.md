---
status: accepted
---

# Observed Built With evidence is read from the project's public repository and never overrides the creator

Shipped upgrades Built With from creator-declared only to creator-declared plus system-observed. A Stack Observation reads `composer.json` and `package.json` from the repository root of the project's advertised public GitHub URL, matches the dependency declarations against the curated vocabulary's observation keys, and marks the matching Technologies as observed evidence on the public project page. No competitor in the space (Product Hunt, Made With Laravel) evidences stack claims; this keeps Shipped's trust wedge compounding.

## Decision

- **Public repository reads only.** Observation reads the repo the project already advertises at `github_url` through the GitHub contents API (default branch). Access is anonymous, with an optional app-level token (`services.github.app_token` / `GITHUB_APP_TOKEN`) purely for rate-limit headroom. Creator OAuth tokens are never used for observation — they can be stale, and evidence must not depend on a creator's session.
- **Only root manifests, runtime and dev sections alike.** `composer.json` `require` + `require-dev`, `package.json` `dependencies` + `devDependencies`. Frontend tooling legitimately lives in npm devDependencies, and dev-only packages (Telescope, Pest, Breeze) are still true statements about the project. An unreadable `package.json` is ignored; `composer.json` is the authority for a Laravel project.
- **The vocabulary owns the mapping.** `technologies.observation_keys` (JSON, seeder-maintained) holds literal dependency names (`vue`) or constraint keys (`laravel/framework:>=12.0,<13.0`). Version technologies use half-open range keys so a single declared constraint can never evidence two version claims. A constraint key matches when the declared constraint's *floor* (the lowest version it admits, per the small `ComposerConstraint` parser — no new dependency) still satisfies the key. `*` and `dev-*` floors never match anything. Technologies without keys (Blade, SQL databases) stay declaration-only.
- **Declared and observed are independent assertions on one pivot row.** `project_technology` gains `is_declared` (creator's current selection) and `observed_at` (last confirming observation); `provenance` records the strongest current source (`observed` beats `declared` while the evidence stands). Creator sync and observation reconcile the same rows without deleting each other: withdrawing a declaration leaves an observed row in place; evidence going stale downgrades an observed row back to `declared` if the creator still claims it, or removes it if nobody holds it. `reviewed` is untouched for future curator review.
- **Observation never touches Verification or visibility.** Stack evidence and deployment evidence are separate trust contracts (ADR 0001). Failures surface in Creator Studio and product events only; nothing about a failure is public.
- **Two triggers, mirroring verification.** Creator-initiated `POST /projects/{project}/stack-observation` (owner-only, `project-observation` throttle) records `stack_observation_started` / `stack_observed` / `stack_observation_failed`; the daily `shipped:observe-project-stacks` command refreshes discoverable projects only and records no events (freshness maintenance is not builder behavior). A rate-limited or unreachable GitHub aborts the scheduled run so the next pass starts fresh.

## Considered Options

- Destructive `sync()` semantics for creator edits: rejected — every save would silently wipe observed evidence. The reconciliation rules exist because of this trap.
- Caret observation keys (`php:^8.1`): rejected after the overlapping-range failure — a `^8.4` floor satisfies `^8.1`–`^8.4`, marking four PHP versions observed at once. Half-open ranges (`php:>=8.1,<8.2`) are non-overlapping by construction.
- `composer/semver` dependency: rejected; the floor-resolution rule is small, needs no cross-driver edge cases, and a tested 150-line class is cheaper than a dependency approval plus API surface.
- Reading with the creator's stored OAuth token: rejected (stale tokens, evidence tied to session grants, scope questions).
- Letting observation failures affect `verification_status` or `is_public`: rejected — conflates two evidence types and punishes repos that moved or went private.

## Consequences

- Public technology pages and Discover facets now mix declared and observed records; the public project page marks observed chips ("Observed by Shipped") and summarizes provenance honestly when a set is mixed.
- GitHub API rate limits bound observation throughput (~5,000 repositories/day with an app token; far fewer anonymously). The command stops the run when rate-limited instead of burning the window on failures.
- The observation adds no new dependencies and no GitHub webhooks; re-observation is pull-based only.
- The evidence gate: measure whether observed markers change visitor behavior (built-with filter use, technology-page traffic) and whether creators re-observe after dependency changes before building any further enrichment (stars, commits, releases).
