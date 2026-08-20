<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Rules\LaravelCloudUrlRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && $this->user()?->can('update', $project) === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'laravel_cloud_url' => ['required', 'string', 'max:255', new LaravelCloudUrlRule],
        ];
    }
}
