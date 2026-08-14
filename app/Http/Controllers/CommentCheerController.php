<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\RedirectResponse;

class CommentCheerController extends Controller
{
    public function store(Comment $comment): RedirectResponse
    {
        $comment->toggleCheer(request()->user());

        return back();
    }
}
