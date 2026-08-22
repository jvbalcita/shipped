<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Rules\LaravelCloudUrlRule;
use App\Services\LaravelCloud\LaravelCloudUrl;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && $this->user()?->can('update', $project) === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'laravel_cloud_url' => ['required', 'string', 'max:255', new LaravelCloudUrlRule],
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
