# LaraShipped Product Roadmap

> A living product-strategy document for turning LaraShipped into the public record of what Laravel builders ship.

**Status:** Working strategy — validate before expanding  
**Last updated:** 2026-08-23
**Working product name:** LaraShipped  
**Primary horizon:** Earn the first 20 external builders, then let their behavior determine the next investment  
**Decision rule:** No feature advances because it sounds valuable; it advances because the previous milestone produced evidence that makes the next bet reasonable.

## How to use this document

This is a living roadmap, not a promise that every feature will be built. It is intended to help the product owner, designer, engineer, and future collaborators make the same decisions from the same evidence.

For every proposed change:

1. Identify the user problem and the smallest testable product bet.
2. Record the hypothesis before building.
3. Build only enough to expose the value or falsify the hypothesis.
4. Measure behavior and collect direct user feedback.
5. Update the decision log before adding adjacent scope.

The priority labels have these meanings:

| Label | Meaning |
| --- | --- |
| **Build Now** | Required to test the initial positioning and earn the first 20 external builders. |
| **Next** | A conditional investment after the initial builder/value loop shows real usage. |
| **Later** | Worth exploring once there is enough activity, density, or repeated demand to support it. |
| **Much Later** | A scale-dependent opportunity whose value depends on a meaningful dataset or network. |
| **Don't Build Yet** | Deliberately excluded until a specific piece of evidence changes the decision. |

The document uses LaraShipped as the current working name. Naming and public brand architecture should remain a separate decision from product validation; a brand change must not become a reason to delay the first-builder experiment.

## 1. Executive direction

### The decision

LaraShipped is worth continuing, but not as a smaller copy of Product Hunt. The current lack of external launches is evidence that the submission directory alone is not yet a compelling reason for a Laravel builder to invest time.

The recommended move is a focused repositioning:

> **LaraShipped is the public record of what Laravel builders ship.**

The first product bet is not “build a full Laravel social network.” It is:

> Give a Laravel builder a credible, verified, useful, and shareable record of a project they actually shipped — then test whether they want to bring more of their shipping history into it.

### The first milestone that matters

The near-term milestone is:

> **20 Laravel builders who are not part of the internal team or immediate product circle publish at least one meaningful project on LaraShipped.**

The 20-builder milestone is a learning instrument, not a vanity target. Each builder should help answer:

- Does verification make the listing more credible or desirable?
- Does a Ship Story make the product more useful than a link directory?
- Will builders claim and share a public profile?
- Does a generated launch asset give them a reason to distribute LaraShipped?
- Which kinds of Laravel builders care most about a permanent shipping record?
- What do builders do after their first launch, if anything?

### The core progression

~~~text
Foundation
    ↓
First 20 external builders
    ↓
Builder identity and distribution
    ↓
Repeat shipping and retention
    ↓
Community interactions
    ↓
Ecosystem intelligence
~~~

The arrows are gates. A later stage is not justified merely because it was imagined in the original product concept.

## 2. Product vision and repositioning

### Vision

LaraShipped should make the work of Laravel builders legible, credible, and discoverable.

It should become the place where a builder can say:

> “Here is what I have shipped, how I built it, what I learned, and the evidence that it is real.”

It should become the place where another Laravel developer can say:

> “Show me real Laravel products, the stacks behind them, and the people who shipped them.”

### Positioning shift

| Old mental model | Recommended mental model |
| --- | --- |
| Product Hunt for Laravel | The shipping reputation layer for Laravel builders |
| A place to submit a project once | A durable record of projects, stories, and shipping history |
| Competes on launch-day traffic | Competes on trust, context, builder identity, and Laravel-specific utility |
| Popularity and ranking are central | Evidence of shipping is central |
| The project is the destination | The project and the builder reinforce each other |

LaraShipped should not claim to replace Product Hunt, GitHub, a portfolio, or a Laravel showcase. It can complement them by representing something they each represent incompletely:

- Product Hunt represents launch visibility.
- GitHub represents code and collaboration activity.
- A portfolio represents selected work.
- LaraShipped can represent the public record of Laravel products actually shipped, along with the builder's story and verified context.

### Three product pillars

#### Discover

Find projects that are actually being built with Laravel. Browse by project type, Laravel version, frontend, database, infrastructure, packages, open-source status, and other trustworthy metadata.

#### Ship

Publish a project, explain the problem and the build, verify useful signals, generate a launch presence, and share it with an audience that understands Laravel.

#### Build reputation

Accumulate a durable, evidence-based record of projects shipped, stories published, technologies used, and meaningful milestones reached. Reputation should come from demonstrated work, not from an opaque score or a popularity contest.

### What LaraShipped must not optimize for yet

LaraShipped does not need to win on the size of its audience, the sophistication of its ranking algorithm, or the number of social interactions. With no meaningful external-user base yet, those are downstream outcomes.

The immediate question is simpler:

> **Will a Laravel builder want the LaraShipped presence enough to create, verify, complete, and share it?**

## 3. Current problem and evidence

### Known evidence

The current conversation provides four important signals:

1. LaraShipped has not yet attracted external project launches.
2. A generic “launch and discover Laravel projects” proposition is not obviously strong enough to overcome the cost of another submission.
3. Established launch and showcase channels already offer distribution, existing audiences, or Laravel project discovery.
4. The strongest differentiated ideas are verification, Ship Stories, builder identity, shipping history, stack-level discovery, shareable assets, and eventually ecosystem data.

These signals do not prove that LaraShipped is unwanted. They do prove that the current value proposition and acquisition motion have not yet been validated.

### Problem statement

For a Laravel builder who has shipped a real project, submitting to another directory can feel like unpaid promotion for someone else's product. The builder will act only if LaraShipped gives them an immediate, credible benefit that is difficult to get from a generic launch site.

The product therefore has to answer both sides of the marketplace:

- **Builder side:** “What do I get for creating this?”
- **Visitor side:** “Why should I trust and use this directory?”

Verification, useful context, builder profiles, and shareable artifacts address the builder side. Structured metadata, quality controls, and eventually aggregate trends address the visitor side. Community and analytics should come after there is enough content for them to be useful.

### Current uncertainty, stated honestly

This roadmap assumes that the product already has some foundation for project submission, project discovery, and Laravel-oriented verification. The actual state of those flows should be audited before implementation. The roadmap does not assume that any existing feature is polished, reliable, or validated merely because it exists.

The first engineering/product task is therefore a short baseline audit:

- What can a new builder complete without help?
- Which project fields are required, optional, or confusing?
- What verification evidence is actually available and safe to expose?
- Can a project be shared with a good preview?
- Is a builder identity created automatically, claimably, or not at all?
- Which events are currently observable for measuring the funnel?

## 4. Strategic principles

### 4.1 Strict YAGNI with explicit hypotheses

YAGNI does not mean building nothing until users request it. With zero or very few external users, nobody can reliably tell us what they need. It means making the smallest product bet that can teach us something important.

Before building a feature, write:

- the behavior we expect;
- the user and job it serves;
- the smallest test;
- the metric or interview signal that would support or reject it;
- the decision if the signal is weak.

### 4.2 Validate the value before expanding the network

Following, notifications, comments, reputation, and feedback markets become useful only when there is enough activity to create something worth returning to. First make one builder's project presence valuable in isolation.

### 4.3 Build for the builder's immediate benefit

Every initial feature should answer “what does the builder receive today?” Good early benefits include:

