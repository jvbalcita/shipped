# Experiment: Content Reports (Registry Integrity)

**Date opened:** 2026-08-28
**Owner:** Jack Vincent Balcita
**Target persona:** Every persona on the visitor side (learners, maintainers, clients evaluating builders); secondarily builders, who gain a corrections path for disputes about their own records
**Problem:** The roadmap's baseline contract (Milestone 0.2) requires "moderation, duplicate handling, reporting, editing, and removal," and the cross-cutting community-safety contract requires "reporting … before community features expand." A 2026-08-28 code audit found reporting and moderation entirely absent while comments, reviews, and cheers were already live — so the registry asked visitors to trust user-generated content that no one could flag, and gave builders no path to dispute duplicates or ownership beyond direct outreach.

**Hypothesis:** If any signed-in builder can report visible registry content (project, comment, or review) with a curated reason, and curators resolve every report with an explicit outcome, then data-quality incidents (broken links, spam, duplicates, misattribution) are surfaced and corrected faster than the daily verification recheck alone allows, because the people who notice problems can act on them immediately.

**Smallest test:** A "Report" action on every project, comment, and review, backed by a controlled reason vocabulary; a curator-only queue at `/reports` listing open reports with subject context; resolution that records an explicit outcome (no action / action taken) without ever auto-enforcing — curators act on content with the tools that already exist.

**Entry criteria:** No community-density gate — this completes an existing Build Now contract that is already overdue relative to the community features it was supposed to precede. All trust surfaces it protects (verification, stories, profiles, Built With) are shipped.

**Primary metric:** Time-to-resolution for open reports, from `content_report_submitted` and `content_report_resolved` product events (both server-side).

**Guardrail metrics:** Report volume per verified project (a spike signals either a corpus-quality problem or reason-list gaming); duplicate-report rate; false-report rate at resolution (share of reports dismissed as no action); curator triage time.

**Starting threshold:** Every report resolved or explicitly deferred within 7 days during the first outreach cohort; zero reports closed by automatic enforcement (there is no such path). Thresholds are provisional and revised at the first cohort check-in.

**Observed evidence:** (open)

**Decision:** Open.

**Roadmap impact:** Completes the reporting/moderation half of Milestone 0.2 and the community-safety contract; duplicate handling gains its review path via the `duplicate` and `ownership` reasons. No roadmap item moves between priority classes. Explicitly out of bounds: public report counters, automated takedowns, penalties or strikes, ban infrastructure, reporter-facing status tracking.

**Date closed:** —
