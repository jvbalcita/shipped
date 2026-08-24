<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateFeaturedProjectsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_ids' => ['present', 'array', 'max:3'],
            'project_ids.*' => ['integer', 'distinct', 'exists:projects,id'],
        ];
    }

    /**
     * Validate that every selected project is owned by the Creator and
     * currently eligible for public discovery.
     *
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty() || $this->user() === null) {
                    return;
                }

                $projectIds = collect((array) $this->input('project_ids'))
                    ->map(static fn (mixed $id): int => (int) $id)
                    ->all();

                $eligibleProjectCount = $this->user()
                    ->projects()
                    ->discoverable()
                    ->whereKey($projectIds)
                    ->count();

                if ($eligibleProjectCount !== count($projectIds)) {
                    $validator->errors()->add(
                        'project_ids',
                        'Select only your currently discoverable projects.',
                    );
                }
            },
        ];
    }
}
