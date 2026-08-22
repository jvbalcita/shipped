<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Rules\LaravelCloudUrlRule;
use App\Services\LaravelCloud\LaravelCloudUrl;
use App\Services\LaravelCloud\ProjectVerificationService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && $this->user()?->can('update', $project) === true;
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->input('laravel_cloud_url');

        if (! is_string($raw)) {
            return;
        }

        $canonical = LaravelCloudUrl::tryFrom($raw);

        if ($canonical === null) {
            return;
        }

        $this->merge([
            'laravel_cloud_url' => $canonical->url(),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'laravel_cloud_url' => [
                'required',
                'string',
                'max:255',
                'bail',
                new LaravelCloudUrlRule,
                Rule::unique('projects', 'laravel_cloud_url')->ignore($project),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'laravel_cloud_url.unique' => ProjectVerificationService::ORIGIN_ALREADY_USED,
        ];
    }

    /**
     * A verified origin is frozen. Recheck may re-probe the stored
     * canonical URL; a different origin is rejected before the probe.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $project = $this->route('project');

                if (! $project instanceof Project || $project->verification_status !== 'verified') {
                    return;
                }

                $submitted = LaravelCloudUrl::tryFrom((string) $this->input('laravel_cloud_url'));
                $stored = $project->cloudUrl();

                if ($submitted !== null && $stored !== null && $submitted->url() === $stored->url()) {
                    return;
                }

                $validator->errors()->add(
                    'laravel_cloud_url',
                    __('The Laravel Cloud URL cannot be changed after verification.'),
                );
            },
        ];
    }
}
