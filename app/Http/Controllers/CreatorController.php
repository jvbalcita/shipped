<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class CreatorController extends Controller
{
    public function show(User $creator): Response
    {
        return Inertia::render('Creators/Show', [
            'creator' => $creator->only('name', 'handle', 'bio', 'avatar_path'),
            'projects' => $creator->projects()->discoverable()->with('category')->withCount('cheers')->latest()->get(),
        ]);
    }
}
