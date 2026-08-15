<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a creator can upload an avatar from profile settings', function () {
    Storage::fake();

    $user = User::factory()->create();
    $avatar = UploadedFile::fake()->image('avatar.jpg', 400, 400);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'title' => 'Creator',
            'avatar' => $avatar,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->avatar_path)->not->toBeNull();
    Storage::assertExists($user->avatar_path);
});

test('avatar upload rejects invalid types and oversized files', function () {
    Storage::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'title' => 'Creator',
            'avatar' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('avatar');

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'title' => 'Creator',
            'avatar' => UploadedFile::fake()->image('huge.jpg')->size(4096),
        ])
        ->assertSessionHasErrors('avatar');
});

test('replacing an avatar deletes the previous file', function () {
    Storage::fake();

    $user = User::factory()->create();
    $first = UploadedFile::fake()->image('first.jpg', 320, 320);

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'title' => 'Creator',
        'avatar' => $first,
    ])->assertSessionHasNoErrors();

    $firstPath = $user->refresh()->avatar_path;
    expect($firstPath)->not->toBeNull();
    Storage::assertExists($firstPath);

    $second = UploadedFile::fake()->image('second.jpg', 320, 320);

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'title' => 'Creator',
        'avatar' => $second,
    ])->assertSessionHasNoErrors();

    $secondPath = $user->refresh()->avatar_path;

    expect($secondPath)->not->toBe($firstPath);
    Storage::assertMissing($firstPath);
    Storage::assertExists($secondPath);
});

test('public creator page exposes the avatar url', function () {
    Storage::fake();

    $user = User::factory()->create(['username' => 'maker']);
    $path = UploadedFile::fake()->image('avatar.jpg', 300, 300)->store('avatars');
    $user->forceFill(['avatar_path' => $path])->save();

    $this->get(route('creators.show', $user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Creators/Show')
            ->where('creator.username', 'maker')
            ->where('creator.avatar_path', $path)
            ->whereNot('creator.avatar_url', null));
});
