<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
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
        $user = $this->user();
        $connection = $user instanceof User ? $user->cloudConnection()->first() : null;
        $connectionId = $connection === null ? 0 : $connection->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'tagline' => ['sometimes', 'required', 'string', 'max:160'],
            'description' => ['sometimes', 'required', 'string', 'max:2000'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'live_url' => ['nullable', 'url', 'max:255'],
            'connected_environment_id' => ['nullable', 'integer', Rule::exists('connected_environments', 'id')->where('cloud_connection_id', $connectionId)],
            'github_url' => ['nullable', 'url', 'max:255'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }
}
