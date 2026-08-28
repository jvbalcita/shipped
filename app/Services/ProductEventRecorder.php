<?php

namespace App\Services;

use App\Enums\ProductEventName;
use App\Models\ProductEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Single writer for the product event log. Every roadmap evidence event goes
 * through record() so the name vocabulary, actor, and subject handling stay
 * in one place. Insert failures are allowed to surface in tests but must
 * never leak user data into properties.
 */
final class ProductEventRecorder
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function record(ProductEventName $name, ?User $actor = null, ?Model $subject = null, array $properties = []): void
    {
        ProductEvent::query()->create([
            'name' => $name->value,
            'creator_id' => $actor?->id,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $properties === [] ? null : $properties,
        ]);
    }
}
