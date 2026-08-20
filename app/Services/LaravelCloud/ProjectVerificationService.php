<?php

namespace App\Services\LaravelCloud;

use App\Models\Project;

final class ProjectVerificationService
{
    public const string LEGACY_MIGRATION_REASON = 'Legacy verification requires a Laravel Cloud URL recheck.';

    /**
     * Safe creator-facing copy per machine failure code. Values are stored
     * in verification_failure_reason; codes themselves are diagnostics.
     *
     * @var array<string, string>
     */
    private const array FAILURE_REASONS = [
        'dns_unavailable' => 'The Laravel Cloud URL could not be resolved. Try again shortly.',
        'non_public_address' => 'The Laravel Cloud URL does not resolve to a public address and cannot be verified.',
        'timeout' => 'The Laravel Cloud URL took too long to respond. Try again shortly.',
        'tls_error' => 'The Laravel Cloud URL could not establish a secure connection.',
        'rate_limited' => 'Laravel Cloud rate limited the verification check. Try again shortly.',
        'server_error' => 'The Laravel Cloud URL returned a server error. Try again shortly.',
        'http_rejected' => 'The Laravel Cloud URL rejected the verification request.',
        'connection_failed' => 'The Laravel Cloud URL could not be reached. Try again shortly.',
        'request_failed' => 'The Laravel Cloud URL could not be verified. Try again shortly.',
    ];

    public function __construct(private LaravelCloudUrlProbe $probe) {}

    /**
     * Verify a project through a freshly submitted Cloud URL. A reachable
     * origin never publishes the project; any non-verified outcome makes
     * it private.
     */
    public function verify(Project $project, LaravelCloudUrl $url): Project
    {
        if (! $this->matchesProjectLiveUrl($project, $url)) {
            return $this->rejectMismatchedUrl($project, $url);
        }

        return $this->applyResult($project, $this->probe->probe($url), $url);
    }

    /**
     * Recheck a project through its already-stored Cloud URL. Projects
     * without URL evidence are returned untouched.
     */
    public function refresh(Project $project): Project
    {
        $url = $project->cloudUrl();

        if ($url === null) {
            return $project;
        }

        if (! $this->matchesProjectLiveUrl($project, $url)) {
            return $this->rejectMismatchedUrl($project, $url);
        }

        return $this->applyResult($project, $this->probe->probe($url), $url);
    }

    public function invalidate(Project $project, string $reason): void
    {
        $project->forceFill([
            'is_public' => false,
            'verification_status' => 'unverified',
            'verified_at' => null,
            'verification_checked_at' => now(),
            'verification_failure_reason' => $reason,
        ])->save();
    }

    private function applyResult(Project $project, CloudUrlProbeResult $result, LaravelCloudUrl $url): Project
    {
        if ($result->isReachable()) {
            return $this->transition($project, [
                'laravel_cloud_url' => $url->url(),
                'verification_method' => 'cloud_url',
                'verification_status' => 'verified',
                'verified_at' => now(),
                'verification_checked_at' => now(),
                'verification_failure_reason' => null,
                // Verification never publishes; is_public is left untouched.
            ]);
        }

        return $this->transition($project, [
            'laravel_cloud_url' => $url->url(),
            'verification_method' => 'cloud_url',
            'verification_status' => $result->outcome === CloudUrlProbeOutcome::DefinitiveFailure
                ? 'failed'
                : 'stale',
            'verification_checked_at' => now(),
            // verified_at survives as the last successful verification time.
            'verification_failure_reason' => self::FAILURE_REASONS[$result->failureCode ?? '']
                ?? self::FAILURE_REASONS['request_failed'],
            'is_public' => false,
        ]);
    }

    private function matchesProjectLiveUrl(Project $project, LaravelCloudUrl $url): bool
    {
        $projectHost = $project->live_url === null
            ? null
            : HostnameNormalizer::normalize($project->live_url);

        return $projectHost !== null
            && $projectHost === HostnameNormalizer::normalize($url->host());
    }

    private function rejectMismatchedUrl(Project $project, LaravelCloudUrl $url): Project
    {
        return $this->transition($project, [
            'laravel_cloud_url' => $url->url(),
            'verification_method' => 'cloud_url',
            'verification_status' => 'failed',
            'verification_checked_at' => now(),
            'verification_failure_reason' => 'The Laravel Cloud URL does not match the project live URL.',
            'is_public' => false,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function transition(Project $project, array $attributes): Project
    {
        $project->forceFill($attributes)->save();

        return $project;
    }
}
