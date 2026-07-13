<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class ConnectedEnvironmentController extends Controller
{
    public function index(): JsonResponse
    {
        $environments = $this->currentUser()
            ->cloudConnection()
            ->first()?->connectedEnvironments()
            ->orderBy('application_name')
            ->orderBy('environment_name')
            ->get([
                'id',
                'application_id',
                'application_name',
                'environment_id',
                'environment_name',
                'domains',
                'synced_at',
            ]) ?? collect();

        return response()->json($environments);
    }
}
