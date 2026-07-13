<?php

namespace App\Services\LaravelCloud;

final class HostnameNormalizer
{
    public static function normalize(string $urlOrHost): ?string
    {
        $urlOrHost = trim($urlOrHost);

        if ($urlOrHost === '') {
            return null;
        }

        $parsedUrl = parse_url(str_contains($urlOrHost, '://') || str_starts_with($urlOrHost, '//')
            ? $urlOrHost
            : "//{$urlOrHost}");
        $host = $parsedUrl['host'] ?? null;

        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = mb_strtolower($host);

        if (str_ends_with($host, '.')) {
            $host = substr($host, 0, -1);
        }

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        if ($host === '' || ! self::isValidHost($host)) {
            return null;
        }

        return $host;
    }

    /**
     * @param  array<int, string>  $domains
     */
    public static function matches(string $projectUrl, array $domains): bool
    {
        $projectHost = self::normalize($projectUrl);

        if ($projectHost === null) {
            return false;
        }

        foreach ($domains as $domain) {
            if ($projectHost === self::normalize($domain)) {
                return true;
            }
        }

        return false;
    }

    private static function isValidHost(string $host): bool
    {
        return filter_var($host, FILTER_VALIDATE_IP) !== false
            || filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }
}
