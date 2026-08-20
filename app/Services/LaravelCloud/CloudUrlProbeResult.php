<?php

namespace App\Services\LaravelCloud;

/**
 * The immutable outcome of one Laravel Cloud URL probe. `failureCode` is a
 * stable machine code (dns_unavailable, non_public_address, timeout,
 * tls_error, rate_limited, server_error, http_rejected, …) that callers map
 * to user-facing copy; `httpStatus` and `durationMs` are diagnostics only.
 */
final readonly class CloudUrlProbeResult
{
    public function __construct(
        public CloudUrlProbeOutcome $outcome,
        public ?int $httpStatus,
        public ?string $failureCode,
        public int $durationMs,
    ) {}

    public function isReachable(): bool
    {
        return $this->outcome === CloudUrlProbeOutcome::Reachable;
    }
}
