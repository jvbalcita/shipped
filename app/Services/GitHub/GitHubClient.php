<?php

namespace App\Services\GitHub;

use App\Services\GitHub\Exceptions\GitHubApiUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class GitHubClient
{
    /**
     * The caller's public repositories they own, collaborate on, or
     * belong to through an organization — the pickable set for a
     * launch's source URL. Capped at the first page (100 entries,
     * most recently pushed first); anything older falls back to
     * pasting the URL.
     *
     * @return array<int, array{name: string, url: string}>
     */
    public function listRepositories(string $token): array
    {
        $response = $this->request(
            $token,
            '/user/repos?visibility=public&affiliation=owner,collaborator,organization_member&sort=pushed&direction=desc&per_page=100',
        );

        $repositories = [];
        foreach ($response->json() ?? [] as $repository) {
            if (! is_array($repository)) {
                continue;
            }

            $name = (string) ($repository['full_name'] ?? '');
            $url = (string) ($repository['html_url'] ?? '');

            if ($name !== '' && $url !== '') {
                $repositories[] = ['name' => $name, 'url' => $url];
            }
        }

        return $repositories;
    }

    private function request(string $token, string $url): Response
    {
        try {
            $response = $this->client($token)->get($url);
        } catch (ConnectionException) {
            throw new GitHubApiUnavailable;
        }

        if ($response->status() === 401 || $response->status() === 403 || $response->status() === 429 || $response->serverError()) {
            throw new GitHubApiUnavailable;
        }

        return $response->throw();
    }

    private function client(string $token): PendingRequest
    {
        return Http::baseUrl('https://api.github.com')
            ->acceptJson()
            ->withToken($token)
            ->connectTimeout(3)
            ->timeout(5);
    }
}
