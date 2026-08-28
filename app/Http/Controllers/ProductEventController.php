<?php

namespace App\Http\Controllers;

use App\Enums\ProductEventName;
use App\Http\Requests\StoreProductEventRequest;
use App\Models\Collection;
use App\Services\ProductEventRecorder;
use Illuminate\Http\JsonResponse;

class ProductEventController extends Controller
{
    public function __construct(private readonly ProductEventRecorder $recorder) {}

    public function store(StoreProductEventRequest $request): JsonResponse
    {
        // Collection-context events prove membership instead of ownership:
        // any visitor may record a click on a real collection member.
        if ($request->filled('collection_id')) {
            $collection = Collection::query()->findOrFail($request->integer('collection_id'));
            $project = $collection->projects()->findOrFail($request->integer('project_id'));
        } else {
            $project = $request->filled('project_id')
                ? $request->user()->projects()->findOrFail($request->integer('project_id'))
                : null;
        }

        $network = $request->validated('network');
        $properties = is_string($network) ? ['network' => $network] : [];

        if ($request->filled('collection_id')) {
            $properties['collection_id'] = $request->integer('collection_id');
        }

        $this->recorder->record(
            ProductEventName::from($request->string('name')->toString()),
            $request->user(),
            $project,
            $properties,
        );

        return response()->json(['recorded' => true], 201);
    }
}
