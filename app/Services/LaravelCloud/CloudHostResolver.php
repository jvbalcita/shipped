<?php

namespace App\Services\LaravelCloud;

/**
 * Resolves every A/AAAA answer for a host so the probe can reject
 * non-public addresses before any HTTP request is made.
 */
interface CloudHostResolver
{
    /**
     * @return array<int, string>
     */
    public function addresses(string $host): array;
}
