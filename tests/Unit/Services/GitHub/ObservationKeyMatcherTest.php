<?php

use App\Services\GitHub\ObservationKeyMatcher;

test('literal keys match declared dependency names', function () {
    expect(ObservationKeyMatcher::matches(
        ['vue', 'tailwindcss'],
        ['vue' => '^3.5.0', 'laravel/framework' => '^12.0'],
    ))->toBeTrue();
});

test('literal keys stay unmatched when the dependency is absent', function () {
    expect(ObservationKeyMatcher::matches(
        ['react'],
        ['vue' => '^3.5.0'],
    ))->toBeFalse();
});

test('constraint keys compare the declared floor against the key constraint', function () {
    expect(ObservationKeyMatcher::matches(
        ['laravel/framework:^12.0'],
        ['laravel/framework' => '^12.16'],
    ))->toBeTrue();
});

test('constraint keys reject a different major', function () {
    expect(ObservationKeyMatcher::matches(
        ['laravel/framework:^12.0'],
        ['laravel/framework' => '^11.44'],
    ))->toBeFalse();
});

test('any-key accepts nothing when the declared constraint is a wildcard', function () {
    expect(ObservationKeyMatcher::matches(
        ['php:^8.3'],
        ['php' => '*'],
    ))->toBeFalse();
});

test('any observation key matching is enough', function () {
    expect(ObservationKeyMatcher::matches(
        ['@inertiajs/vue3', '@inertiajs/inertia-vue3', 'inertia'],
        ['@inertiajs/inertia-vue3' => '^0.6'],
    ))->toBeTrue();
});

test('empty keys never match', function () {
    expect(ObservationKeyMatcher::matches([], ['vue' => '^3']))->toBeFalse();
});
