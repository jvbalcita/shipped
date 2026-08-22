<?php

namespace App\Services\LaravelCloud;

use App\Models\Project;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

final class ProjectVerificationService
{
    public const string LEGACY_MIGRATION_REASON = 'Legacy verification requires a Laravel Cloud URL recheck.';

    public const string ORIGIN_ALREADY_USED = 'This Laravel Cloud URL is already used by another listing.';

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
        if (! $this->matchesProjectName($project, $url)) {
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

        if (! $this->matchesProjectName($project, $url)) {
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

    /**
     * Match the normalized project name in the live hostname against the
     * Laravel Cloud environment slug without using a Cloud API token.
     *
     * A custom domain may use `artisanbizops.com` while Cloud adds a
     * deployment suffix such as `artisan-bizops-x1233.laravel.cloud`.
     */
    private function matchesProjectName(Project $project, LaravelCloudUrl $url): bool
    {
        $projectName = $project->live_url === null
            ? null
            : $this->normalizedProjectName($project->live_url);
        $cloudHost = HostnameNormalizer::normalize($url->host());
        $cloudName = $cloudHost === null ? null : $this->normalizedProjectName($cloudHost);

        if ($projectName === null || $cloudName === null) {
            return false;
        }

        if ($cloudName === $projectName) {
            return true;
        }

        if (! str_starts_with($cloudName, $projectName)) {
            return false;
        }

        $cloudLabel = explode('.', $cloudHost, 2)[0];
        $normalizedCharacters = 0;

        foreach (str_split($cloudLabel) as $index => $character) {
            if ($character === '-') {
                continue;
            }

            $normalizedCharacters++;

            if ($normalizedCharacters === strlen($projectName)) {
                return ($cloudLabel[$index + 1] ?? null) === '-';
            }
        }

        return false;
    }

    private function normalizedProjectName(string $urlOrHost): ?string
    {
        $host = HostnameNormalizer::normalize($urlOrHost);

        if ($host === null) {
            return null;
        }

        $label = explode('.', $host, 2)[0];
        $name = preg_replace('/[^a-z0-9]/', '', mb_strtolower($label));

        return is_string($name) && $name !== '' ? $name : null;
    }

    private function rejectMismatchedUrl(Project $project, LaravelCloudUrl $url): Project
    {
        return $this->transition($project, [
            'laravel_cloud_url' => $url->url(),
            'verification_method' => 'cloud_url',
            'verification_status' => 'failed',
            'verification_checked_at' => now(),
            'verification_failure_reason' => 'The Laravel Cloud URL name does not match the project live URL name.',
            'is_public' => false,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function transition(Project $project, array $attributes): Project
    {
        try {
            $project->forceFill($attributes)->save();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'laravel_cloud_url' => self::ORIGIN_ALREADY_USED,
            ]);
        } catch (QueryException $e) {
            if (
                $e->getCode() !== '23505'
                && ! str_contains((string) $e->getMessage(), '23505')
                && ! str_contains((string) $e->getMessage(), 'UNIQUE constraint failed')
            ) {
                throw $e;
            }

            throw ValidationException::withMessages([
                'laravel_cloud_url' => self::ORIGIN_ALREADY_USED,
            ]);
        }

        return $project;
    }
}
