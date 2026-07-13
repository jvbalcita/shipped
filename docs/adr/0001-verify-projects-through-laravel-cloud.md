# Verify projects through Laravel Cloud

Shipped will issue Verification by using one creator-provided Laravel Cloud API token to inspect selected Cloud environments and compare their configured domains with a project's normalized live hostname. The hostname must exactly match the environment's primary or custom domain, with `www.`, scheme, paths, queries, and trailing slashes ignored. A creator has one Cloud Connection and each project can bind to one Connected Environment. This is more trustworthy than creator-claimed verification while keeping the MVP read-only and avoiding deployment control.

The Cloud integration may list and inspect Laravel Cloud resources only. It must not trigger deployments, restart environments, or modify Laravel Cloud settings or resources.

Verification runs when a creator binds or rechecks an environment, then runs daily. A failed or stale recheck removes the public verified marker while retaining the last successful verification timestamp for the Creator Studio.

Public visibility requires both a published Release and a current verified state. Shipped is therefore a verified Laravel Cloud registry, not a general-purpose launch directory.

Changing a Project's live URL or Connected Environment immediately makes it private. A failed or stale daily verification does the same. Shipped retains the Project and its Release history in Creator Studio, but never republishes automatically after later verification succeeds.

Cloud Connection is a dedicated one-to-one record owned by a Creator. It holds the encrypted token and connection health, while Projects only retain their selected Connected Environment evidence.

Connected Environment is a record owned by Cloud Connection, carrying Cloud application/environment identifiers, names, and normalized domains. A Project references one Connected Environment instead of duplicating those values.

Disconnect deletes the encrypted token and Connected Environment records, marks every affected Project unverified and private, and preserves the Project and Release history for later reconnection.

When a scheduled Release reaches its publication time, Shipped may publish its Project only if the Project has a current verified state. Otherwise it remains private until the Creator restores verification and explicitly republishes.

Demo Launches are available only in local and test environments. They have a distinct demo state and label, never a Laravel Cloud verification marker; production discovery accepts only verified Projects.
