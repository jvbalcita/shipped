# Shipped

Shipped is the public record of what Laravel builders ship. It gives creators a public place to present the applications they have shipped and the releases that evolve them.

## Language

**Builder**:
The person Shipped exists for — someone who has shipped a real Laravel product. This is the positioning term used in product language and copy; it is implemented by the Creator record.
_Avoid_: Maker, founder, author

**Creator**:
The individual user, identified by a chosen public username, who owns a project and presents it to the community.
_Avoid_: Owner, account

**Shipping Profile**:
The canonical public page for an individual Creator at /@{creator:username}. It leads with the Creator's identity and factual record, then presents discoverable Projects, selected Featured Projects, approved Ship Stories, published Releases, and verification evidence.
_Avoid_: Portfolio builder, resume, social profile

**Shipping History**:
A derived, project-granular chronology on a Creator's Shipping Profile. Each entry represents one currently discoverable Project and is ordered by its latest published Release, then Launch Date, filing time, or creation time; the Project page remains the complete Release chronology.
_Avoid_: Timeline, activity feed, update stream

**Featured Project**:
A Creator-selected, currently discoverable Project shown near the top of the Creator's Shipping Profile. Up to three public selections are displayed; a selection's stored order is retained when its Project becomes private or stale, but it is suppressed until the Project is discoverable again.
_Avoid_: Pinned post, portfolio item, promoted listing

**Username**:
A unique, user-chosen public identifier, used for public routing (`/@{creator:username}`) and community identity. Chosen at registration, or **claimed** once during provider sign-up onboarding — an auto-assigned handle stays unclaimed until the creator first picks one. Changeable after claiming, with a reservation hold-window that releases the old username so it cannot be immediately squatted.
_Avoid_: Handle, login, screen name

**Title**:
A creator's self-described role or occupation shown on their public profile; free-text, defaults to "Creator" at registration.
_Avoid_: Role, occupation, position

**Links**:
A typed collection of a creator's external URLs (website, github, twitter, linkedin) stored as a single JSON array on the creator's profile.
_Avoid_: Socials, social links, contacts

**Location**:
A free-text, optional description of where a creator is based, shown on their public profile. Not structured geo data.
_Avoid_: Country, address, region

**Avatar**:
A creator-uploaded profile image stored as a Laravel Storage path; auto-populated from an OAuth provider on first registration only, never overwritten afterwards. Validated as jpg/png/webp up to 3 MB.
_Avoid_: Profile picture, profile photo, user image

**OAuth Account**:
A linked third-party provider identity stored on a dedicated `oauth_accounts` table, enabling OAuth login alongside Fortify password/passkey auth. GitHub is offered when credentials are configured; Google appears only when its client ID exists. One creator may link multiple providers.
_Avoid_: Social login, social account, provider account

**Creator Studio**:
The authenticated workspace where a creator shapes projects, releases, Laravel Cloud URL verification, and their public presence.
_Avoid_: Dashboard, admin area, back office

**Project**:
A creator-owned application showcase that can be published publicly only while it is discoverable: public, verified, has a published Release, and has an approved, complete Ship Story.
_Avoid_: App, listing, submission

**Launch Composer**:
A guided creation flow that creates a Project's private identity. Verification and public launch decisions happen later in Creator Studio.
_Avoid_: Form, wizard, project editor

**Launch Kit**:
An owner-only Creator Studio page that gathers one Project's shareable assets and snippets — README badge, launch card, cover plate, Ship Manifest, share text, and Share Intents.
_Avoid_: Press kit, marketing page, share hub

**Share Intent**:
An outbound, prefilled share link that hands a Project's canonical URL to an external network such as X, LinkedIn, or Reddit.
_Avoid_: Social login, OAuth link, share button

**Product Event**:
A first-party, append-only record of one observed product behavior — a submission started, a verification passed, a kit asset copied — written for roadmap evidence and never shown to users. Distinct from the community-facing activity feed.
_Avoid_: Analytics, telemetry, activity

**Release**:
A publishable record of a project being shipped, containing its release notes and publication time. A public Release can be shared as its own record within its public Project; a scheduled Release can publish its Project only while Verification is current.
_Avoid_: Update, changelog entry, deployment

**Ship Story**:
A creator-authored, structured account of a Project's purpose, audience, build choices, hardest problem, and lessons learned. It explains the work behind a Project and is distinct from a Release, which records a subsequent shipping event. A draft remains private in Creator Studio; a published Ship Story is public only while its Project is discoverable.
_Avoid_: Case study, launch post, update, release notes

**Cheer**:
An authenticated community member's single, reversible expression of appreciation for a cheerable record — currently a Project or a Comment. Stored polymorphically so the same model serves both targets.
_Avoid_: Like, upvote, appreciation

**Verification**:
A system-issued confirmation that a Project's creator-submitted Laravel Cloud URL is a reachable HTTPS `*.laravel.cloud` origin whose normalized project name matches the Project's live hostname. It is deployment evidence, not proof of Cloud account ownership, and a successful check never republishes automatically.
_Avoid_: Badge, claimed deployment, token verification