- a verified public presence;
- a permanent project page;
- a meaningful Ship Story;
- a builder portfolio entry;
- a shareable launch/profile card;
- a structured record of the technologies used.

### 4.4 Verification is a trust contract, not a decorative badge

Only show a verified state when the underlying evidence and freshness rules support it. Distinguish maker-declared information from system-observed information. Show the verification date or freshness state when it affects interpretation. Never imply that verification is a quality endorsement, security audit, or business validation unless the product actually performs that review.

### 4.5 Shipping beats popularity

Cheers can provide lightweight social proof, but they should not become the identity of the product. The durable signal is that a builder shipped something real and can explain it.

### 4.6 Manual operations are a valid early product capability

Concierge onboarding, hand-picked invitations, manual verification review, and curated discovery are appropriate while learning. Automate only after the workflow and demand are understood.

### 4.7 Data quality is part of the product

Broken links, stale deployments, misleading stack labels, duplicate projects, and unclaimed profiles will undermine the proposed trust advantage. Data freshness, moderation, reporting, and correction workflows are not optional polish.

### 4.8 Measure behavior, not stated enthusiasm alone

Interview feedback is necessary but insufficient. Pair “this sounds useful” with an observable action: publishing, completing verification, writing a story, claiming a profile, copying a share URL, returning to update a project, or referring another builder.

### 4.9 Preserve consent and attribution

Builders should understand what is public, what is verified, how metadata is sourced, and how a project or profile can be corrected, claimed, hidden, or removed. Shareable assets should point back to the canonical LaraShipped page and accurately attribute the builder.

## 5. Target users and personas

### Primary persona: the Laravel product builder

**Who:** An indie hacker, founder, internal product engineer, or small team that has shipped a Laravel application.  
**Job:** Make the project visible, explain what was built, and establish credibility.  
**Pain:** Generic directories provide little lasting value; launch posts disappear; a portfolio may not capture the building process.  
**What LaraShipped can provide:** A verified project presence, Ship Story, builder profile, stack record, and shareable launch assets.  
**Activation signal:** Publishes a project and completes at least one value-creating action beyond submission.

### Secondary persona: the Laravel freelancer or agency

**Who:** A professional who needs proof of shipped work for prospective clients or employers.  
**Job:** Show breadth, technical range, and credible outcomes without maintaining a bespoke portfolio for every project.  
**Pain:** Client work may be confidential; existing portfolios are selective and hard to keep current.  
**What LaraShipped can provide:** A claimable builder profile, permission-aware project history, verified public work, and a professional profile card.  
**Activation signal:** Claims a profile, adds multiple projects, or shares the profile externally.

### Secondary persona: the open-source package or tool maintainer

**Who:** A maintainer who wants to see real projects using a package or ecosystem tool.  
**Job:** Discover adoption, examples, and builders who use a technology in production.  
**Pain:** Package metadata and GitHub stars do not show the variety of real shipped products.  
**What LaraShipped can provide:** Built With discovery and eventually aggregate ecosystem intelligence.  
**Activation signal:** Browses or shares a technology collection, claims a project, or submits a project using the tool.

### Secondary persona: the Laravel learner and ecosystem observer

**Who:** A developer exploring architecture, product ideas, or the Laravel ecosystem.  
**Job:** Learn from real projects and their build stories.  
**Pain:** Tutorials show isolated examples; directories often show links without context.  
**What LaraShipped can provide:** Ship Stories, stack filters, project history, and eventually shipping trends.  
**Activation signal:** Reads a story, filters by stack, visits a builder profile, or returns for new updates.

### Future persona: the ecosystem researcher or Laravel-adjacent organization

**Who:** A package vendor, community publisher, event organizer, or ecosystem analyst.  
**Job:** Understand what Laravel builders are actually shipping.  
**Pain:** Available data is fragmented, self-reported, or not organized around shipped products.  
**What LaraShipped can provide:** Carefully governed aggregate data and reports.  
**Activation signal:** Uses a public trend report or requests a data partnership.

## 6. Core value propositions

| Audience | Promise | Why it is different | Proof to seek |
| --- | --- | --- | --- |
| Builder | “Turn a shipped Laravel project into a credible public presence.” | Verification, story, profile, and share assets are bundled around the builder's work. | Publication, verification, completion, sharing, and interview language. |
| Freelancer or agency | “Build a durable record of what you have shipped.” | A profile can accumulate projects and evidence over time. | Profile claims, multiple projects, external profile sharing. |
| Laravel developer | “Discover how real Laravel products are built.” | Stack metadata and Ship Stories add context beyond a thumbnail and link. | Search/filter use, story reads, saves or return visits. |
| Package maintainer | “Find real products using the technologies you care about.” | Built With data connects tools to shipped products. | Technology page visits, project referrals, maintainer interest. |
| Ecosystem observer | “See patterns in what Laravel builders ship.” | Aggregate data can be based on actual project records and declared provenance. | Report readership, citations, shares, data-quality feedback. |

## 7. Product loops

### The builder value loop

~~~text
Builder ships a project
    → publishes it on LaraShipped
    → receives trustworthy verification and a permanent page
    → adds a Ship Story and builder profile history
    → receives a launch/profile card or badge
    → shares it externally
    → receives useful visits, credibility, or feedback
    → returns to update the record or add another project
~~~

### The discovery loop

~~~text
Visitor discovers a real Laravel project
    → understands its story and stack
    → explores related projects or the builder
    → learns what Laravel builders are shipping
    → shares or recommends a project
    → attracts another builder or visitor
~~~

### The data loop

~~~text
More builders publish structured project records
    → Built With and verification data becomes more useful
    → discovery improves
    → aggregate trends become credible enough to publish
    → more builders want to be represented
~~~

The data loop is a long-term outcome. It should not be used to justify building analytics before the underlying records are complete, current, and permissioned.

## 8. Priority map

