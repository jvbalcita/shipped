<?php

namespace App\Services\LaravelCloud;

use App\Models\CloudConnection;
use App\Models\ConnectedEnvironment;
use App\Models\Project;
use App\Services\LaravelCloud\Exceptions\CloudApiUnavailable;
use App\Services\LaravelCloud\Exceptions\InvalidCloudToken;

final class ProjectVerificationService
{
    public function __construct(private LaravelCloudClient $client) {}

    public function verify(Project $project, ConnectedEnvironment $environment): Project
    {
        try {
            $cloudEnvironment = $this->refreshEnvironment($environment);
        } catch (InvalidCloudToken) {
            return $this->transition($project, [
                'connected_environment_id' => $environment->id,
                'is_public' => false,
                'verification_status' => 'failed',
                'verification_checked_at' => now(),
                'verification_failure_reason' => 'Laravel Cloud credentials are invalid. Reconnect Cloud and verify again.',
            ]);
        } catch (CloudApiUnavailable) {
            return $this->transition($project, [
                'connected_environment_id' => $environment->id,
                'is_public' => false,
                'verification_status' => 'stale',
                'verification_checked_at' => now(),
                'verification_failure_reason' => 'Laravel Cloud could not be reached.',
            ]);
        }

        return $this->verifyAgainstEnvironment($project, $environment, $cloudEnvironment);
    }

    public function refresh(CloudConnection $connection): void
    {
        $environments = $connection->connectedEnvironments()->get();

        try {
            foreach ($environments as $environment) {
                $environment->setRelation('cloudConnection', $connection);
                $cloudEnvironment = $this->refreshEnvironment($environment);

                Project::query()
                    ->where('connected_environment_id', $environment->id)
                    ->where('is_demo', false)
                    ->each(fn (Project $project) => $this->verifyAgainstEnvironment($project, $environment, $cloudEnvironment));
            }

            $connection->forceFill([
                'last_validated_at' => now(),
                'last_error' => null,
            ])->save();
        } catch (InvalidCloudToken) {
            $this->markConnectionInvalid($connection, $environments->modelKeys());
        } catch (CloudApiUnavailable) {
            $this->markConnectionStale($connection, $environments->modelKeys());
        }
    }

    private function verifyAgainstEnvironment(Project $project, ConnectedEnvironment $environment, CloudEnvironmentData $cloudEnvironment): Project
    {
        if (! HostnameNormalizer::matches((string) $project->live_url, $cloudEnvironment->domains)) {
            return $this->transition($project, [
                'connected_environment_id' => $environment->id,
                'is_public' => false,
                'verification_status' => 'failed',
                'verification_checked_at' => now(),
                'verification_failure_reason' => 'The live URL does not match the selected Laravel Cloud environment.',
            ]);
        }

        return $this->transition($project, [
            'connected_environment_id' => $environment->id,
            'verification_status' => 'verified',
            'verified_at' => now(),
            'verification_checked_at' => now(),
            'verification_failure_reason' => null,
        ]);
    }

    private function refreshEnvironment(ConnectedEnvironment $environment): CloudEnvironmentData
    {
        $connection = $environment->cloudConnection;
        $cloudEnvironment = $this->client->getEnvironment(
            $connection->api_token,
            $environment->application_id,
            $environment->environment_id,
            $environment->application_name,
        );

        $environment->update([
            'application_name' => $cloudEnvironment->applicationName,
            'environment_name' => $cloudEnvironment->environmentName,
            'domains' => $cloudEnvironment->domains,
            'synced_at' => now(),
        ]);

        return $cloudEnvironment;
    }

    /**
     * @param  array<int, int>  $environmentIds
     */
    private function markConnectionInvalid(CloudConnection $connection, array $environmentIds): void
    {
        $connection->forceFill([
            'status' => 'invalid',
            'last_error' => 'Laravel Cloud credentials are invalid. Reconnect Cloud and verify again.',
        ])->save();

        $this->transitionProjectsForEnvironments($environmentIds, [
            'is_public' => false,
            'verification_status' => 'failed',
            'verification_checked_at' => now(),
            'verification_failure_reason' => 'Laravel Cloud credentials are invalid. Reconnect Cloud and verify again.',
        ]);
    }

    /**
     * @param  array<int, int>  $environmentIds
     */
    private function markConnectionStale(CloudConnection $connection, array $environmentIds): void
    {
        $connection->forceFill([
            'last_error' => 'Laravel Cloud could not be reached.',
        ])->save();

        $this->transitionProjectsForEnvironments($environmentIds, [
            'is_public' => false,
            'verification_status' => 'stale',
            'verification_checked_at' => now(),
            'verification_failure_reason' => 'Laravel Cloud could not be reached.',
        ]);
    }

    /**
     * @param  array<int, int>  $environmentIds
     * @param  array<string, mixed>  $attributes
     */
    private function transitionProjectsForEnvironments(array $environmentIds, array $attributes): void
    {
        Project::query()
            ->whereIn('connected_environment_id', $environmentIds)
            ->where('is_demo', false)
            ->update($attributes);
    }

    public function invalidate(Project $project, string $reason): void
    {
        $project->forceFill([
            'is_public' => false,
            'verification_status' => 'unverified',
            'verified_at' => null,
            'verification_failure_reason' => $reason,
        ])->save();
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
