<?php

namespace App\Services\LaravelCloud;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Probes one Laravel Cloud URL for reachability without following
 * redirects to another origin. Same-origin redirects are accepted as
 * reachable evidence without being followed. Every resolved address is
 * checked against the public-address allowlist before the request is sent,
 * the body is streamed and discarded under a byte ceiling, and no
 * credentials or cookies are attached. The probe never retries on its own:
 * temporal retries belong to the scheduler.
 */
class LaravelCloudUrlProbe
{
    /** Responses larger than this are aborted once the ceiling is reached. */
    private const int MAX_RESPONSE_BYTES = 64 * 1024;

    private const string USER_AGENT = 'Shipped-Cloud-Verifier/1.0';

    /**
     * Ranges PHP's FILTER_FLAG_NO_*_RANGE flags still allow but that are
     * documentation blocks or non-unicast and therefore never valid
     * destinations for a Laravel Cloud environment.
     */
    private const array NON_PUBLIC_OR_DOCUMENTATION_RANGES = [
        '0.0.0.0/8',
        '100.64.0.0/10',
        '192.0.0.0/24',
        '192.0.2.0/24',
        '198.18.0.0/15',
        '198.51.100.0/24',
        '203.0.113.0/24',
        '224.0.0.0/4',
        '240.0.0.0/4',
        '100::/64',
        '2001:2::/48',
        '2001:10::/28',
        '2001:db8::/32',
        '3fff::/20',
        'ff00::/8',
    ];

    public function __construct(private CloudHostResolver $resolver) {}

    public function probe(LaravelCloudUrl $url): CloudUrlProbeResult
    {
        $startedAt = hrtime(true);

        $addresses = $this->resolver->addresses($url->host());

        if ($addresses === []) {
            return $this->result(CloudUrlProbeOutcome::RetryableFailure, null, 'dns_unavailable', $startedAt);
        }

        foreach ($addresses as $address) {
            if (! self::isPublicAddress($address)) {
                return $this->result(CloudUrlProbeOutcome::DefinitiveFailure, null, 'non_public_address', $startedAt);
            }
        }

        try {
            $response = $this->client()->head($url->url());
            $status = $response->status();

            if ($this->isSameOriginRedirect($response, $url)) {
                return $this->result(CloudUrlProbeOutcome::Reachable, $status, null, $startedAt);
            }

            if ($status === 405 || $status === 501) {
                $response = $this->client(stream: true)->get($url->url());
                $this->discardBody($response);
                $status = $response->status();

                if ($this->isSameOriginRedirect($response, $url)) {
                    return $this->result(CloudUrlProbeOutcome::Reachable, $status, null, $startedAt);
                }
            }
        } catch (ConnectionException $exception) {
            return $this->result(
                CloudUrlProbeOutcome::RetryableFailure,
                null,
                $this->connectionFailureCode($exception),
                $startedAt,
            );
        } catch (Throwable) {
            return $this->result(CloudUrlProbeOutcome::RetryableFailure, null, 'request_failed', $startedAt);
        }

        return match (true) {
            $status >= 200 && $status < 300 => $this->result(CloudUrlProbeOutcome::Reachable, $status, null, $startedAt),
            $status === 408, $status === 425 => $this->result(CloudUrlProbeOutcome::RetryableFailure, $status, 'timeout', $startedAt),
            $status === 429 => $this->result(CloudUrlProbeOutcome::RetryableFailure, $status, 'rate_limited', $startedAt),
            $status >= 500 => $this->result(CloudUrlProbeOutcome::RetryableFailure, $status, 'server_error', $startedAt),
            default => $this->result(CloudUrlProbeOutcome::DefinitiveFailure, $status, 'http_rejected', $startedAt),
        };
    }

    private function isSameOriginRedirect(Response $response, LaravelCloudUrl $url): bool
    {
        if ($response->status() < 300 || $response->status() >= 400) {
            return false;
        }

        $location = $response->header('Location');

        if ($location === '') {
            return false;
        }

        if (str_starts_with($location, '/') && ! str_starts_with($location, '//')) {
            return true;
        }

        $parts = parse_url($location);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        if (
            strcasecmp((string) $parts['scheme'], 'https') !== 0
            || isset($parts['user'], $parts['pass'], $parts['port'])
        ) {
            return false;
        }

        return mb_strtolower(rtrim((string) $parts['host'], '.')) === $url->host();
    }

    private function client(bool $stream = false): PendingRequest
    {
        $request = Http::withUserAgent(self::USER_AGENT)
            ->connectTimeout(3)
            ->timeout(8)
            ->withoutRedirecting();

        return $stream ? $request->withOptions(['stream' => true]) : $request;
    }

    /**
     * Drain the streamed body up to the byte ceiling so a hostile or
     * oversized response cannot pin memory; the payload is never parsed.
     */
    private function discardBody(Response $response): void
    {
        $stream = $response->toPsrResponse()->getBody();
        $remaining = self::MAX_RESPONSE_BYTES;

        try {
            while (! $stream->eof() && $remaining > 0) {
                $remaining -= strlen($stream->read(min(8192, $remaining)));
            }
        } finally {
            $stream->close();
        }
    }

    private function connectionFailureCode(ConnectionException $exception): string
    {
        $message = mb_strtolower($exception->getMessage());

        return match (true) {
            str_contains($message, 'timed out') => 'timeout',
            str_contains($message, 'could not resolve') => 'dns_unavailable',
            str_contains($message, 'ssl'), str_contains($message, 'tls'), str_contains($message, 'certificate') => 'tls_error',
            default => 'connection_failed',
        };
    }

    private static function isPublicAddress(string $address): bool
    {
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        $packed = @inet_pton($address);

        if ($packed === false) {
            return false;
        }

        $mappedPrefix = @inet_pton('::ffff:0:0');

        if ($mappedPrefix !== false && substr($packed, 0, 12) === substr($mappedPrefix, 0, 12)) {
            return false;
        }

        foreach (self::NON_PUBLIC_OR_DOCUMENTATION_RANGES as $cidr) {
            if (self::withinRange($packed, $cidr)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  string  $packed  Binary-packed address from inet_pton().
     */
    private static function withinRange(string $packed, string $cidr): bool
    {
        [$network, $prefix] = explode('/', $cidr);
        $networkPacked = @inet_pton($network);

        if ($networkPacked === false || strlen($networkPacked) !== strlen($packed)) {
            return false;
        }

        $prefix = (int) $prefix;
        $maskBytes = intdiv($prefix, 8);
        $maskBits = $prefix % 8;

        if ($maskBytes > 0 && substr($packed, 0, $maskBytes) !== substr($networkPacked, 0, $maskBytes)) {
            return false;
        }

        if ($maskBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $maskBits)) & 0xFF;

        return ((ord($packed[$maskBytes]) ^ ord($networkPacked[$maskBytes])) & $mask) === 0;
    }

    private function result(CloudUrlProbeOutcome $outcome, ?int $httpStatus, ?string $failureCode, int|float $startedAt): CloudUrlProbeResult
    {
        return new CloudUrlProbeResult(
            outcome: $outcome,
            httpStatus: $httpStatus,
            failureCode: $failureCode,
            durationMs: (int) ((hrtime(true) - $startedAt) / 1e6),
        );
    }
}
