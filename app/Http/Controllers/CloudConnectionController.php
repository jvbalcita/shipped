<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCloudConnectionRequest;
use App\Models\CloudConnection;
use App\Models\Project;
use App\Services\LaravelCloud\CloudEnvironmentData;
use App\Services\LaravelCloud\Exceptions\CloudApiUnavailable;
use App\Services\LaravelCloud\Exceptions\InvalidCloudToken;
use App\Services\LaravelCloud\LaravelCloudClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CloudConnectionController extends Controller
{
    public function store(StoreCloudConnectionRequest $request, LaravelCloudClient $client): RedirectResponse
    {
        $token = $request->validated('api_token');

        try {
            $environments = $this->environmentsFor($client, $token);
        } catch (InvalidCloudToken) {
            throw ValidationException::withMessages([
                'api_token' => 'The Laravel Cloud token is invalid.',
            ]);
        } catch (CloudApiUnavailable) {
            throw ValidationException::withMessages([
                'cloud_connection' => 'Laravel Cloud is temporarily unavailable. Try again shortly.',
            ]);
        }

        DB::transaction(function () use ($request, $token, $environments): void {
            $connection = CloudConnection::query()->updateOrCreate(
                ['user_id' => $request->user()->id],
                [
                    'api_token' => $token,
                    'status' => 'connected',
                    'last_validated_at' => now(),
                    'last_error' => null,
                ],
            );

            $environmentIds = array_map(
                fn (CloudEnvironmentData $environment) => $environment->environmentId,
                $environments,
            );
            $staleEnvironments = $connection->connectedEnvironments()
                ->when(
                    $environmentIds !== [],
                    fn ($query) => $query->whereNotIn('environment_id', $environmentIds),
                )
                ->get();
            $staleEnvironmentIds = $staleEnvironments->modelKeys();

            if ($staleEnvironmentIds !== []) {
                Project::query()
                    ->whereIn('connected_environment_id', $staleEnvironmentIds)
                    ->update([
                        'is_public' => false,
                        'verification_status' => 'unverified',
                        'verified_at' => null,
                        'verification_checked_at' => now(),
                        'verification_failure_reason' => 'Laravel Cloud environment is no longer available.',
                    ]);

                $connection->connectedEnvironments()->whereKey($staleEnvironmentIds)->delete();
            }

            foreach ($environments as $environment) {
                $connection->connectedEnvironments()->updateOrCreate(
                    ['environment_id' => $environment->environmentId],
                    [
                        'application_id' => $environment->applicationId,
                        'application_name' => $environment->applicationName,
                        'environment_name' => $environment->environmentName,
                        'domains' => $environment->domains,
                        'synced_at' => now(),
                    ],
                );
            }
        });

        return to_route('dashboard');
    }

    public function destroy(): RedirectResponse
    {
        $connection = request()->user()->cloudConnection;

        if ($connection === null) {
            return to_route('dashboard');
        }

        DB::transaction(function () use ($connection): void {
            Project::query()
                ->whereHas('connectedEnvironment', fn ($query) => $query->whereBelongsTo($connection))
                ->update([
                    'is_public' => false,
                    'verification_status' => 'unverified',
                    'verified_at' => null,
                    'verification_checked_at' => now(),
                    'verification_failure_reason' => 'Laravel Cloud connection removed.',
                ]);

            $connection->delete();
        });

        return to_route('dashboard');
    }

    /**
     * @return array<int, CloudEnvironmentData>
     */
    private function environmentsFor(LaravelCloudClient $client, string $token): array
    {
        $environments = [];

        foreach ($client->listApplications($token) as $application) {
            foreach ($client->listEnvironments($token, $application['id']) as $environment) {
                $environments[] = new CloudEnvironmentData(
                    applicationId: $environment->applicationId,
                    applicationName: $application['name'],
                    environmentId: $environment->environmentId,
                    environmentName: $environment->environmentName,
                    domains: $environment->domains,
                );
            }
        }

        return $environments;
    }
}
