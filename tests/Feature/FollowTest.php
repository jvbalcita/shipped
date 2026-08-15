<?php

use App\Models\Follow;
use App\Models\Project;
use App\Models\User;

test('a member can follow and unfollow a creator', function () {
    $creator = User::factory()->create();
    $member = verifiedUser();

    $this->actingAs($member)
        ->post(route('users.follow.store', $creator))
        ->assertRedirect();

    expect(Follow::query()->where([
        'user_id' => $member->id,
        'followable_type' => 'user',
        'followable_id' => $creator->id,
    ])->exists())->toBeTrue();

    $this->actingAs($member)
        ->delete(route('users.follow.destroy', $creator))
        ->assertRedirect();

    expect($member->followings()->count())->toBe(0);
});

test('a member can follow and unfollow a project', function () {
    $project = Project::factory()->for(User::factory(), 'creator')->create();
    $member = verifiedUser();

    $this->actingAs($member)
        ->post(route('projects.follow.store', $project))
        ->assertRedirect();

    expect(Follow::query()->where([
        'user_id' => $member->id,
        'followable_type' => 'project',
        'followable_id' => $project->id,
    ])->exists())->toBeTrue();

    $this->actingAs($member)
        ->delete(route('projects.follow.destroy', $project))
        ->assertRedirect();

    expect($member->followings()->count())->toBe(0);
});

test('following the same target twice creates a single follow', function () {
    $creator = User::factory()->create();
    $member = verifiedUser();

    $member->addFollower($member);
    $creator->addFollower($member);
    $creator->addFollower($member);

    expect($creator->followers()->count())->toBe(1);
});

test('a member cannot follow themselves', function () {
    $member = verifiedUser();

    $this->actingAs($member)
        ->post(route('users.follow.store', $member))
        ->assertForbidden();
});

test('a member may follow their own project', function () {
    $member = verifiedUser();
    $project = Project::factory()->for($member, 'creator')->create();

    $this->actingAs($member)
        ->post(route('projects.follow.store', $project))
        ->assertRedirect();

    expect($project->followers()->count())->toBe(1);
});

test('a guest following a creator is redirected to login', function () {
    $creator = User::factory()->create();

    $this->post(route('users.follow.store', $creator))
        ->assertRedirect(route('login'));
});
