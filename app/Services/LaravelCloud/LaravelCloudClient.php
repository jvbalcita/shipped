<?php

namespace App\Services\LaravelCloud;

use App\Services\LaravelCloud\Exceptions\CloudApiUnavailable;
use App\Services\LaravelCloud\Exceptions\InvalidCloudToken;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

final class LaravelCloudClient
{
    /**
     * @return array<int, array{id: string, name: string}>
     */
    public function listApplications(string $token): array
    {
        $applications = [];

        foreach ($this->paginatedData($token, '/applications') as $application) {
            $applications[] = [
                'id' => (string) Arr::get($application, 'id'),
                'name' => (string) Arr::get($application, 'attributes.name'),
            ];
        }

        return $applications;
    }

    /**
     * @return array<int, CloudEnvironmentData>
     */
    public function listEnvironments(string $token, string $applicationId): array
    {
        $environments = [];

        foreach ($this->paginatedData(
            $token,
            "/applications/{$applicationId}/environments?include=primaryDomain",
        ) as $environment) {
            $environmentId = (string) Arr::get($environment, 'id');
            $detail = $this->request($token, "/environments/{$environmentId}?include=primaryDomain,application");
            $detailPayload = $detail->json();

            $environments[] = $this->environmentData(
                $applicationId,
                $environment,
                is_array($detailPayload) ? $detailPayload : [],
            );
        }

        return $environments;
    }

    public function getEnvironment(
        string $token,
        string $applicationId,
        string $environmentId,
        ?string $applicationName = null,
    ): CloudEnvironmentData {
        $response = $this->request($token, "/environments/{$environmentId}?include=primaryDomain,application");
        $payload = $response->json();
        $customDomains = $this->domainNames($this->paginatedData($token, "/environments/{$environmentId}/domains"));

        return $this->environmentData(
            $applicationId,
            ['id' => $environmentId],
            is_array($payload) ? $payload : [],
            $customDomains,
            $applicationName,
        );
    }

    private function request(string $token, string $url): Response
    {
        try {
            $response = $this->client($token)->get($url);
        } catch (ConnectionException) {
            throw new CloudApiUnavailable;
        }

        if ($response->status() === 401 || $response->status() === 403) {
            throw new InvalidCloudToken;
        }

        if ($response->status() === 429 || $response->serverError()) {
            throw new CloudApiUnavailable;
        }

        return $response->throw();
    }

    private function client(string $token): PendingRequest
    {
        return Http::baseUrl('https://cloud.laravel.com/api')
            ->acceptJson()
            ->withToken($token)
            ->connectTimeout(3)
            ->timeout(5);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function paginatedData(string $token, string $url): array
    {
        $data = [];

        do {
            $response = $this->request($token, $url);
            array_push($data, ...$this->data($response));
            $url = $this->nextUrl($response);
        } while ($url !== null);

        return $data;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function data(Response $response): array
    {
        $data = $response->json('data', []);

        if (! is_array($data)) {
            return [];
        }

        return array_values(array_filter($data, 'is_array'));
    }

    private function nextUrl(Response $response): ?string
    {
        $next = $response->json('links.next');

        if (is_array($next)) {
            $next = $next['href'] ?? null;
        }

        if (! is_string($next) || $next === '') {
            return null;
        }

        if (str_starts_with($next, 'https://cloud.laravel.com/api/')) {
            return $next;
        }

        if (str_starts_with($next, '/applications/') || str_starts_with($next, '/applications') || str_starts_with($next, '/environments/')) {
            return $next;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $environment
     * @param  array<string, mixed>  $detail
     * @param  array<int, string>  $customDomains
     */
    private function environmentData(
        string $applicationId,
        array $environment,
        array $detail,
        array $customDomains = [],
        ?string $applicationName = null,
    ): CloudEnvironmentData {
        $resource = Arr::get($detail, 'data', $environment);
        $primaryDomainId = Arr::get($resource, 'relationships.primaryDomain.data.id');
        $applicationResourceId = Arr::get($resource, 'relationships.application.data.id');
        $primaryDomain = null;
        $application = null;
        $includedResources = Arr::get($detail, 'included', []);

        foreach (is_array($includedResources) ? $includedResources : [] as $included) {
            if (! is_array($included)) {
                continue;
            }

            if (Arr::get($included, 'id') === $primaryDomainId) {
                $primaryDomain = $included;
            }

            if (Arr::get($included, 'id') === $applicationResourceId) {
                $application = $included;
            }
        }
        $domains = array_values(array_unique(array_filter([
            Arr::get($primaryDomain, 'attributes.name'),
            Arr::get($resource, 'attributes.vanity_domain'),
            ...$customDomains,
        ], fn (mixed $domain): bool => is_string($domain) && $domain !== '')));

        return new CloudEnvironmentData(
            applicationId: $applicationId,
            applicationName: (string) (Arr::get($application, 'attributes.name') ?: Arr::get($resource, 'attributes.application_name') ?: $applicationName ?: $applicationId),
            environmentId: (string) Arr::get($resource, 'id', Arr::get($environment, 'id')),
            environmentName: (string) Arr::get($resource, 'attributes.name'),
            domains: $domains,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $domains
     * @return array<int, string>
     */
    private function domainNames(array $domains): array
    {
        return array_values(array_filter(array_map(
            fn (array $domain): mixed => Arr::get($domain, 'attributes.name') ?? Arr::get($domain, 'attributes.domain'),
            $domains,
        ), 'is_string'));
    }
}
