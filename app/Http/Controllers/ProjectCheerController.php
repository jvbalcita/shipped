<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;

class ProjectCheerController extends Controller
{
    public function store(Project $project): RedirectResponse
    {
        abort_unless($project->is_public, 404);
        $project->toggleCheer(request()->user());

        return back();
    }
}
