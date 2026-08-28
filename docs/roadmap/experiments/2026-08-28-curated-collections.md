# Experiment: Curated Collections

**Date opened:** 2026-08-28
**Owner:** Jack Vincent Balcita
**Target persona:** Laravel learner / ecosystem observer (visitor side), secondarily the builder whose project gains a curated placement
**Problem:** Shipped's verified, structured project records answer "which real Laravel products use X and can prove it" better than any competitor, but the only visitor surfaces for that data are raw filters. Product Hunt curates by popularity; Made with Laravel does not curate at all. Nobody curates by *evidence*.

**Hypothesis:** If we publish curator-written collections grounded in verified, structured project data (Cloud verification, Built With stack, Release history), then visitors arriving from search and shares will click through into member projects at a measurable rate, because evidence-picked context answers "which real products use X" better than self-reported or vote-ranked alternatives.

**Smallest test:** Hand-curate a small set of collections (title, narrative, ordered member projects) on new public pages at `/collections/{slug}`, operated entirely by the site curator through config-gated screens. No creator-made lists, no rules engine, no votes.

**Entry criteria:** Every Build Now surface (verification, Ship Stories, profiles, Built With, Launch Kit) is shipped; the product audit on 2026-08-28 found Phase 3 retention mechanics (Releases, Follows, Notifications) already implemented, so the roadmap's strict sequencing was already overtaken by the code. Collections are the roadmap's sanctioned manual step ("operated manually before algorithmic ranking") and need zero community density.

**Primary metric:** Collection session → member-project click-through rate, from `collection_viewed` (server-side) and `collection_project_clicked` (client-side) product events.

**Guardrail metrics:** Curator time per collection (must stay under one hour); stale-member visibility incidents (must be zero — members are suppressed while non-discoverable); collection-page bounce when member count is low.

**Starting threshold:** 3+ live collections, 100+ collection sessions in the first 30 days after outreach begins, ≥ 35% click-through to member projects. These thresholds are explicitly provisional and are revised at the first cohort check-in like every other gate.

**Observed evidence:** (open)

**Decision:** Open.

**Roadmap impact:** "Curated collections or editorial discovery operated manually" moves from Next to Build Now, pulled forward ahead of cohort evidence per the 2026-08-28 decision-log entry. All excluded scope (creator-made lists, rules engine, cheers on collections, per-project blurbs, member consent flow, trending, follows) remains out of bounds.

**Date closed:** —
