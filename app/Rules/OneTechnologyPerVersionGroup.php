<?php

namespace App\Rules;

use App\Enums\TechnologyGroup;
use App\Models\Technology;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class OneTechnologyPerVersionGroup implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $slugs = is_array($value) ? $value : [];

        // The stack_group cast means pluck() yields enum instances.
        $selectedGroups = Technology::query()
            ->whereIn('slug', $slugs)
            ->whereIn('stack_group', [
                TechnologyGroup::LaravelVersion->value,
                TechnologyGroup::PhpVersion->value,
            ])
            ->pluck('stack_group')
            ->map(fn (mixed $selected): string => $selected instanceof TechnologyGroup ? $selected->value : (string) $selected);

        foreach ([TechnologyGroup::LaravelVersion, TechnologyGroup::PhpVersion] as $group) {
            if ($selectedGroups->filter(fn (string $selected): bool => $selected === $group->value)->count() > 1) {
                $fail("Choose only one {$group->label()}.");
            }
        }
    }
}
