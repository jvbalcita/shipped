<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DiscoverController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'exists:categories,slug'],
        ]);

        $projects = Project::query()
            ->discoverable()
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('tagline', 'like', "%{$search}%")))
            ->when($filters['category'] ?? null, fn ($query, $slug) => $query->whereHas('category', fn ($query) => $query->where('slug', $slug)))
            ->with(['creator', 'category'])
            ->withCount('cheers')
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return Inertia::render('Discover/Index', [
            'projects' => $projects,
            'categories' => Category::query()->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }
}
