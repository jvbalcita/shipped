<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Response;

class BadgeController extends Controller
{
    /** @var array<string, array{label: string, color: string}> */
    private const STATUSES = [
        'verified' => ['label' => 'LIVE ON CLOUD', 'color' => '#16a34a'],
        'stale' => ['label' => 'STALE', 'color' => '#d97706'],
        'failed' => ['label' => 'VERIFICATION FAILED', 'color' => '#dc2626'],
        'unverified' => ['label' => 'UNVERIFIED', 'color' => '#6b7280'],
    ];

    /**
     * Render the README verification badge as a self-contained SVG.
     * Only discoverable (publicly filed) launches get a badge — same
     * privacy rule as the OG preview images.
     */
    public function show(Project $project): Response
    {
        abort_unless(Project::query()->discoverable()->whereKey($project)->exists(), 404);

        $status = self::STATUSES[$project->verification_status] ?? self::STATUSES['unverified'];

        return response()
            ->view('badges.project', [
                'project' => $project,
                'label' => $status['label'],
                'color' => $status['color'],
            ])
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=300');
    }
}
