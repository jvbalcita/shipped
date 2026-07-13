<?php

namespace App\Services\LaravelCloud;

final readonly class CloudEnvironmentData
{
    /**
     * @param  array<int, string>  $domains
     */
    public function __construct(
        public string $applicationId,
        public string $applicationName,
        public string $environmentId,
        public string $environmentName,
        public array $domains,
    ) {}
}
