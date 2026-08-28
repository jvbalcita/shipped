<?php

namespace App\Services\GitHub;

use App\Services\GitHub\Exceptions\GitHubApiUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Reads files from a public repository's default branch through the
 * GitHub contents API. Read access is anonymous by design — the project
 * advertises a public repository URL — with an optional app-level token
 * for rate-limit headroom. Creator OAuth tokens are never used: they can
 * be stale, and the evidence must not depend on a creator's session.
 */
final class RepoFileFetcher
{
    /**
     * The decoded file contents, or null when the file does not exist.
     *
     * @throws GitHubApiUnavailable When GitHub cannot be reached or rate-limits the request.
     */
    public function fetch(GitHubRepository $repository, string $path): ?string
    {
        $client = Http::baseUrl('https://api.github.com')
            ->accept('application/vnd.github.raw+json')
            ->connectTimeout(3)
            ->timeout(8);

        $token = (string) config('services.github.app_token');

        if ($token !== '') {
            $client = $client->withToken($token);
        }

        try {
            $response = $client->get("/repos/{$repository->slug()}/contents/{$path}");
        } catch (ConnectionException) {
            throw new GitHubApiUnavailable;
        }

        if ($response->status() === 404) {
            return null;
        }

        if ($response->status() === 403 || $response->status() === 429 || $response->serverError()) {
            throw new GitHubApiUnavailable;
        }

        return $response->throw()->body();
    }
}
