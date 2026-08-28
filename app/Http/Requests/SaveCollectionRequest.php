<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by collection store and update: the curator is a trusted,
 * config-gated operator, so the rules carry the shape of an editorial
 * record (narrative + ordered membership) rather than UGC constraints.
 */
class SaveCollectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('curate');
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:10000'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'project_ids' => ['present', 'array'],
            'project_ids.*' => ['integer', 'distinct', 'exists:projects,id'],
        ];
    }

    /**
     * @return array{title: string, description: string, project_ids: list<int>}
     */
    public function validatedPayload(): array
    {
        $projectIds = [];

        foreach ((array) $this->validated('project_ids') as $id) {
            $projectIds[] = (int) $id;
        }

        return [
            'title' => (string) $this->validated('title'),
            'description' => (string) $this->validated('description'),
            'project_ids' => $projectIds,
        ];
    }
}
