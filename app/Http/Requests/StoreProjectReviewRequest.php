<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\Review;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectReviewRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'body' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Enforce one review per creator per project (also backed by a unique index).
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        $project = $this->route('project');
        $userId = $this->user()?->id;

        return [
            function (Validator $validator) use ($project, $userId): void {
                if (! $project instanceof Project || $userId === null) {
                    return;
                }

                $alreadyReviewed = Review::query()
                    ->where('project_id', $project->id)
                    ->where('user_id', $userId)
                    ->exists();

                if ($alreadyReviewed) {
                    $validator->errors()->add('rating', __('You have already reviewed this project.'));
                }
            },
        ];
    }
}
