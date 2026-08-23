<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\ShipStory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveShipStoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            && ($this->user()?->can('update', $project) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'problem' => ['nullable', 'string', 'max:2000'],
            'audience' => ['nullable', 'string', 'max:2000'],
            'shipped' => ['nullable', 'string', 'max:2000'],
            'build_decisions' => ['nullable', 'string', 'max:2000'],
            'hardest_problem' => ['nullable', 'string', 'max:2000'],
            'lessons_learned' => ['nullable', 'string', 'max:2000'],
            'next' => ['nullable', 'string', 'max:2000'],
            'approve' => ['sometimes', 'boolean'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        if (! $this->boolean('approve')) {
            return [];
        }

        return [function (Validator $validator): void {
            foreach (ShipStory::requiredContentFields() as $field) {
                if (trim((string) $this->input($field)) === '') {
                    $validator->errors()->add($field, 'Complete every core prompt before approving your Ship Story.');
                }
            }
        }];
    }
}
