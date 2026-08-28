<?php

namespace App\Services\GitHub;

/**
 * A minimal Composer/npm version constraint reader for stack observation.
 *
 * Repositories declare dependency constraints ("^12.0"), not installed
 * versions, so a claimed version is resolved to the constraint's floor —
 * the lowest version the declaration admits — and a technology's
 * observation key ("laravel/framework:^12") is satisfied when that floor
 * still satisfies the key. This stays honest: ">=8.1" can never evidence
 * "PHP 8.4", and "*" evidences nothing.
 */
final class ComposerConstraint
{
    /**
     * @param  array<int, array{0: string, 1: array{int, int, int}}>  $parts  Operator plus version tuples ANDed together.
     */
    private function __construct(private readonly array $parts) {}

    public static function parse(string $constraint): self
    {
        $alternatives = array_values(array_filter(
            array_map(trim(...), explode('||', $constraint)),
            fn (string $part): bool => $part !== '',
        ));

        $best = new self([]);

        foreach ($alternatives as $alternative) {
            $candidate = new self(self::parseAnd($alternative));

            if ($candidate->floorIsHigherThan($best)) {
                $best = $candidate;
            }
        }

        return $best;
    }

    /**
     * The lowest version this constraint admits, or null when the
     * constraint admits any version ("*", "dev-main") or is unreadable.
     * Upper bounds never move the floor.
     *
     * @return array{int, int, int}|null
     */
    public function floor(): ?array
    {
        $floor = null;

        foreach ($this->parts as [$operator, $version]) {
            if ($operator === '*' || $operator === 'invalid') {
                return null;
            }

            if ($operator === '<' || $operator === '<=') {
                continue;
            }

            $floor ??= $version;

            if (self::compare($version, $floor) > 0) {
                $floor = $version;
            }
        }

        return $floor;
    }

    /**
     * Whether a declared constraint's floor still satisfies this
     * constraint — the test that matches a claim to a version technology.
     */
    public function admitsFloorOf(self $declared): bool
    {
        $floor = $declared->floor();

        if ($floor === null || $this->parts === []) {
            return false;
        }

        foreach ($this->parts as [$operator, $version]) {
            if (! self::satisfies($floor, $operator, $version)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, array{0: string, 1: array{int, int, int}}>
     */
    private static function parseAnd(string $constraint): array
    {
        $parts = preg_split('/[,\s]+/', $constraint) ?: [];

        $parsed = [];

        foreach ($parts as $part) {
            if ($part === '' || $part === '-') {
                continue;
            }

            $parsed[] = self::parsePart($part);
        }

        return $parsed;
    }

    /**
     * @return array{0: string, 1: array{int, int, int}}
     */
    private static function parsePart(string $part): array
    {
        if (preg_match('/^(\^|~|>=|<=|>|<|=|==)?\s*(.+)$/', $part, $matches) !== 1) {
            return ['invalid', [0, 0, 0]];
        }

        $operator = $matches[1] === '' ? '=' : ($matches[1] === '==' ? '=' : $matches[1]);
        $raw = strtolower(trim($matches[2]));

        if (in_array($raw, ['*', 'x', ''], true) || str_starts_with($raw, 'dev-') || $raw === 'v') {
            return ['*', [0, 0, 0]];
        }

        if (! preg_match('/^v?(\d+)(?:\.(\d+|x|\*))?(?:\.(\d+|x|\*))?(?:-[0-9a-z.\-]+)?$/', $raw, $version)) {
            return ['invalid', [0, 0, 0]];
        }

        $major = (int) $version[1];
        $rawMinor = $version[2] ?? null;
        $rawPatch = $version[3] ?? null;

        if ($rawMinor === null || $rawMinor === 'x' || $rawMinor === '*') {
            // "12" and "12.x" admit any minor — treat as a whole-major
            // wildcard so it never evidences a specific minor claim.
            return ['*', [$major, 0, 0]];
        }

        $minor = (int) $rawMinor;
        $patch = ($rawPatch === null || $rawPatch === 'x' || $rawPatch === '*') ? 0 : (int) $rawPatch;

        return [$operator, [$major, $minor, $patch]];
    }

    /**
     * @param  array{int, int, int}  $left
     * @param  array{int, int, int}  $right
     */
    private static function compare(array $left, array $right): int
    {
        return $left <=> $right;
    }

    /**
     * @param  array{int, int, int}  $version
     * @param  array{int, int, int}  $bound
     */
    private static function satisfies(array $version, string $operator, array $bound): bool
    {
        $comparison = self::compare($version, $bound);

        return match ($operator) {
            '=' => $comparison === 0,
            '>=' => $comparison >= 0,
            '>' => $comparison > 0,
            '<=' => $comparison <= 0,
            '<' => $comparison < 0,
            '^' => $version[0] === $bound[0] && $comparison >= 0,
            '~' => $version[0] === $bound[0] && $version[1] === $bound[1] && $comparison >= 0,
            default => false,
        };
    }

    private function floorIsHigherThan(?self $other): bool
    {
        $mine = $this->floor();
        $theirs = $other?->floor();

        if ($mine === null) {
            return false;
        }

        if ($theirs === null) {
            return true;
        }

        return self::compare($mine, $theirs) > 0;
    }
}
