<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectCommentRequest;
use App\Http\Requests\UpdateProjectCommentRequest;
use App\Models\Comment;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;

class ProjectCommentController extends Controller
{
    public function store(StoreProjectCommentRequest $request, Project $project): RedirectResponse
    {
        $project->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->input('body'),
            'parent_id' => $request->input('parent_id'),
        ]);

        return back();
    }

    public function update(UpdateProjectCommentRequest $request, Project $project, Comment $comment): RedirectResponse
    {
        // Authors may edit only within a 15-minute window after posting.
        abort_unless(
            $comment->created_at !== null && $comment->created_at->gt(now()->subMinutes(15)),
            403,
            __('The comment edit window has closed.'),
        );

        $comment->update(['body' => $request->input('body')]);

        return back();
    }

    public function destroy(Project $project, Comment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        // Preserve a "[deleted]" placeholder when the comment has replies so the
        // thread stays intact; otherwise remove it outright. The render keys off
        // deleted_at (body stays intact in storage).
        if ($comment->replies()->exists()) {
            $comment->forceFill(['deleted_at' => now()])->save();
        } else {
            $comment->delete();
        }

        return back();
    }
}
