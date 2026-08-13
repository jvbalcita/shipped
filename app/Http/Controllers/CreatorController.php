<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CreatorController extends Controller
{
    public function show(User $creator): Response
    {
        return Inertia::render('Creators/Show', [
            'creator' => [
                ...$creator->only('name', 'username', 'title', 'location', 'bio', 'avatar_path', 'links'),
                'avatar_url' => $creator->avatar_path === null
                    ? null
                    : Storage::disk()->url($creator->avatar_path),
            ],
            'projects' => $creator->projects()->discoverable()->with(['creator', 'category', 'tags'])->withCount('cheers')->latest()->get(),
            'ogTitle' => $creator->name.' — Shipped',
            'ogDescription' => 'Public launches filed by @'.$creator->username.'.',
        ]);
    }
}
