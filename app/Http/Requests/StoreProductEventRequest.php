<?php

namespace App\Http\Requests;

use App\Enums\ProductEventName;
use App\Models\Collection;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreProductEventRequest extends FormRequest
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
            'name' => ['required', 'string'],
            'project_id' => ['nullable', 'integer'],
            'collection_id' => ['nullable', 'integer'],
            'network' => ['nullable', 'string', 'in:x,linkedin,reddit'],
        ];
    }

    /**
     * Only the client-recordable event vocabulary may arrive from the
     * browser, a subject project must belong to the acting creator, and
     * a collection-context project must actually be a member.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $event = ProductEventName::tryFrom($this->string('name')->toString());

            if ($event === null || ! $event->canBeRecordedByClient()) {
                $validator->errors()->add('name', 'This event cannot be recorded from the client.');

                return;
            }

            $projectId = $this->integer('project_id');

            if ($this->filled('collection_id')) {
                $isMember = Collection::query()
                    ->whereKey($this->integer('collection_id'))
                    ->whereHas('projects', fn (Builder $query): Builder => $query->whereKey($projectId))
                    ->exists();

                if (! $isMember) {
                    $validator->errors()->add('project_id', 'The project is not a member of this collection.');
                }

                return;
            }

            if ($this->filled('project_id')
                && ! $this->user()?->projects()->whereKey($projectId)->exists()) {
                $validator->errors()->add('project_id', 'The project does not belong to you.');
            }
        });
    }
}
