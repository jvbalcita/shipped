<?php

namespace App\Services\GitHub;

/**
 * Matches a technology's observation keys against the dependency
 * declarations read from a repository.
 *
 * A key is either a literal package name ("vue", "livewire/livewire") or
 * a constraint form ("laravel/framework:^12", "php:>=8.1") used by the
 * version technologies. Constraint keys are satisfied when the declared
 * constraint's floor still satisfies the key's constraint.
 */
final class ObservationKeyMatcher
{
    /**
     * @param  array<int, string>  $observationKeys
     * @param  array<string, string>  $declared  Dependency name to constraint string.
     */
    public static function matches(array $observationKeys, array $declared): bool
    {
        foreach ($observationKeys as $key) {
            if ($key === '') {
                continue;
            }

            if (self::matchesKey($key, $declared)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, string>  $declared
     */
    private static function matchesKey(string $key, array $declared): bool
    {
        $separator = strpos($key, ':');

        if ($separator === false) {
            return array_key_exists($key, $declared);
        }

        $name = substr($key, 0, $separator);
        $constraint = substr($key, $separator + 1);

        if ($name === '' || $constraint === '') {
            return false;
        }

        $declaredConstraint = $declared[$name] ?? null;

        if ($declaredConstraint === null) {
            return false;
        }

        return ComposerConstraint::parse($constraint)
            ->admitsFloorOf(ComposerConstraint::parse($declaredConstraint));
    }
}
