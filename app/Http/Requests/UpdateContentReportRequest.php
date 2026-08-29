<?php

namespace App\Http\Requests;

use App\Enums\ContentReportResolution;
use App\Models\ContentReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContentReportRequest extends FormRequest
{
    /**
     * Only curators reach this request — the route middleware enforces the
     * curate gate before authorization gets here.
     */
    public function authorize(): bool
    {
        return $this->user()->can('curate') && $this->route('report') instanceof ContentReport;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'resolution' => ['required', Rule::enum(ContentReportResolution::class)],
            'resolution_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
