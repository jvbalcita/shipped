---
status: accepted
---

# Built With is a curated Technology vocabulary with provenance, not free-form tags

Shipped will let Creators declare the stack behind a Project through a controlled vocabulary of Technologies, each classified into a Stack Group (Laravel version, PHP version, Frontend, Database, Infrastructure, Package). The declaration is the Built With record: creator-declared today, with room for system-observed and manually reviewed entries later. Visitors can filter Discover by stack and browse a page per Technology.

## Decision

- Keep the vocabulary curated and seeder-governed (`TechnologySeeder`, idempotent on slug; production runbook: `db:seed --class=TechnologySeeder --force`). No free-form Technology entry.
- Store declarations on a `project_technology` pivot carrying a `provenance` column (`declared` / `observed` / `reviewed`, default `declared`). v1 writes only `declared`; the column exists so GitHub-observed enrichment can attach without a migration.
- Keep version groups (Laravel version, PHP version) single-choice per Project, enforced by the `OneTechnologyPerVersionGroup` rule and the picker interaction; other groups allow multiple selections up to the request ceiling of 12.
- Give each Technology a public page at `/built-with/{technology:slug}` plus a `/built-with` index. Both reuse the shared `DiscoverProjects` card listing; thin pages (zero discoverable Projects) are `noindex` until real projects back them. Used Technologies enter the sitemap.
- Show provenance on the public Project page ("Declared by the creator") so maker-declared data is never mistaken for system-observed data.
- Keep the free-form Tags and the single curated Category untouched: Tags stay topical descriptors, Category stays the product classification.

## Considered Options

- Absorb Tags into the curated vocabulary: rejected because it deletes a working free-form surface, forces a data migration, and conflates topical labels with stack facts.
- Fixed stack columns on `projects`: rejected because Packages and Infrastructure are many-per-project and technology pages need real slugged entities rather than hardcoded enums.
- A hosting Stack Group: dropped because Verification already implies Laravel Cloud for every discoverable Project — the facet would be constant. Infrastructure (Redis, Meilisearch, S3, Reverb, …) carries the real variance.
- System-observed GitHub data (stars, last commit) in v1: deferred; it couples the first release to GitHub API limits and the verification trust contract. The provenance column keeps the door open.
- Admin-managed vocabulary: deferred; the seeder is enough at 32 entries and matches the CategorySeeder operating pattern.

## Consequences

- The vocabulary needs an owner and deliberate growth; every addition becomes a public filter, a technology page, and (implicitly) future analytics vocabulary.
- Every discovery surface must keep using the shared `DiscoverProjects` card shape so Discover and technology pages cannot drift apart.
- `hosting` may return as a group if non-Cloud discoverable records ever exist (for example open-source tools verified differently).
- Public aggregate claims remain off-limits until metadata completeness and sampling bias are understood, per the roadmap's data-quality gate.
- The first success gate is behavioral: builder metadata completion, visitor use of stack filters and technology pages, and referral traffic to `/built-with` URLs.
