# Verify projects through Laravel Cloud URL evidence

Shipped verifies a project through a creator-submitted Laravel Cloud environment URL. The submitted URL must be an HTTPS `*.laravel.cloud` origin, must resolve only to public addresses, must answer the hardened reachability probe without a redirect, and must have the same normalized hostname as the project's live URL. Normalization lowercases the hostname, removes one leading `www.`, removes a trailing dot, and ignores scheme, port, path, query, and fragment.

This direct URL evidence is the active verification contract because it avoids requesting a creator API token during normal project verification. A reachable Cloud origin that belongs to another project is not evidence for the current project. Verification rejects the mismatch before making the outbound probe.

The Cloud probe is read-only and must not trigger deployments, restart environments, modify Laravel Cloud settings, follow redirects, attach credentials/cookies, contact private or special-use address space, or buffer an unbounded fallback response. A `405` or `501` HEAD response may use a streamed GET fallback, but the body is drained only to the configured ceiling and then closed.

Verification runs when a creator submits or rechecks a URL, then runs daily. A failed, stale, mismatched, or legacy-pending recheck makes the project private and stores the last check reason. A successful recheck never republishes automatically; the creator must explicitly publish again.

Public visibility requires both a published Release and a current verified state. Shipped is therefore a verified Laravel Cloud registry, not a general-purpose launch directory.

The synchronous verification route is rate-limited per authenticated creator/project because DNS resolution and outbound HTTP probing are expensive. Rate limiting occurs before the probe is invoked.

## Legacy rollout

Existing `cloud_connections` and `connected_environments` records are retained during the migration from token-backed evidence. Run the backfill in this order after the additive schema migration:

```bash
php artisan shipped:backfill-cloud-verification-urls --dry-run
php artisan shipped:backfill-cloud-verification-urls --apply --verify
php artisan shipped:refresh-cloud-verifications
```

`--apply` writes only an unverified, private URL candidate. Only `--verify` can restore verified state after the candidate passes the exact live-host comparison and reachability probe. Zero or multiple Cloud hostname candidates require manual creator input; applying the command withdraws their old public verification. The daily recheck also scans legacy-connected projects and withdraws any project that still lacks URL evidence, so a missed backfill cannot leave stale public verification untouched.

Do not delete the legacy connection/environment records until the backfill report has been reviewed and all remaining projects have either been migrated or intentionally remediated. If the migration has already been recorded as applied in production, repair an actual partial schema with a new forward migration rather than editing the deployed migration.

## State and publication rules

Changing a project's live URL or stored Cloud URL immediately makes it private and requires verification again. A failed or stale daily verification does the same. Shipped retains the project and Release history in Creator Studio, but never republishes automatically after later verification succeeds.

When a scheduled Release reaches its publication time, Shipped may publish its project only if the project remains verified. Otherwise the Release remains available in Creator Studio and the project stays private until the creator restores verification and explicitly republishes.

Demo Launches are available only in local and test environments. They have a distinct demo state and label, never a Laravel Cloud verification marker; production discovery accepts only verified projects.
