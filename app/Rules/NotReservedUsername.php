<?php

namespace App\Rules;

use App\Models\ReservedUsername;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class NotReservedUsername implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (ReservedUsername::active()->where('username', $value)->exists()) {
            $fail('The :attribute is not available.');
        }
    }
}