| Opportunity | Priority | Product reason | Entry condition | Initial exit signal |
| --- | --- | --- | --- | --- |
| Existing project submission and discovery | **Build Now** | The foundation for every other experiment. | Always. | A new builder can publish and a visitor can find a project reliably. |
| Laravel/Laravel Cloud verification | **Build Now** | The clearest trust differentiator and builder benefit. | Verification evidence and safe display rules are defined. | Builders complete verification and describe it as valuable or credible. |
| Ship Stories | **Build Now** | Adds context and learning value that a directory lacks. | Project publication works. | A meaningful share of builders complete a story and visitors read it. |
| Builder profiles and shipping history | **Build Now** | Creates a reason to belong and return beyond one listing. | A project can be associated with a builder identity. | Builders claim/share profiles or add more than one project. |
| Shareable launch/profile cards and launch kit | **Build Now** | Gives immediate distribution value without requiring LaraShipped to have a large audience. | Canonical project/profile pages and reliable metadata exist. | Builders copy, download, or share assets and referrals are measurable. |
| Built With metadata and discovery | **Build Now** | Makes project records useful to visitors and creates a structured data foundation. | A small controlled vocabulary can be maintained. | Visitors use filters and builders accurately complete stack metadata. |
| Cheers/social proof | **Build Now — keep simple** | Provides lightweight appreciation without making ranking the product. | Authenticated identity and abuse limits exist. | Cheers are used as a positive signal without dominating acquisition or incentives. |
| Manual seeding and concierge onboarding | **Build Now** | Solves the early marketplace chicken-and-egg problem and creates learning conversations. | A clear offer and target-builder list exist. | 20 external builders are invited and supported through the funnel. |
| Project updates/changelog | **Next** | Creates a reason to return after launch. | Initial builders demonstrate repeat shipping or ask for ongoing records. | Updates generate repeat visits or meaningful creator retention. |
| Following and notifications | **Next** | Makes updates discoverable, but only after there is something worth following. | Projects publish updates and users return. | Opt-in follows lead to useful repeat sessions without notification fatigue. |
| Feedback requests | **Later** | Could turn “look at my project” into “help me improve it,” but needs community density. | Enough relevant visitors can respond to requests. | Requests receive useful, timely responses in a constrained pilot. |
| Shipping graph | **Later** | A signature expression of shipping activity, but not the first value proof. | A trustworthy event model and enough activity exist. | Builders view/share it and it reflects meaningful events rather than vanity. |
| Transparent reputation signals | **Later** | Can summarize demonstrated shipping, but premature scoring would create false authority. | Profiles contain diverse, verified activity. | Users understand and value the signals without gaming or status anxiety. |
| Algorithmic trending, ranking, or recommendations | **Later** | May improve discovery at density, but cannot manufacture activity. | A large enough corpus and clear quality signals exist. | Discovery improves in controlled tests without rewarding manipulation. |
| Laravel ecosystem analytics | **Much Later** | Requires complete, current, and representative data. | Dataset and consent standards are mature. | Developers and ecosystem participants use and cite the insights. |
| State of Laravel Shipping | **Much Later** | A report is a product outcome, not a starting feature. | Sufficient verified project sample and defensible methodology. | The report earns sustained readership and informed external feedback. |
| Generic social network, complex gamification, or broad marketplace features | **Don't Build Yet** | These add operational complexity before the core value loop is proven. | Revisit only with direct demand and a validated core. | No current exit signal; keep out of scope. |

## 9. Roadmap phases and milestones

### Phase 0 — Make the promise testable

**Priority:** Build Now  
**Purpose:** Ensure a builder can understand the value proposition, publish a credible project, and receive a measurable outcome.

#### Milestone 0.1: Clarify the product promise

**Build / change:**

- Present LaraShipped as the public record of what Laravel builders ship.
- Explain the three pillars: Discover, Ship, Build Reputation.
- Make the immediate builder benefit visible before asking for a submission.
- Describe verification accurately as evidence, not endorsement.
- Add a clear invitation path for a builder who wants concierge onboarding.

**Why it matters:** A product cannot validate a proposition that visitors cannot repeat back. The landing page and submission flow should sell a durable builder presence, not merely ask for free directory content.

**Who benefits:** Every persona, especially builders who compare LaraShipped with Product Hunt, Made with Laravel, GitHub, or a personal portfolio.

**Hypothesis:** If builders understand the permanent, verified, shareable record they receive, more of them will start and complete the submission flow.

**Success signals:**

- Targeted visitors can explain the difference between LaraShipped and a generic launch directory in interviews.
- Invited builders start the flow without a long explanation from the product owner.
- The submission-start-to-publish funnel is measurable.

**Dependencies:** A working project page, a clear identity model, and basic event instrumentation.

**Acceptance / exit criteria:**

- The value proposition is consistent across the homepage, submission flow, project page, and share assets.
- A new visitor can identify what they get before creating an account or submitting a project.
- Every major step in the initial funnel has an observable event or a documented manual count.
- No copy implies that LaraShipped provides traffic or endorsement that it cannot yet provide.

#### Milestone 0.2: Baseline submission and discovery

**Build / change:**

- Keep project submission focused on the minimum information needed for a credible public record.
- Provide a public project page with canonical URL, project status, builder attribution, and clear source/provenance labels.
- Make projects discoverable through search and a small set of useful filters.
- Support moderation, duplicate handling, reporting, editing, and removal.
- Seed a small set of high-quality projects manually so new visitors see a living product.

**Why it matters:** Every later feature depends on project records being accurate, findable, and safe. A sparse but trustworthy directory is more useful for learning than a large set of incomplete records.

**Direct benefits:**

- Builders receive a durable home for their project.
- Visitors can find and understand projects.
- The product team can observe where the funnel fails.

**Hypothesis:** A short, reliable submission flow plus credible discovery is sufficient for a motivated builder to publish when paired with the new value proposition.

**Metrics:** Submission starts, completion rate, time to publish, project-page visits, search/filter use, broken-link rate, duplicate/report rate, and manual support time.

**Dependencies:** Identity, project lifecycle, moderation, canonical URLs, and analytics.

**Acceptance / exit criteria:**

- A builder can publish a project without internal intervention, while concierge support remains available.
- A visitor can find a project by name and at least one meaningful category or technology.
- A builder can correct or remove a project through a clear path.
- Projects have explicit states such as draft, published, needs attention, stale, or removed; “verified” is never used as a substitute for publication.
- The product owner can inspect the initial funnel without relying solely on server logs or memory.

### Phase 1 — Build the trust and identity wedge

**Priority:** Build Now  
**Purpose:** Give a builder something worth owning and sharing even before LaraShipped has a large audience.

#### Milestone 1.1: Laravel and Laravel Cloud verification

**Build / change:**

- Define a small set of verification checks that can be performed reliably.
- Distinguish Laravel-related evidence, deployment evidence, production availability, open-source status, and maker-declared claims.
- Display a verification state, evidence category, and freshness or last-checked context where appropriate.
- Define retry, failure, stale, and needs-attention states that are understandable to a builder.
- Provide a manual review or support path for legitimate projects that cannot be automatically verified.
- Keep secret credentials, private repository information, and sensitive infrastructure details out of public output.

**Why it matters:** Verification can answer the most important trust question: “Is this a real Laravel project that exists and is being shipped?” It is a defensible wedge because it is specific to the ecosystem and can improve both builder credibility and visitor confidence.

**Who benefits:** Builders seeking proof, visitors evaluating project legitimacy, clients/employers evaluating work, and future ecosystem researchers.

**Hypothesis:** A verified project presence is more valuable to builders than an unverified directory listing and increases willingness to complete/share the listing.

**Smallest test:** Offer verification to the first manually recruited builders, measure completion and sharing, and ask whether the badge or evidence changed their willingness to use LaraShipped.

**Metrics:** Verification start rate, pass rate, time to result, retry rate, stale rate, support cases, builder-reported trust value, and share rate for verified versus unverified projects.

**Dependencies:** Safe verification boundaries, project URLs, deployment/provider evidence, freshness rules, moderation, and transparent copy.

**Acceptance / exit criteria:**

- A builder can see what is being verified and what is not.
- A public viewer can distinguish verified, unverified, failed, and stale states.
- A verification failure does not erase the project or falsely label it as invalid.
- The system does not expose secrets or private data.
- At least several early builders complete verification or provide a clear, repeated reason why they do not.
- The team can explain why a verification result is trustworthy before using it in reputation or analytics.

#### Milestone 1.2: Ship Stories as first-class content

**Status:** Implementation complete — evidence gate open (2026-08-23)

**Shipped in:** Commit `8d9806a` on `jvbalcita/feat/25-ship-stories`.

**Delivered:**

- One private Ship Story per project, including legacy backfill and new-project draft creation.
- Creator Studio editing with explicit approval and validation for core prompts.
- Public project/profile excerpts, full story presentation, and dynamic OG story context.
- Public discovery, visibility, and scheduled publication gates requiring a complete approved story.

