---
status: accepted
---

# Make the Creator Shipping Profile the primary public product object

Shipped will treat the individual Creator's public Shipping Profile as the durable product surface. The existing /@{creator:username} route remains canonical, but it now leads with identity, factual shipping counts, selected proof, and a derived Shipping History. Projects remain the evidence records behind that profile; Releases, approved Ship Stories, and Verification State make the record credible.

## Decision

- Keep the product individual-Creator only in this version.
- Derive Shipping History from currently discoverable Projects and their existing published Releases and approved Ship Stories.
- Allow a Creator to select up to three discoverable Featured Projects from Creator settings.
- Preserve a Featured Project's stored order when its Project becomes private or stale, while suppressing it from the public profile until discoverability returns.
- Make Creator identity a real navigation target from Project surfaces and provide a canonical profile share/OG image.

## Considered Options

- Keep Project pages as the primary object and add more Project-level engagement: rejected because it gives creators no durable view of their body of work or reason to return after a single launch.
- Build a separate portfolio editor: deferred because it duplicates existing Project, Release, Ship Story, and Verification records before the profile value is proven.
- Introduce teams and shared ownership: deferred because the first product question is whether an individual Creator's public shipping record earns shares and return visits.
- Add reputation scores or ranking: deferred because factual evidence is available now, while a score would introduce opaque incentives and moderation concerns.
- Add a social feed or notification expansion: deferred until profile sharing and repeat behavior show a clear return loop.

## Consequences

- Creator identity becomes the stable navigation and sharing layer while Project-first Discover remains intact.
- Public profile counts and history must use the same discoverability contract as Discover so private, stale, failed, or incomplete records cannot leak.
- A profile can be useful even when it has no Projects, and curation remains optional rather than blocking publication.
- The first success gate is behavioral evidence from the first 20 external Creators: profile shares/copies, referred visits, repeat profile visits, additional public Projects, and profile-to-Project navigation. The roadmap's initial signals are eight profile shares, five meaningful returns within 30 days, and three independent referrals.
- No analytics platform, notification system, Project Update entity, team model, or reputation score is introduced by this decision.
