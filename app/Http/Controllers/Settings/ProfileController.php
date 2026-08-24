<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\Project;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $discoverableProjectIds = Project::query()
            ->discoverable()
            ->whereBelongsTo($user, 'creator')
            ->pluck('id')
            ->flip();

        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'profileProjects' => $user->projects()
                ->with('category:id,name')
                ->withCount([
                    'releases as published_releases_count' => fn ($query) => $query->published(),
                ])
                ->latest()
                ->get()
                ->map(fn (Project $project): array => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'tagline' => $project->tagline,
                    'category' => $project->category?->only('id', 'name'),
                    'profile_featured_order' => $project->profile_featured_order,
                    'is_discoverable' => $discoverableProjectIds->has($project->id),
                    'published_releases_count' => (int) $project->published_releases_count,
                ])
                ->values(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->except(['avatar', 'avatar_removal']);

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::delete($user->avatar_path);
            }

            $data['avatar_path'] = $request->file('avatar')->store('avatars');
        } elseif ($request->boolean('avatar_removal')) {
            if ($user->avatar_path) {
                Storage::delete($user->avatar_path);
            }

            $data['avatar_path'] = null;
        }

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