**Implementation evidence:** Ship Story tests pass (8 tests, 66 assertions); PHPStan, Pint, and changed Vue formatting checks pass. The full suite passes 384/385; the remaining failure is the pre-existing Inertia SSR/Vite test harness request to `localhost:5177`.

**Next evidence gate:** Measure external-builder story completion, reading, sharing, and return behavior before adding richer editorial or content features.

**Build / change:**

Create a concise, structured story format around the actual work:

- What problem does the project solve?
- Who is it for?
- What did the builder ship?
- What stack or Laravel capabilities were important?
- What was the hardest problem?
- What did the builder learn?
- What would they do differently?
- What are they shipping next?

The story should be more than an optional paragraph hidden below a listing. It should be readable as an educational artifact and link clearly back to the project and builder.

**Why it matters:** A link directory is easy to replicate. Build context creates learning value and gives a builder a reason to tell the story on LaraShipped rather than merely pasting a URL elsewhere.

**Who benefits:** Builders gain a narrative record; learners gain practical context; visitors can understand a project faster; package maintainers see how tools are used.

**Hypothesis:** Builders will complete a structured story when the prompts help them communicate expertise and the resulting page is worth sharing.

**Metrics:** Story start rate, completion rate, median field completion, story read depth, story shares, interview language about usefulness, and whether stories attract repeat visits.

**Dependencies:** Published projects, builder attribution, a readable project page, moderation/editing, and shareable canonical URLs.

**Acceptance / exit criteria:**

- A builder can publish a useful story without writing an essay.
- Visitors can scan the story and understand the project, problem, and build choices.
- A story can be edited or withdrawn without orphaning the project.
- Content moderation and reporting are available.
- The team has evidence that stories are completed or valued before adding more elaborate editorial or content features.

#### Milestone 1.3: Builder profiles and shipping history

**Build / change:**

- Create or attach a builder identity when a project is published.
- Provide a public, claimable profile with name, short description, avatar or identity field, links, and projects.
- Show shipping history as a chronological list of public projects, stories, and verified states.
- Display simple factual counts such as projects shipped, verified projects, open-source projects, or Laravel Cloud projects only when the underlying records support them.
- Allow a builder to claim, edit, hide, or correct their profile and project associations.
- Keep confidential or client work out of public history unless the builder chooses to include it.

**Why it matters:** The profile gives LaraShipped a reason to belong to a builder, not just a place to submit one project. It creates the foundation for future shipping reputation without prematurely inventing a score.

**Who benefits:** Builders, freelancers, agencies, employers/clients, and visitors who want to understand the person behind a project.

**Hypothesis:** Builders will value and share an accumulated record of shipped Laravel work more than a single launch listing.

**Metrics:** Profile-claim rate, profile completion, number of projects per claimed builder, profile views, profile shares, repeat project additions, and interviews about professional value.

**Dependencies:** Stable builder identity, project attribution, permissions, verification, Ship Stories, canonical URLs, and removal/correction workflows.

**Acceptance / exit criteria:**

- A builder can claim a profile without losing existing project history.
- A visitor can navigate project → builder and builder → project.
- The profile clearly distinguishes verified projects, self-declared projects, and unavailable/private work.
- The builder can share a profile that looks complete enough to represent them professionally.
- At least a subset of early builders claim or share profiles before expanding into reputation scoring.

### Phase 2 — Turn presence into distribution and learn from 20 builders

**Priority:** Build Now  
**Purpose:** Make the product useful before it has a large audience and run the first high-touch validation cohort.

#### Milestone 2.1: Shareable launch and profile cards

**Build / change:**

Provide a simple launch kit generated from a published project or claimed builder profile:

- Social/Open Graph preview with project name, builder, Laravel context, and verification state.
- A project launch card with canonical URL.
- A builder profile card with canonical URL.
- Optional README or website badge that links back to LaraShipped.
- A copyable short description or share text, provided as a convenience rather than an automated marketing claim.
- Clear handling for unverified, stale, private, and removed projects.

**Why it matters:** This directly addresses the chicken-and-egg problem. The builder receives a useful artifact even while LaraShipped's own audience is small. Every shared card can also create measurable distribution.

**Who benefits:** Builders get immediate promotional and portfolio value; LaraShipped gets qualified referrals; visitors receive consistent context.

**Hypothesis:** Builders will share or embed a good-looking, accurate asset when it makes their project look more credible or helps them maintain a public portfolio.

**Metrics:** Card preview success, asset downloads, URL copies, badge installs where observable, outbound share clicks, referred visits, new builder starts attributed to shared assets, and qualitative asset feedback.

**Dependencies:** Stable canonical pages, reliable metadata, verification status, profiles, image generation/rendering, and privacy rules.

**Acceptance / exit criteria:**

- A builder can generate and use an asset without manual design support.
- The asset always links to the canonical project or profile.
- The card does not overstate verification, popularity, or traffic.
- Referral traffic and originating asset type can be measured.
- At least several early builders use or share an asset before investing in a full distribution platform.

#### Milestone 2.2: Built With metadata and discovery

**Status:** Implementation complete — evidence gate open (2026-08-26)

**Delivered:**

- Curated six-group stack vocabulary (Laravel/PHP version, Frontend, Database, Infrastructure, Package; 32 seed entries) governed by an idempotent, production-safe `TechnologySeeder`. No hosting group: verification already implies Laravel Cloud.
- Provenance-ready pivot (`declared` / `observed` / `reviewed`); v1 records are creator-declared and labeled as such on the public project page.
- Launch Composer and Creator Studio stack picker with single-choice version groups enforced client- and server-side.
- Discover stack facets (AND across selections), stack chips on project cards, and a shared `DiscoverProjects` card service reused by every discovery surface.
- `/built-with` vocabulary index and per-technology pages with breadcrumb JSON-LD, thin-page `noindex`, sitemap inclusion, and stack-decorated Demo Launches for local development.

**Implementation evidence:** Built With tests pass (12 tests, 95 assertions); PHPStan, Pint, ESLint (changed files), Prettier, and the Vite build pass; the full suite passes 426/427 with the pre-existing Inertia SSR/Vite harness failure unchanged. Documented in ADR 0013.

**Next evidence gate:** Measure builder metadata completion, visitor use of stack filters and technology pages, and `/built-with` referral traffic before adding system-observed provenance (GitHub enrichment) or expanding the vocabulary.

**Build / change:**

- Let builders declare a controlled set of technologies used by a project.
- Start with high-value categories such as Laravel/PHP version, frontend, database, hosting/infrastructure, and notable packages.
- Make metadata provenance visible: builder-declared, system-observed, or manually reviewed.
- Make technologies clickable so visitors can browse related projects.
- Provide correction and stale-data paths.
- Prefer a small curated vocabulary over unlimited free-text tags.

**Why it matters:** Structured stack data turns LaraShipped from a list of names into a discovery and learning tool. It also creates the minimum foundation for later ecosystem analytics.

**Who benefits:** Visitors exploring technical approaches, package maintainers, builders looking for examples, and future ecosystem researchers.

**Hypothesis:** Visitors will use technology-level discovery and builders will tolerate the metadata effort when it improves their project's visibility and context.

**Metrics:** Metadata completion, filter usage, technology-page visits, project discovery from technology pages, corrections, duplicate tags, and builder perception of profile value.

**Dependencies:** Project submission, a controlled vocabulary owner, provenance rules, search/filtering, and data correction.

**Acceptance / exit criteria:**

