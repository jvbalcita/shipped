# Shipped

Shipped is a community showcase for projects launched on Laravel Cloud. It gives creators a public place to present the applications they have shipped and the releases that evolve them.

## Language

**Creator**:
The individual user, identified by an immutable public handle, who owns a project and presents it to the community.
_Avoid_: Owner, account

**Creator Studio**:
The authenticated workspace where a creator shapes projects, releases, Cloud Connections, and their public presence.
_Avoid_: Dashboard, admin area, back office

**Project**:
A creator-owned application showcase that can be published publicly only after it has both a published Release and a current Verification.
_Avoid_: App, listing, submission

**Launch Composer**:
A guided creation flow that creates a Project's private identity. Cloud Connection, Verification, and public launch decisions happen later in Creator Studio.
_Avoid_: Form, wizard, project editor

**Release**:
A publishable record of a project being shipped, containing its release notes and publication time. A public Release can be shared as its own record within its public Project; a scheduled Release can publish its Project only while Verification is current.
_Avoid_: Update, changelog entry, deployment

**Cheer**:
An authenticated community member's single, reversible expression of appreciation for a project.
_Avoid_: Like, upvote, appreciation

**Verification**:
A system-issued confirmation that a Project's normalized live hostname matches the selected Connected Environment's primary or custom domain.
_Avoid_: Badge, claimed deployment

**Verification State**:
The current outcome of a Verification: verified, failed, stale, or unverified. Shipped refreshes it immediately when requested and daily afterward; a non-verified state makes the Project private.
_Avoid_: Badge status, deployment status

**Cloud Connection**:
A creator-controlled, encrypted Laravel Cloud API credential used by Shipped to inspect applications and environments for verification. It is validated before storage, never shown again, and is a dedicated record owned by one Creator; a Creator has one Cloud Connection.
_Avoid_: Cloud account, deployment token

**Disconnect**:
The Creator's explicit removal of a Cloud Connection. It removes its credential and Connected Environments, and makes every affected Project private without deleting its Project or Releases.
_Avoid_: Delete account, unverify

**Connected Environment**:
The stored record of a Laravel Cloud environment available through a Creator's Cloud Connection. A Project selects at most one Connected Environment as its deployment evidence.
_Avoid_: Production app, Cloud project

**Category**:
A curated product classification assigned to a project for community discovery.
_Avoid_: Tag, label, type

**Demo Launch**:
A clearly labeled fictional Project and Release included only in local and test environments to demonstrate Shipped before community content exists. It is never Laravel Cloud verified.
_Avoid_: Real submission, seed example
