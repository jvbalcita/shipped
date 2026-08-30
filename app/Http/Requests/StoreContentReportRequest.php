<?php

namespace App\Http\Requests;

use App\Enums\ContentReportReason;
use App\Models\Comment;
use App\Models\ContentReport;
use App\Models\Project;
use App\Models\Review;
use App\Policies\ContentReportPolicy;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContentReportRequest extends FormRequest
{
    /**
     * Allowed reportable morph-map keys with their model classes.
     *
     * @return array<string, class-string>
     */
    public static function reportableTypes(): array
    {
        return [
            'project' => Project::class,
            'comment' => Comment::class,
            'review' => Review::class,
        ];
    }

    /**
     * Route middleware already guarantees a signed-in, verified reporter.
     * Every rule below — including the self-report denial — surfaces as a
     * validation error so stale UI gets a field error instead of a 403.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reportable_type' => ['required', 'string', Rule::in(array_keys(self::reportableTypes()))],
            'reportable_id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', Rule::enum(ContentReportReason::class)],
            'note' => [
                'nullable',
                'string',
                'max:1000',
                Rule::requiredIf($this->input('reason') === ContentReportReason::Other->value),
            ],
        ];
    }

    /**
     * The subject must exist, be publicly visible, and not be the reporter's
     * own content; a reporter gets one open report per subject — resolved
     * reports may be filed again.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $reportable = $this->reportable();

                if ($reportable === null) {
                    $validator->errors()->add('reportable_id', __('The content you are reporting no longer exists.'));

                    return;
                }

                if ($reportable instanceof Comment && $reportable->isDeleted()) {
                    $validator->errors()->add('reportable_id', __('The content you are reporting is no longer visible.'));
                }

                if ($reportable instanceof Project && ! $reportable->is_public) {
                    $validator->errors()->add('reportable_id', __('The content you are reporting is no longer visible.'));
                }

                if (! (new ContentReportPolicy)->create($this->user(), $reportable)) {
                    $validator->errors()->add('reportable_id', __('You cannot report your own content.'));
                }

                $alreadyOpen = ContentReport::query()
                    ->where('reporter_id', $this->user()->getKey())
                    ->where('reportable_type', $reportable->getMorphClass())
                    ->where('reportable_id', $reportable->getKey())
                    ->open()
                    ->exists();

                if ($alreadyOpen) {
                    $validator->errors()->add('reportable_id', __('You already have an open report on this content.'));
                }
            },
        ];
    }

    /**
     * The resolved reportable subject, or null when it is gone or the type
     * key is invalid.
     */
    public function reportable(): Project|Comment|Review|null
    {
        $type = self::reportableTypes()[$this->input('reportable_type')] ?? null;

        if ($type === null) {
            return null;
        }

        $reportable = $type::query()->find($this->input('reportable_id'));

        return $reportable instanceof Project || $reportable instanceof Comment || $reportable instanceof Review
            ? $reportable
            : null;
    }
}