- A visitor can find projects by at least the most important stack dimensions.
- A builder understands which values are declared versus verified.
- Incorrect or obsolete technologies can be corrected without destroying the project record.
- The initial vocabulary remains small enough to maintain manually.
- No analytics claims are published from metadata until completeness and representativeness are understood.

#### Milestone 2.3: Keep cheers as bounded social proof

**Build / change:**

- Keep the existing lightweight cheer concept if it already exists and is understandable.
- Limit abuse through identity, rate, and duplicate-action rules.
- Treat cheers as appreciation, not a measure of product quality, builder worth, or market demand.
- Avoid making a leaderboard or daily streak the primary home experience.
- Show aggregate social proof only where it helps a visitor decide what to explore, and do not let it displace verification or story context.

**Why it matters:** A small social action can make a launch feel acknowledged, but a voting system is not the differentiated product. Product Hunt already competes heavily on launch-day visibility and engagement.

**Who benefits:** Builders receive recognition; visitors get a lightweight way to respond; LaraShipped learns whether social acknowledgment supports sharing.

**Hypothesis:** Builders appreciate simple recognition when it is attached to a credible project presence, but they will not choose LaraShipped solely for points or rankings.

**Metrics:** Cheer rate per unique visitor, repeat cheer abuse, share rate for cheered projects, builder sentiment, and whether cheers correlate with meaningful discovery rather than manipulation.

**Dependencies:** Authentication, project identity, moderation, and clear copy.

**Acceptance / exit criteria:**

- A user understands what a cheer means.
- Duplicate or automated cheering is bounded.
- Cheers do not determine verification, reputation, or eligibility for future reports.
- The product can remove or ignore the feature without damaging the core project/profile loop.

#### Milestone 2.4: Recruit and study the first 20 external builders

**Build / change:**

- Build a list of real Laravel builders with recently shipped or actively maintained projects.
- Invite them personally with a value-led message, not a generic directory-submission request.
- Offer concierge onboarding: import the initial project details, help write the story, and complete verification together.
- Ask every builder for a short post-onboarding interview or asynchronous reflection.
- Track the cohort separately from internal or friendly seed projects.

**Why it matters:** A marketplace with no supply and no demand will not self-start. Manual recruitment tests the proposition and creates the conversations needed to learn what the product should become.

**Who benefits:** Early builders receive help and a differentiated presence; the product team receives high-quality evidence; future visitors receive a credible initial corpus.

**Hypothesis:** A personal invitation built around verified identity, shipping history, and a shareable launch presence will convert better than asking builders to “submit to our site.”

**Starting cohort thresholds:** These are initial decision thresholds, not universal benchmarks. Revise them after the first five interviews if they prove unrealistic or poorly matched to the product.

| Signal | Starting target for the first cohort |
| --- | ---: |
| External builders who publish at least one project | 20 |
| Builders who complete or resolve a verification path | 12 or more |
| Builders who publish a meaningful Ship Story | 10 or more |
| Builders who claim or share a profile | 8 or more |
| Builders who use, copy, or share a launch/profile asset | 8 or more |
| Builders who perform a meaningful return action within 30 days | 5 or more |
| Builders who independently refer another builder or visitor | 3 or more |

**Dependencies:** All Build Now capabilities, a target list, a simple CRM or tracking sheet, outreach time, and a consistent interview script.

**Acceptance / exit criteria:**

- “External” is defined and tracked consistently.
- The team can identify the step where each builder stopped or progressed.
- At least five builders can describe a concrete benefit they received, even if the aggregate thresholds are not met.
- Every failed conversion is classified as a product issue, proposition issue, trust issue, timing issue, or acquisition-channel issue.
- The next phase is selected from evidence, not from the original feature list.

### Phase 3 — Create reasons to return

**Priority:** Next  
**Entry gate:** The first 20-builder experiment shows that builders value the initial presence, and there is evidence of continuing project activity or explicit demand for updates.

#### Milestone 3.1: Project updates and changelog

**Build / change:**

- Allow a builder to publish meaningful updates to an existing project.
- Keep updates distinct from a new launch: release, major feature, open-source change, milestone, deployment, or learning.
- Show a chronological project timeline.
- Preserve the original launch and Ship Story while allowing the project to evolve.
- Let builders edit, hide, or correct updates.

**Why it matters:** Launching once is not enough to create retention. Continuous shipping is a natural behavior for product builders and can give LaraShipped a reason to be revisited without pretending every minor change is a new launch.

**Who benefits:** Builders maintain a living public record; followers and visitors understand project progress; learners see how products evolve.

**Hypothesis:** Builders who value the initial record will return to document meaningful progress when the update workflow is lighter than writing a new launch.

**Metrics:** Percentage of published projects with updates, update frequency, repeat project-page visits, time between launch and first update, update reads, and return rate among builders who publish updates.

**Dependencies:** Stable project ownership, timeline/event model, moderation, canonical pages, and optional notification infrastructure.

**Acceptance / exit criteria:**

- An update is clearly associated with one project and one builder.
- The project page communicates launch versus subsequent shipping.
- Builders can publish an update without rewriting the original project.
- The team sees repeat usage before adding a broader social feed.

#### Milestone 3.2: Following and notifications

**Build / change:**

- Allow an authenticated user to follow a project or builder only after a meaningful public record exists.
- Notify followers about relevant events such as a new project update or published Ship Story.
- Start with clear in-product notifications or a low-frequency email digest; do not create a high-volume notification stream.
- Provide obvious unsubscribe, mute, and manage-following controls.
- Measure whether notifications lead to useful return visits.

**Why it matters:** Following is a retention mechanism, not an acquisition strategy. Building it before updates exist would create empty social infrastructure and notification debt.

**Who benefits:** Visitors who want to keep up with work, builders who want an interested audience, and LaraShipped if it increases repeat engagement.

**Hypothesis:** Visitors will opt in to updates from a small number of builders or projects when the notifications are specific and infrequent.

**Entry criteria:** At least 10 active projects or builders with a realistic possibility of publishing updates, plus evidence of repeat visits or direct requests for update tracking.

**Metrics:** Follow conversion, notification opt-in, open/click rate where relevant, return visit rate, mute/unsubscribe rate, and update-to-notification usefulness feedback.

**Acceptance / exit criteria:**

- A user can see and control what they follow.
- Notifications are sent only for events the user opted into.
- The team can identify notification fatigue and turn down volume quickly.
- Following produces measurable return value before expanding into recommendations or a social graph.

### Phase 4 — Add community capabilities only when density supports them

**Priority:** Later  
**Entry gate:** There is a meaningful base of active builders and visitors, plus evidence that builders want interaction beyond views and cheers.

#### Milestone 4.1: Constrained feedback requests

**Build / change:**

- Let a builder mark a project as seeking feedback on one or two specific areas: UI/UX, pricing, landing page, architecture, performance, onboarding, idea, or another controlled topic.
- Display the request prominently on the project page and discovery surfaces.
- Provide a simple response path with quality and abuse controls.
- Start with a small pilot and manual moderation.
- Avoid promising a guaranteed response or building a marketplace before response supply is real.

**Why it matters:** “Help me improve what I am shipping” is a stronger community job than “look at my project.” It can give builders a reason to return and visitors a reason to participate.

**Who benefits:** Builders seeking useful critique, experienced Laravel developers who enjoy helping, learners who want to practice reviewing, and LaraShipped if it becomes a high-quality community destination.

**Hypothesis:** Specific feedback requests will receive more useful interaction than generic comments or popularity actions.

