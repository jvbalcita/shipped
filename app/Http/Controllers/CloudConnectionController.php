<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class CloudConnectionController extends Controller
{
    public function destroy(): RedirectResponse
    {
        $connection = request()->user()->cloudConnection;

        if ($connection === null) {
            return to_route('dashboard');
        }

        DB::transaction(function () use ($connection): void {
            Project::query()
                ->whereHas('connectedEnvironment', fn ($query) => $query->whereBelongsTo($connection))
                ->update([
                    'is_public' => false,
                    'verification_status' => 'unverified',
                    'verified_at' => null,
                    'verification_checked_at' => now(),
                    'verification_failure_reason' => 'Laravel Cloud connection removed.',
                ]);

            $connection->delete();
        });

        return to_route('dashboard');
    }
}
