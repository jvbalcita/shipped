<?php

namespace App\Rules;

use App\Services\LaravelCloud\LaravelCloudUrl;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class LaravelCloudUrlRule implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || LaravelCloudUrl::tryFrom($value) === null) {
            $fail('Enter the HTTPS `*.laravel.cloud` URL assigned to this environment.');
        }
    }
}