**Metrics:** Requests published, response rate, time to first response, response usefulness rating, repeat requester rate, moderation incidents, and the percentage of requests that lead to a project update.

**Dependencies:** Active community density, identity, moderation, notification controls, and clear community norms.

**Acceptance / exit criteria:**

- Requests are specific enough to answer.
- The pilot produces useful responses without excessive manual intervention.
- Builders can close or update a request.
- The product does not imply that feedback is expert advice or guaranteed.
- If response density is low, the feature is paused rather than expanded.

#### Milestone 4.2: Shipping graph

**Build / change:**

- Define a small, meaningful event vocabulary: project launch, verified milestone, Ship Story, project update, open-source release, or another explicitly documented shipping event.
- Show a builder-level timeline or calendar-like visualization only after enough events exist to make it meaningful.
- Let a builder filter or explain events.
- Do not infer activity from private data without permission.
- Do not reward raw event volume, streaks, or compulsive posting.

**Why it matters:** A shipping graph could become LaraShipped's signature expression of execution. It says “things shipped,” not “attention received.” It is also visually shareable and can make a long-term builder profile feel alive.

**Who benefits:** Builders who want a visual record of progress, visitors learning about a builder's work, and future employers or clients evaluating consistency.

**Hypothesis:** Builders will value a factual shipping timeline if it reflects meaningful milestones and can be understood at a glance.

**Metrics:** Graph views, profile share rate after graph exposure, event corrections, builder comprehension in interviews, and whether the graph drives a second project or update.

**Dependencies:** Stable event model, project history, updates, story publication, freshness rules, and enough activity per profile.

**Acceptance / exit criteria:**

- Every displayed event has a clear source and meaning.
- Sparse profiles are handled gracefully without manufacturing activity.
- Builders can correct or hide an event.
- The visualization is a summary of shipping, not a disguised leaderboard.
- Evidence shows it improves profile value before expanding it into streaks, badges, or gamification.

#### Milestone 4.3: Transparent reputation signals

**Build / change:**

- Start with factual, inspectable signals: projects published, verified projects, stories written, technologies used, years or dates of public shipping where the builder chooses to show them, and meaningful updates.
- Link each signal back to the underlying record.
- Explain what the signals do and do not mean.
- Avoid a single opaque score, “top builder” ranking, or irreversible status.
- Add stronger reputation concepts only if builders and visitors demonstrate a real need.

**Why it matters:** A public shipping record can become a professional reputation layer, but an invented score would create false authority and encourage gaming before the evidence model is mature.

**Who benefits:** Builders seeking credible proof, visitors evaluating expertise, and clients/employers who need context.

**Hypothesis:** Inspectable evidence is more trusted and useful than a composite reputation number.

**Metrics:** Profile engagement with signals, builder sharing, visitor understanding, correction/dispute rate, and whether signals affect a real decision such as contacting or following a builder.

**Dependencies:** Accurate profiles, verification, project history, data provenance, moderation, and privacy controls.

**Acceptance / exit criteria:**

- A visitor can explain how each signal was earned.
- A builder can correct or hide information where appropriate.
- There is no material incentive to create low-quality projects or spam updates.
- A composite score remains deferred unless evidence justifies it.

### Phase 5 — Use the dataset as an ecosystem product

**Priority:** Much Later  
**Entry gate:** LaraShipped has a meaningful corpus of current, structured, permissioned, and sufficiently diverse project records.

#### Milestone 5.1: Laravel ecosystem analytics

**Build / change:**

- Aggregate Built With data and project attributes only when completeness, sampling bias, and provenance can be explained.
- Show trends with counts and ranges rather than false precision.
- Separate public aggregate statistics from private or individual project information.
- Let builders understand how their data contributes and correct inaccurate source records.
- Invite maintainers and ecosystem publishers to challenge methodology.

**Why it matters:** Actual shipped-project data could help Laravel developers, package maintainers, educators, community publishers, and the Laravel ecosystem understand what is being built.

**Who benefits:** Ecosystem observers, tool maintainers, builders choosing technologies, and eventually the broader community.

**Hypothesis:** A trustworthy view of how Laravel products are engineered is valuable enough to attract recurring readers and external contributors.

**Metrics:** Report/page visits, repeat readership, citations or shares, correction rate, inbound requests, methodology feedback, and the proportion of records with sufficient metadata.

**Dependencies:** Dataset scale, data quality, consent and governance, stable taxonomy, analytics definitions, and a methodology owner.

**Acceptance / exit criteria:**

- Every published metric has a documented denominator, date window, and data source.
- The product can state what the dataset does not represent.
- Privacy and aggregation thresholds prevent singling out small or sensitive groups.
- External readers find the result useful enough to return or cite.

#### Milestone 5.2: State of Laravel Shipping

**Build / change:**

Create a periodic, editorially reviewed report that describes patterns in actual shipped Laravel projects, such as:

- frontend and interaction approaches;
- databases and infrastructure;
- package adoption;
- project types and business models;
- open-source versus closed-source participation;
- deployment patterns;
- changes over time.

The report should include methodology, limitations, and a clear distinction between LaraShipped records and the entire Laravel ecosystem.

**Why it matters:** This could become the long-term moat and a valuable public contribution, but publishing a weak or overconfident report would damage trust.

**Who benefits:** Laravel developers, maintainers, community publishers, educators, event organizers, and ecosystem organizations.

**Hypothesis:** A well-governed report based on real project records can become an annual or periodic reference point for Laravel shipping trends.

**Metrics:** Sustained readership, citations, qualified inbound interest, corrections handled, and whether builders want their projects represented in the next edition.

**Dependencies:** All prior data-quality and governance work, sufficient sample size, report editorial capacity, and a defensible methodology.

**Acceptance / exit criteria:**

- A reviewer can reproduce the major claims from documented data.
- The report states its selection bias and limitations.
- The product has enough data to avoid turning a small convenience sample into an ecosystem-wide claim.
- The report creates value independent of a launch-day spike.

## 10. Feature classification at a glance

### Build Now

- Clarify the “public record of what Laravel builders ship” positioning.
- Stabilize project submission, project pages, and discovery.
- Add moderation, correction, removal, and basic analytics.
- Make Laravel/Laravel Cloud verification trustworthy and understandable.
- Make Ship Stories first-class.
- Create builder profiles with shipping history.
- Add a small, controlled Built With metadata system and discovery.
- Generate shareable project/profile cards and a simple launch kit.
- Keep cheers bounded and secondary.
- Manually seed projects and recruit the first 20 external builders.

### Next

- Project updates and changelog.
- A lightweight project timeline.
- Following and notifications, only after updates and repeat visits exist.
- Profile/project claim and correction improvements if the initial cohort exposes friction.
- Embeddable verified or shipped badges if builders ask for them and share assets successfully.
- Curated collections or editorial discovery operated manually before algorithmic ranking.

### Later

- Constrained feedback requests.
- A meaningful shipping graph visualization.
- Transparent evidence-based reputation signals.
- More sophisticated discovery facets after taxonomy quality is proven.
- Algorithmic trending or recommendations only after enough activity and quality data exist.

### Much Later

- Laravel ecosystem analytics.
- State of Laravel Shipping.
- Public data exports or an API, if an external ecosystem use case requires them.
- Partnerships with package maintainers, community publishers, or ecosystem organizations.

### Don't Build Yet

