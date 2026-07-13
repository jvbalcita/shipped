<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $user = $this->user();
        $connection = $user instanceof User ? $user->cloudConnection()->first() : null;
        $connectionId = $connection === null ? 0 : $connection->id;

        return [
            'connected_environment_id' => [
                'required',
                'integer',
                Rule::exists('connected_environments', 'id')->where('cloud_connection_id', $connectionId),
            ],
        ];
    }
}
