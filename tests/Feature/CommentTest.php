<?php

use App\Models\Cheer;
use App\Models\Comment;
use App\Models\Project;
use App\Models\User;

function commentableProject(): Project
{
    return Project::factory()
        ->public()
        ->for(User::factory(), 'creator')
        ->create();
}

test('a creator can comment and reply once on a project', function () {
    $user = verifiedUser();
    $project = commentableProject();

    $this->actingAs($user)
        ->post(route('projects.comments.store', $project), ['body' => 'Top-level comment'])
        ->assertRedirect();

    $parent = Comment::where('project_id', $project->id)->first();
    expect($parent)->not->toBeNull();

    $this->actingAs($user)
        ->post(route('projects.comments.store', $project), ['body' => 'A reply', 'parent_id' => $parent->id])
        ->assertSessionHasNoErrors();

    expect(Comment::where('project_id', $project->id)->count())->toBe(2);
});

test('replies are limited to a single level', function () {
    $user = verifiedUser();
    $project = commentableProject();
    $top = Comment::factory()->for($project)->for($user, 'user')->create();
    $reply = Comment::factory()->for($project)->for($user, 'user')->create(['parent_id' => $top->id]);

    $this->actingAs($user)
        ->post(route('projects.comments.store', $project), ['body' => 'Nested', 'parent_id' => $reply->id])
        ->assertSessionHasErrors('parent_id');
});

test('an author can edit a comment only within the edit window', function () {
    $author = verifiedUser();
    $project = commentableProject();
    $comment = Comment::factory()->for($project)->for($author, 'user')->create(['created_at' => now()->subMinutes(20)]);

    $this->actingAs($author)
        ->patch(route('projects.comments.update', [$project, $comment]), ['body' => 'Edited'])
        ->assertForbidden();
});

test('deleting a comment with replies keeps a placeholder', function () {
    $author = verifiedUser();
    $project = commentableProject();
    $top = Comment::factory()->for($project)->for($author, 'user')->create();
    Comment::factory()->for($project)->for(User::factory(), 'user')->create(['parent_id' => $top->id]);

    $this->actingAs($author)
        ->delete(route('projects.comments.destroy', [$project, $top]))
        ->assertRedirect();

    expect(Comment::where('id', $top->id)->exists())->toBeTrue();
    expect($top->fresh()->deleted_at)->not->toBeNull();
});

test('deleting a comment without replies removes it', function () {
    $author = verifiedUser();
    $project = commentableProject();
    $comment = Comment::factory()->for($project)->for($author, 'user')->create();

    $this->actingAs($author)
        ->delete(route('projects.comments.destroy', [$project, $comment]))
        ->assertRedirect();

    expect(Comment::where('id', $comment->id)->exists())->toBeFalse();
});

test('only the author can update or delete a comment', function () {
    $author = verifiedUser();
    $other = verifiedUser();
    $project = commentableProject();
    $comment = Comment::factory()->for($project)->for($author, 'user')->create();

    $this->actingAs($other)
        ->patch(route('projects.comments.update', [$project, $comment]), ['body' => 'Hacked'])
        ->assertForbidden();

    $this->actingAs($other)
        ->delete(route('projects.comments.destroy', [$project, $comment]))
        ->assertForbidden();
});

test('project cheers are polymorphic and toggle', function () {
    $cheerer = verifiedUser();
    $project = commentableProject();

    $this->actingAs($cheerer)->post(route('projects.cheers.store', $project))->assertRedirect();
    expect($project->cheers()->count())->toBe(1);
    expect(Cheer::where('cheerable_type', 'project')->where('cheerable_id', $project->id)->exists())->toBeTrue();

    $this->actingAs($cheerer)->post(route('projects.cheers.store', $project))->assertRedirect();
    expect($project->cheers()->count())->toBe(0);
});

test('comments can be cheered and toggled', function () {
    $cheerer = verifiedUser();
    $project = commentableProject();
    $comment = Comment::factory()->for($project)->for(User::factory(), 'user')->create();

    $this->actingAs($cheerer)->post(route('comments.cheers.store', $comment))->assertRedirect();
    expect($comment->cheers()->count())->toBe(1);

    $this->actingAs($cheerer)->post(route('comments.cheers.store', $comment))->assertRedirect();
    expect($comment->cheers()->count())->toBe(0);
});