- A full generic social network with feed, direct messages, and broad comment infrastructure.
- A complex follower graph before projects publish updates.
- An opaque reputation score or “best builder” leaderboard.
- Streaks, points, achievement systems, or badges whose main purpose is compulsion.
- A feedback marketplace with guarantees or matching before there is response density.
- AI recommendations, AI-generated Ship Stories, or automated content farms.
- Paid acquisition or sponsorship packages before the builder value proposition converts organically through direct outreach.
- A custom portfolio builder, custom domains, or extensive profile themes before the basic profile is shared.
- Broad integrations with every source, provider, or code host before the verification contract is reliable.
- State-of-the-ecosystem claims before the data is sufficiently large, current, and representative.

## 11. First 20 external builders: acquisition strategy

### Acquisition objective

Do not ask people to donate a listing to a new website. Offer a concrete early-builder benefit:

> “We are building the public record of what Laravel builders ship. We would like your project to be one of the first verified launches, and we will help you create a polished project page, Ship Story, builder profile entry, and shareable launch asset.”

### Who to recruit

Build a deliberately varied cohort:

- indie SaaS or product builders;
- Laravel freelancers and agencies;
- open-source maintainers with real Laravel applications or tools;
- internal product teams willing to share a public project;
- builders who recently launched or materially updated a project;
- a mix of Laravel stacks, deployment choices, project sizes, and levels of public visibility.

Avoid making the first cohort only close friends or the same type of project. Friendly seed projects are useful for presentation, but the validation cohort must be external enough to reveal whether the value proposition travels.

### Outreach sequence

| Step | Action | Learning goal |
| --- | --- | --- |
| 1. Identify | Find a real Laravel project with a public URL and a builder who can be reached directly. | Which project types and builder personas are most likely to care? |
| 2. Personalize | Mention the project or shipping context, not a generic directory pitch. | Does the message resonate with the builder's actual work? |
| 3. Offer | Explain the verified presence, story, profile, and launch asset they receive. | Which benefit creates the first “yes”? |
| 4. Concierge | Help import or complete the initial record while observing friction. | What is confusing, tedious, or trust-sensitive? |
| 5. Verify | Complete the verification path together and explain the result. | Does verification increase perceived credibility? |
| 6. Share | Give the builder a polished asset and ask how they would use it. | Will they distribute LaraShipped without being pushed? |
| 7. Interview | Ask what they would miss if the page disappeared and what they expected next. | What is the actual job-to-be-done? |
| 8. Refer | Ask whether another builder should be represented. | Does the product naturally create qualified referrals? |

### Suggested invitation

> I’m building LaraShipped as a public record of what Laravel builders ship — not just another launch directory. I’d love to include your project as one of the first verified examples. I can help set up the project page, capture the story behind it, add it to your builder profile, and give you a shareable launch card. The goal is to make the page useful to you even while the community is still small. Would you be open to trying it with [project]?

### Interview questions

Ask about behavior and concrete value:

1. What made you agree to include this project?
2. What part of the LaraShipped presence was most useful?
3. What did you expect to receive but not receive?
4. Would you put the project or profile in a README, portfolio, social post, or proposal? Why or why not?
5. Did verification change how credible the page felt?
6. Was writing the Ship Story worth the time?
7. What would make you return next month?
8. What would make you add another project?
9. Who else should be represented on LaraShipped?
10. If LaraShipped disappeared tomorrow, what would you lose?

### Acquisition guardrails

- Do not buy traffic before the proposition converts in direct conversations.
- Do not count internal projects as external-builder validation.
- Do not judge the product only by signups; require publication and a value action.
- Do not over-promise traffic, leads, or endorsement.
- Do not hide the fact that the product is early.
- Record rejection reasons, not only wins.

## 12. Measurement system

### North-star metric

**External Verified Shipping Builders (EVSB):** the number of distinct external builders who have at least one publicly published project with a current, successful verification state during the measurement period.

This metric is intentionally narrower than total registrations, page views, cheers, or project count. It measures whether real external builders are represented in the proposed trust layer.

### Supporting metrics

| Area | Metrics | Why it matters |
| --- | --- | --- |
| Acquisition | Qualified invitations, invite acceptance, signup, submission start, referral source | Shows whether the message and channel reach the right people. |
| Activation | Project published, verification started/completed, story published, profile claimed, asset used | Shows whether a builder receives the promised value. |
| Discovery | Search/filter use, technology-page visits, project-to-project navigation, story reads | Shows whether structured content helps visitors. |
| Distribution | Share/copy/download, referred sessions, referred builder starts, profile link use | Tests the growth loop while the audience is small. |
| Retention | Return visits, updates, second project, follow, notification interaction | Shows whether LaraShipped is more than a one-time submission. |
| Trust and quality | Link health, stale verification, corrections, reports, duplicates, false-positive review | Protects the credibility advantage. |
| Community | Cheers, feedback requests, response rate, response quality, moderation incidents | Tests interaction without confusing popularity for value. |
| Operations | Concierge time per builder, verification review time, support requests, email unsubscribes | Shows whether the workflow can scale responsibly. |

### Event vocabulary for measurement

The exact implementation is intentionally unspecified, but the product should be able to observe events equivalent to:

~~~text
landing_viewed
submission_started
submission_published
verification_started
verification_passed
verification_failed
verification_marked_stale
ship_story_started
ship_story_published
builder_profile_claimed
builder_profile_shared
project_card_generated
project_card_shared_or_copied
referral_visit_received
built_with_filter_used
cheer_given
project_update_published
project_followed
notification_opened
feedback_request_published
feedback_response_submitted
shipping_graph_viewed
~~~

Events should be attributable to a project, builder, source, and cohort where the user has consented and the data is needed for the decision. Do not collect more personal data than the experiment requires.

### Qualitative evidence

Every cohort review should include:

- direct builder quotes or paraphrased evidence;
- what builders actually shared or used;
- repeated objections and confusion;
- reasons for abandoning submission or verification;
- what users expected the product to do next;
- whether the product was useful without LaraShipped having large traffic.

## 13. Decision gates

### Gate A — Is the initial promise understandable?

**Review after:** Baseline copy and the first targeted conversations.  
**Continue when:** Builders can explain the difference between LaraShipped and a generic launch directory, and at least some start the submission flow from the value proposition alone.  
**Pivot when:** Builders like the concept but cannot identify a personal benefit; change the offer and positioning before adding features.  
**Stop or pause when:** Multiple messages and targeted conversations with the right builders produce no credible job-to-be-done.

### Gate B — Does a builder receive value from the initial presence?

**Review after:** The first 20 external builders.  
**Continue when:** The cohort reaches most starting thresholds, especially publication, verification, profile/asset use, and at least some return or referral behavior.  
**Pivot when:** One wedge works but another does not. Examples: builders want profiles but not launch listings; verification is valued but stories are not; launch assets are shared but the submission flow is too hard. Narrow the product around the strongest observed job.  
**Stop or pause when:** After two focused recruitment cycles and meaningful concierge support, builders still do not publish, use, share, or describe a concrete benefit, and interviews reveal no urgent adjacent problem worth solving.

### Gate C — Is there a reason to return?

**Review after:** Project updates are available to early builders.  
**Continue when:** Builders publish updates, return to project pages, or explicitly request update tracking.  
**Pivot when:** Builders value the static record but not updates; keep LaraShipped as a durable portfolio/verification product and do not add a notification-heavy community layer.  
**Stop or pause when:** No project activity or user demand supports an ongoing record.

### Gate D — Is community interaction useful?

