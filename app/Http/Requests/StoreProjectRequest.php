<?php

namespace App\Http\Requests;

use App\Enums\ProjectPricing;
use App\Rules\OneTechnologyPerVersionGroup;
use App\Rules\SquareImage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'tagline' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:2000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'live_url' => ['nullable', 'url', 'max:255', 'required_without:github_url'],
            'github_url' => ['nullable', 'url', 'max:255', 'required_without:live_url'],
            'pricing' => ['nullable', Rule::enum(ProjectPricing::class)],
            'launch_date' => ['nullable', 'date'],
            'tags' => ['nullable', 'string', 'max:500'],
            'technologies' => ['nullable', 'array', 'max:16', new OneTechnologyPerVersionGroup],
            'technologies.*' => ['string', 'distinct', 'exists:technologies,slug'],
            'cover_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144', new SquareImage(256)],
            'screenshots' => ['required', 'array', 'min:1', 'max:5'],
            'screenshots.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'screenshots_captions' => ['nullable', 'array', 'max:5'],
            'screenshots_captions.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cover_image.required' => 'Add a cover image so the launch has a face.',
            'screenshots.required' => 'Add at least one screenshot as evidence.',
            'live_url.required_without' => 'Add a live URL or a source URL so people can find the project.',
            'github_url.required_without' => 'Add a live URL or a source URL so people can find the project.',
            'technologies.*.exists' => 'Choose stack items from the Built With list.',
        ];
    }
}
