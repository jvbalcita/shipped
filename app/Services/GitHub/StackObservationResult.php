<?php

namespace App\Services\GitHub;

/**
 * The outcome of one stack observation pass.
 *
 * @property array<int, string> $observed Names of the technologies the evidence supports.
 */
final class StackObservationResult
{
    /**
     * @param  array<int, string>  $observed
     */
    private function __construct(
        public readonly array $observed,
        public readonly ?StackObservationFailureReason $failureReason,
    ) {}

    /**
     * @param  array<int, string>  $observed
     */
    public static function observed(array $observed): self
    {
        return new self($observed, null);
    }

    public static function failed(StackObservationFailureReason $reason): self
    {
        return new self([], $reason);
    }

    public function succeeded(): bool
    {
        return $this->failureReason === null;
    }
}
