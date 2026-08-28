<?php

namespace App\Http\Controllers;

use App\Enums\ProductEventName;
use App\Http\Requests\StoreProductEventRequest;
use App\Services\ProductEventRecorder;
use Illuminate\Http\JsonResponse;

class ProductEventController extends Controller
{
    public function __construct(private readonly ProductEventRecorder $recorder) {}

    public function store(StoreProductEventRequest $request): JsonResponse
    {
        $project = $request->filled('project_id')
            ? $request->user()->projects()->findOrFail($request->integer('project_id'))
            : null;

        $network = $request->validated('network');

        $this->recorder->record(
            ProductEventName::from($request->string('name')->toString()),
            $request->user(),
            $project,
            is_string($network) ? ['network' => $network] : [],
        );

        return response()->json(['recorded' => true], 201);
    }
}