**Verification State**:
The current outcome of a Verification: verified, failed, stale, or unverified. Shipped refreshes it when a creator submits or rechecks a Laravel Cloud URL, then daily. Changing the live URL or stored Laravel Cloud URL, or a failed, stale, mismatched, or legacy-pending recheck, makes the Project private; a later successful check never republishes automatically.
_Avoid_: Badge status, deployment status

**Laravel Cloud URL**:
The HTTPS `*.laravel.cloud` origin a creator submits as verification evidence. It must resolve only to public addresses, answer the hardened reachability probe, and share a normalized project name with the live hostname. Normal verification stores this URL and does not request an API token.
_Avoid_: Cloud token, environment ID, Connected Environment URL

**Cloud Connection**:
A legacy creator-owned record of an encrypted Laravel Cloud API token, retained during cutover for read-only environment backfill. It is not the active verification path; a Creator may still have one Cloud Connection, but new verification uses a Laravel Cloud URL.
_Avoid_: Cloud account, deployment token, current verification credential

**Disconnect**:
The explicit removal of a legacy Cloud Connection. It deletes the stored credential and Connected Environments and makes every affected Project private without deleting its Project or Releases. It is a cutover remnant, not the current way to withdraw verification.
_Avoid_: Delete account, unverify

**Connected Environment**:
A legacy stored record of a Laravel Cloud environment synced through a Cloud Connection. Retained as a backfill source for URL candidates; a Project no longer verifies by selecting one, and a remaining binding without URL evidence is treated as legacy-pending and made private.
_Avoid_: Production app, Cloud project, current verification evidence

**Category**:
A single curated product classification assigned to a project for community discovery.
_Avoid_: Tag, label, type

**Collection**:
A curator-selected, ordered set of discoverable Projects presented as one editorial page at /collections/{slug}. Membership is hand-picked by the curator; a member is suppressed from public display while its Project is non-discoverable and keeps its stored order for when it returns. Distinct from a Category, which classifies a single Project, and from a Featured Project, which a Creator selects on their own profile.
_Avoid_: List, showcase, roundup, featured picks

**Tags**:
A free-form set of descriptors a creator adds to a project at creation/edit time, entered comma-separated and slug-normalized. Many per project, distinct from the single curated Category and from the curated Built With vocabulary.
_Avoid_: Keywords, topics, labels

**Technology**:
A curated stack component from a controlled vocabulary, classified into a Stack Group. Maintained by seeder, never free-form entry; version groups hold at most one selection per project.
_Avoid_: Tag, label, framework

**Stack Group**:
The classification of a Technology in the controlled vocabulary: Laravel version, PHP version, Frontend, Database, Infrastructure, or Package. Version groups are single-choice per project; the others allow multiple selections. There is deliberately no hosting group — Verification already implies Laravel Cloud for every discoverable project.
_Avoid_: Category, facet, type

**Built With**:
A creator's declared selection of Technologies on a Project. Each record carries a provenance — declared, observed, or reviewed; current records are creator-declared only — shown publicly with its provenance label, filterable on Discover, and browsable per Technology.
_Avoid_: Tech stack, skills, badge

**Pricing**:
A project's commercial model enumerated as one of Free, Freemium, Paid, Open Source, Subscription, or One-Time; default Free. Filterable on Discover.
_Avoid_: Price tier, cost, plan

**Logo**:
A project's square icon image stored as a Laravel Storage path; validated square, PNG/JPG/WebP, at least 256×256, up to 6 MB. Distinct from the wide cover image.
_Avoid_: App icon, project icon, thumbnail

**Launch Date**:
A user-selected, date-only record of when a project was launched. Distinct from the system-issued Filed serial timestamp; display and filter metadata only, no scheduling or visibility gating.
_Avoid_: Release date, shipped date, go-live

**Screenshots**:
Up to five project images, each with an optional caption and ordinal position, stored as Laravel Storage paths. Validated jpg/png/webp up to 5 MB each; the five-image limit is enforced both client- and server-side.
_Avoid_: Gallery, images, photos

**Review**:
A single rated evaluation one creator may submit per project: a required 1–5 integer rating plus an optional plain-text body. Its rating aggregates into the project's average, shown on the Show page and Discover card.
_Avoid_: Rating, testimonial, feedback entry

**Comment**:
A creator's contribution to a project's public discussion thread; supports single-level replies (reply to a top-level comment only) and is itself cheerable. Body is short plain text.
_Avoid_: Reply, message, post

**Edit Window**:
The 15-minute period after a comment is posted during which its author may edit it; afterward the body is locked but delete remains available.
_Avoid_: Grace period, edit lock

**Demo Launch**:
A clearly labeled fictional Project and Release included only in local and test environments to demonstrate Shipped before community content exists. It is never Laravel Cloud verified.
_Avoid_: Real submission, seed example