**Review after:** A small feedback-request or follow pilot.  
**Continue when:** Specific requests receive useful responses and participants return.  
**Pivot when:** Visitors read but do not respond; invest in educational content and discovery rather than forcing a community mechanic.  
**Stop or pause when:** Interaction requires unsustainable manual labor or attracts low-quality/abusive behavior without clear user value.

### Gate E — Is the dataset trustworthy enough for ecosystem claims?

**Review before:** Any analytics page or State of Laravel Shipping report.  
**Continue when:** Metadata completeness, freshness, sample size, consent, and selection bias can be explained.  
**Pivot when:** The data supports useful niche reports but not ecosystem-wide claims; publish narrow, clearly scoped insights.  
**Stop or pause when:** The dataset cannot support defensible conclusions or the governance burden outweighs the value.

### Decision outcomes

| Outcome | Meaning | Required action |
| --- | --- | --- |
| Continue | The current wedge produces meaningful behavior and learning. | Advance only the next gated capability; keep the rest deferred. |
| Narrow | One user/persona/job is clearly stronger than the broad vision. | Rewrite the positioning and roadmap around the strongest job. |
| Pivot | The proposed product is not working, but a nearby problem is repeatedly evidenced. | Define a new small experiment with a fresh hypothesis. |
| Pause | Evidence is inconclusive or operationally too costly. | Stop feature work, preserve learning, and revisit only with new evidence. |
| Stop | Repeated targeted experiments show no urgent job or differentiated value. | Archive the roadmap or repurpose useful data/assets; do not continue adding features by inertia. |

## 14. Risks and mitigations

| Risk | Why it matters | Mitigation |
| --- | --- | --- |
| No audience for launch-day distribution | LaraShipped cannot yet compete with Product Hunt's reach. | Give builders verification, a profile, a story, and share assets that are useful independently of LaraShipped traffic. |
| Builders see submission as unpaid promotion | A directory-only value proposition creates low motivation. | Lead with durable identity and evidence; offer concierge onboarding. |
| Verification over-promises trust | A bad badge would damage the core differentiator. | Define checks, provenance, freshness, failure states, privacy limits, and manual review. |
| Sparse marketplace | Empty discovery surfaces make the product look inactive. | Seed a curated initial corpus and recruit a varied first cohort manually. |
| Stale or inaccurate data | The public record becomes untrustworthy. | Add correction, stale, removal, duplicate, and link-health workflows from the start. |
| Reputation becomes gamified | Scores invite manipulation and distract from shipping. | Start with inspectable facts; defer composite scores and leaderboards. |
| Community features launch too early | Empty feeds and unanswered feedback requests create disappointment. | Gate follows, notifications, feedback, and comments on density and observed demand. |
| Analytics claims exceed the data | A biased sample could be mistaken for the whole Laravel ecosystem. | Document denominators, sampling limits, consent, date ranges, and methodology. |
| Manual onboarding does not scale | Concierge work can become a hidden operating cost. | Use manual work for learning, record friction, and automate only repeated stable steps. |
| Privacy or client confidentiality concerns | Builders may have legitimate reasons not to expose work. | Make public/private states, claim/removal, selective history, and attribution explicit. |
| Scope creep | A long opportunity list can become an unvalidated backlog. | Require a hypothesis, smallest test, entry gate, and exit decision for every new bet. |

## 15. Cross-cutting product contracts

These are product-level contracts that should remain true regardless of the eventual technical implementation.

### Project and builder identity

- Every public project has one canonical page and a clear builder association.
- A builder can claim, correct, hide, or remove their profile or project association.
- Project history is not silently rewritten when a status changes; important state changes remain understandable.
- Duplicate projects and name collisions have a review path.

### Verification and provenance

- Verified means that defined evidence checks passed at a defined time.
- Verified does not mean secure, profitable, popular, high quality, or endorsed unless separately established.
- Maker-declared, system-observed, and manually reviewed data are distinguishable.
- Stale, failed, and unavailable checks have user-readable states.

### Sharing and attribution

- Every project and profile asset links to a canonical LaraShipped URL.
- Assets accurately reflect current verification and visibility state.
- The builder controls whether a project or history item is public.
- Removing a project or profile does not leave a misleading public badge behind.

### Community safety

- Cheers, follows, feedback, and future comments are attributable to an identity or bounded anonymous policy.
- Rate limits, reporting, moderation, and removal are available before community features expand.
- Notifications are opt-in, specific, and reversible.

### Data and analytics

- Internal metrics support decisions but do not automatically become public statistics.
- Public aggregate claims include scope, date, denominator, and limitations.
- The product can correct source data and record when a metric changed because of a correction.

## 16. Living-document operating process

### Update cadence

Update this document:

- after every five external-builder conversations or launches;
- at the end of each experiment;
- when a decision gate is reached;
- when a metric definition changes;
- when a feature is promoted, demoted, split, or removed;
- before starting a new phase.

Do not update the roadmap merely because an idea feels exciting. Add the evidence and the decision that the evidence supports.

### Decision log

| Date | Decision | Evidence | Impact | Revisit when |
| --- | --- | --- | --- | --- |
| 2026-08-22 | Reposition around “the public record of what Laravel builders ship.” | No external launches and a weak reason to submit to a generic directory; verification, stories, identity, and shipping history are more differentiated. | Build Now centers on builder value and trust, not a larger launch feed. | After the first 20 external builders. |
| 2026-08-22 | Apply strict YAGNI and validation-first sequencing. | Network and analytics features depend on activity that does not yet exist. | Every phase has entry and exit criteria; later features remain conditional. | At each phase gate. |
| 2026-08-22 | Keep cheers bounded and secondary. | Popularity mechanics do not create LaraShipped's strongest differentiation. | No leaderboard or complex gamification in the initial roadmap. | If users show a specific, non-gameable need for social proof. |
| 2026-08-22 | Treat profiles, stories, verification, and share assets as the initial wedge. | They provide immediate value even while the audience is small. | First 20-builder experiment measures completion, sharing, and repeat use. | After cohort evidence. |

### Experiment record template

Copy this template for each new experiment:

~~~markdown
## Experiment: [short name]

**Date opened:** [YYYY-MM-DD]
**Owner:** [person]
**Target persona:** [persona]
**Problem:** [specific user problem]
**Hypothesis:** If [we do X] for [this user], then [we expect Y behavior] because [reason].
**Smallest test:** [the smallest product or manual test]
**Entry criteria:** [why this is justified now]
**Primary metric:** [one metric]
**Guardrail metrics:** [quality, privacy, or operational constraints]
**Starting threshold:** [what would support the next decision]
**Observed evidence:** [numbers and user feedback]
**Decision:** Continue / narrow / pivot / pause / stop
**Roadmap impact:** [what moves, and why]
**Date closed:** [YYYY-MM-DD]
~~~

### Cohort review template

At the end of a builder cohort, record:

- number invited, accepted, published, verified, and retained;
- number who completed a Ship Story and claimed a profile;
- number who used or shared an asset;
- number who returned or added another project;
- top three conversion blockers;
- top three benefits builders described without prompting;
- examples of external sharing or referral;
- what should be stopped, simplified, or promoted;
- the next smallest experiment.

### Definition of “done” for this roadmap

The roadmap is not done when all listed features exist. It is working when LaraShipped has demonstrated a repeatable, differentiated reason for Laravel builders to create and maintain a public record of what they ship.

The next version of this document should be more evidence-based than this one. That is the intended sign of progress.
