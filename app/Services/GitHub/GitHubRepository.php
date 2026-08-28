<?php

namespace App\Services\GitHub;

/**
 * The owner/repo pair of a public GitHub repository URL. Only the first
 * two path segments are significant; deeper paths belong to the file
 * being read, not the repository identity.
 */
final class GitHubRepository
{
    private function __construct(
        public readonly string $owner,
        public readonly string $name,
    ) {}

    public static function fromUrl(?string $url): ?self
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        // git@github.com:owner/repo.git form
        if (preg_match('/^git@github\.com:([^\/\s]+)\/([^\/\s#?]+)/', trim($url), $ssh)) {
            return new self($ssh[1], self::stripGitSuffix($ssh[2]));
        }

        $host = parse_url(trim($url), PHP_URL_HOST);
        $path = parse_url(trim($url), PHP_URL_PATH);

        if (! is_string($host) || ! in_array(strtolower($host), ['github.com', 'www.github.com'], true)) {
            return null;
        }

        if (! is_string($path) || preg_match('/^\/([^\/\s]+)\/([^\/\s#?]+)/', $path, $matches) !== 1) {
            return null;
        }

        return new self($matches[1], self::stripGitSuffix($matches[2]));
    }

    public function slug(): string
    {
        return $this->owner.'/'.$this->name;
    }

    private static function stripGitSuffix(string $name): string
    {
        return preg_replace('/\.git$/', '', $name) ?? $name;
    }
}
