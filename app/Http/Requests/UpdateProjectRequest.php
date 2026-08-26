<?php

namespace App\Http\Requests;

use App\Enums\ProjectPricing;
use App\Models\Project;
use App\Rules\OneTechnologyPerVersionGroup;
use App\Rules\SquareImage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->project) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'tagline' => ['sometimes', 'required', 'string', 'max:160'],
            'description' => ['sometimes', 'required', 'string', 'max:2000'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'live_url' => ['nullable', 'url', 'max:255'],
            'github_url' => ['nullable', 'url', 'max:255'],
            'pricing' => ['sometimes', 'nullable', Rule::enum(ProjectPricing::class)],
            'launch_date' => ['sometimes', 'nullable', 'date'],
            'tags' => ['sometimes', 'nullable', 'string', 'max:500'],
            'technologies' => ['sometimes', 'nullable', 'array', 'max:12', new OneTechnologyPerVersionGroup],
            'technologies.*' => ['string', 'distinct', 'exists:technologies,slug'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'cover_removal' => ['sometimes', 'boolean'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144', new SquareImage(256)],
            'logo_removal' => ['sometimes', 'boolean'],
            'screenshots' => ['nullable', 'array', 'max:5'],
            'screenshots.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'screenshots_captions' => ['nullable', 'array', 'max:5'],
            'screenshots_captions.*' => ['nullable', 'string', 'max:255'],
            'screenshot_order' => ['nullable', 'array'],
            'screenshot_order.*' => ['integer', 'exists:project_screenshots,id'],
            'screenshot_captions' => ['nullable', 'array'],
            'screenshot_captions.*' => ['nullable', 'string', 'max:255'],
            'removed_screenshots' => ['nullable', 'array'],
            'removed_screenshots.*' => ['integer', 'exists:project_screenshots,id'],
        ];
    }

    /**
     * Enforce the five-screenshot ceiling accounting for existing screenshots,
     * removals, and new uploads (the `screenshots:max:5` rule only counts uploads).
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $project = $this->route('project');

                if (! $project instanceof Project) {
                    return;
                }

                $existingIds = $project->screenshots()->pluck('id')->all();
                $removing = array_intersect(
                    $existingIds,
                    array_map('intval', $this->input('removed_screenshots', [])),
                );
                $newCount = count($this->file('screenshots', []));

                if ((count($existingIds) - count($removing) + $newCount) > 5) {
                    $validator->errors()->add('screenshots', __('A project may have a maximum of 5 screenshots.'));
                }
            },
        ];
    }
}
