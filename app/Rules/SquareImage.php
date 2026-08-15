<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Translation\PotentiallyTranslatedString;

class SquareImage implements ValidationRule
{
    public function __construct(private int $minimumEdge = 256) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return;
        }

        $size = @getimagesize($value->getPathname());

        if ($size === false) {
            $fail('The :attribute must be a valid image.');

            return;
        }

        [$width, $height] = $size;

        if ($width !== $height) {
            $fail('The :attribute must be a square image.');

            return;
        }

        if ($width < $this->minimumEdge) {
            $fail("The :attribute must be at least {$this->minimumEdge}×{$this->minimumEdge} pixels.");
        }
    }
}
