# Shipped

Shipped is a community showcase for projects launched on Laravel Cloud. It gives creators a public place to present the applications they have shipped and the releases that evolve them.

## Language

**Creator**:
The individual user, identified by a chosen public username, who owns a project and presents it to the community.
_Avoid_: Owner, account

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
A creator-owned application showcase that can be published publicly only after it has both a published Release and a current Verification.
_Avoid_: App, listing, submission

**Launch Composer**:
A guided creation flow that creates a Project's private identity. Verification and public launch decisions happen later in Creator Studio.
_Avoid_: Form, wizard, project editor

**Release**:
A publishable record of a project being shipped, containing its release notes and publication time. A public Release can be shared as its own record within its public Project; a scheduled Release can publish its Project only while Verification is current.
_Avoid_: Update, changelog entry, deployment

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

**Tags**:
A free-form set of descriptors a creator adds to a project at creation/edit time, entered comma-separated and slug-normalized. Many per project, distinct from the single curated Category.
_Avoid_: Keywords, topics, labels

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
