<?php

use App\Models\Project;
use App\Models\Review;
use App\Models\User;

function reviewableProject(): Project
{
    return Project::factory()->for(User::factory(), 'creator')->create();
}

test('a creator can post one review per project', function () {
    $creator = verifiedUser();
    $project = reviewableProject();

    $this->actingAs($creator)
        ->post(route('projects.reviews.store', $project), [
            'rating' => 4,
            'body' => 'Solid launch.',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect(Review::where('project_id', $project->id)->where('user_id', $creator->id)->exists())->toBeTrue();

    $this->actingAs($creator)
        ->post(route('projects.reviews.store', $project), ['rating' => 5])
        ->assertSessionHasErrors('rating');

    expect(Review::where('project_id', $project->id)->count())->toBe(1);
});

test('review rating must be between 1 and 5', function () {
    $creator = verifiedUser();
    $project = reviewableProject();

    $this->actingAs($creator)
        ->post(route('projects.reviews.store', $project), ['rating' => 6])
        ->assertSessionHasErrors('rating');

    $this->actingAs($creator)
        ->post(route('projects.reviews.store', $project), ['rating' => 0])
        ->assertSessionHasErrors('rating');

    expect(Review::count())->toBe(0);
});

test('only the author can update or delete their review', function () {
    $author = verifiedUser();
    $other = verifiedUser();
    $project = reviewableProject();
    $review = Review::factory()->for($project)->for($author, 'user')->create(['rating' => 3]);

    $this->actingAs($other)
        ->patch(route('projects.reviews.update', [$project, $review]), ['rating' => 5])
        ->assertForbidden();

    $this->actingAs($other)
        ->delete(route('projects.reviews.destroy', [$project, $review]))
        ->assertForbidden();

    expect($review->fresh()->rating)->toBe(3);
});

test('the author can update and delete their review and the aggregate updates', function () {
    $author = verifiedUser();
    $project = reviewableProject();
    Review::factory()->for($project)->create(['rating' => 5]);
    $review = Review::factory()->for($project)->for($author, 'user')->create(['rating' => 2]);

    expect(round((float) $project->reviews()->avg('rating'), 1))->toBe(3.5);

    $this->actingAs($author)
        ->patch(route('projects.reviews.update', [$project, $review]), ['rating' => 4])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($review->fresh()->rating)->toBe(4);
    expect(round((float) $project->reviews()->avg('rating'), 1))->toBe(4.5);

    $this->actingAs($author)
        ->delete(route('projects.reviews.destroy', [$project, $review]))
        ->assertRedirect();

    expect(Review::where('id', $review->id)->exists())->toBeFalse();
    expect(round((float) $project->reviews()->avg('rating'), 1))->toBe(5.0);
});

test('guests cannot post a review', function () {
    $project = reviewableProject();

    $this->post(route('projects.reviews.store', $project), ['rating' => 4])
        ->assertRedirect(route('login'));
});
