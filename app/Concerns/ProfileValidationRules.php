<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function publicProfileRules(): array
    {
        return [
            'title' => $this->titleRules(),
            'location' => $this->locationRules(),
            'bio' => $this->bioRules(),
            'links' => $this->linksRules(),
            'links.*.type' => ['required_with:links', 'string', Rule::in(['website', 'github', 'twitter', 'linkedin'])],
            'links.*.url' => ['required_with:links', 'string', 'url', 'max:255'],
        ];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function bioRules(): array
    {
        return ['nullable', 'string', 'max:280'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function titleRules(): array
    {
        return ['required', 'string', 'max:50'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function locationRules(): array
    {
        return ['nullable', 'string', 'max:80'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function linksRules(): array
    {
        return ['nullable', 'array', 'max:8'];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }
}
