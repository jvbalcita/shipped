<?php

namespace App\Http\Requests;

use App\Models\Comment;
use App\Models\Project;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectCommentRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:500'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ];
    }

    /**
     * Enforce single-level replies: a parent must be an existing, non-deleted,
     * top-level comment in the same project.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        $project = $this->route('project');

        return [
            function (Validator $validator) use ($project): void {
                $parentId = $this->input('parent_id');
                if ($parentId === null) {
                    return;
                }

                /** @var Comment|null $parent */
                $parent = Comment::query()->find($parentId);
                $valid = $parent !== null
                    && $project instanceof Project
                    && $parent->project_id === $project->id
                    && $parent->parent_id === null
                    && $parent->deleted_at === null;

                if (! $valid) {
                    $validator->errors()->add('parent_id', __('Replies must target an existing top-level comment.'));
                }
            },
        ];
    }
}
