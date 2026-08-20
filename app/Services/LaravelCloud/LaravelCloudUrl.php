<?php

namespace App\Services\LaravelCloud;

use InvalidArgumentException;

/**
 * A canonical Laravel Cloud environment origin of the exact form
 * `https://<single-label>.laravel.cloud`. Everything else — other schemes
 * or hosts, credentials, ports, non-root paths, queries, fragments, and
 * non-ASCII or malformed input — is rejected before it reaches the probe.
 */
final readonly class LaravelCloudUrl
{
    private const string HOST_PATTERN = '/^(?!-)[a-z0-9-]{1,63}(?<!-)\.laravel\.cloud$/';

    public const int MAX_INPUT_LENGTH = 255;

    private function __construct(public string $host) {}

    /**
     * @throws InvalidArgumentException when the value is not an allowed Cloud origin.
     */
    public static function from(string $value): self
    {
        return self::tryFrom($value)
            ?? throw new InvalidArgumentException('The value must be an HTTPS `*.laravel.cloud` URL.');
    }

    public static function tryFrom(string $value): ?self
    {
        if ($value === '' || strlen($value) > self::MAX_INPUT_LENGTH) {
            return null;
        }

        if (preg_match('/[\s\x00-\x1f\x7f]/', $value) === 1) {
            return null;
        }

        $parts = parse_url($value);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        foreach (['user', 'pass', 'port', 'query', 'fragment'] as $component) {
            if (isset($parts[$component])) {
                return null;
            }
        }

        if (strcasecmp($parts['scheme'], 'https') !== 0 || ($parts['path'] ?? '/') !== '/') {
            return null;
        }

        $host = strtolower($parts['host']);

        if (str_ends_with($host, '.')) {
            $host = substr($host, 0, -1);
        }

        if (preg_match(self::HOST_PATTERN, $host) !== 1) {
            return null;
        }

        return new self($host);
    }

    public function url(): string
    {
        return 'https://'.$this->host;
    }

    public function host(): string
    {
        return $this->host;
    }
}
