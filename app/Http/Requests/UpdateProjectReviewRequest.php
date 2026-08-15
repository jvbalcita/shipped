<?php

namespace App\Http\Requests;

use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to update this review (author only).
     */
    public function authorize(): bool
    {
        $review = $this->route('review');

        return $review instanceof Review
            && $this->user()?->can('update', $review) === true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'rating' => ['sometimes', 'required', 'integer', 'between:1,5'],
            'body' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
