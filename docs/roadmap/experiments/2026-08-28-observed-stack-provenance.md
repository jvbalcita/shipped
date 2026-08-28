# Experiment: GitHub-Observed Stack Provenance

**Date opened:** 2026-08-28
**Owner:** Jack Vincent Balcita
**Target persona:** Laravel developer / learner discovering by stack (visitor side), secondarily the builder whose claims become independently confirmed
**Problem:** Shipped's Built With data was 100% creator-declared — the same trust level as Made With Laravel's self-reported listings — while Product Hunt carries no stack data at all. The roadmap's own contract ("maker-declared, system-observed, and manually reviewed data are distinguishable") was schema-modeled but never exercised: every `project_technology` row said `declared`.

**Hypothesis:** If Shipped reads each project's public repository manifests and publicly marks the Technologies the code confirms ("Observed by Shipped"), then visitors use stack evidence in their browsing decisions and builders treat the observed marker as a reason to keep their repository link and Built With accurate, because independently confirmed stack claims are something no competing registry offers.

**Smallest test:** Observation pass over root `composer.json` + `package.json` matched against curated observation keys, surfaced as an observed marker on the public project page chips, an owner-triggered button in Creator Studio, and a daily freshness pass over discoverable projects. No stars/commits/release enrichment, no webhooks, no private-repo access.

**Entry criteria:** All Build Now trust surfaces shipped (verification, stories, profiles, Built With, Launch Kit, collections); first-party product-event instrumentation live since M2.1. The M2.2 gate ("measure before adding system-observed provenance") is satisfied the same way as the collections pull-forward: the gates govern *measuring*, and the declared-only corpus cannot produce the gate's signal without the M2.4 cohort — while observed provenance directly improves the two metrics the gate measures (builder metadata completion, visitor filter/technology-page use) and sharpens the concierge outreach pitch ("your stack is independently confirmed"). Decision-log entry 2026-08-28 (second) records the pull-forward.

**Primary metric:** Share of discoverable projects with ≥ 1 observed technology (`stack_observed` events, `observed_at` coverage), and `built_with_filter_used` / technology-page visit rates for projects with observed markers versus declared-only.

**Guardrail metrics:** Observation failure rate (repo moved/private/composer missing) — reported to the creator, never public, and never touching `verification_status` or `is_public`; GitHub API consumption per daily run; declared-vs-observed disputes (a creator removing a technology that observation re-adds).

**Starting threshold:** ≥ 60% of discoverable projects observed at the first cohort check-in; visitor stack-filter usage on observed projects at or above declared-only projects; zero incidents of observation altering visibility or verification. Provisional until the first cohort review.

**Observed evidence:** (open)

**Decision:** Open.

**Roadmap impact:** System-observed Built With provenance moves from gated-Next to Build Now (implemented ahead of cohort evidence) per the 2026-08-28 decision-log entry. Still excluded: GitHub stars/commits/release enrichment, webhooks, private repository reads, any use of creator OAuth tokens for observation, and publishing aggregate claims from the metadata (Gate E).

**Date closed:** —
