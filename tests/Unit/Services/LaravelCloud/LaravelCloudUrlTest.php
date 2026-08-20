<?php

use App\Rules\LaravelCloudUrlRule;
use App\Services\LaravelCloud\LaravelCloudUrl;

test('normalizes host casing, trailing dots, and a root path into one canonical origin', function (string $input) {
    $url = LaravelCloudUrl::from($input);

    expect($url->url())->toBe('https://my-app.laravel.cloud')
        ->and($url->host())->toBe('my-app.laravel.cloud');
})->with([
    'exact origin' => 'https://my-app.laravel.cloud',
    'trailing slash' => 'https://my-app.laravel.cloud/',
    'uppercase scheme and host' => 'HTTPS://My-App.Laravel.Cloud',
    'trailing host dot' => 'https://my-app.laravel.cloud.',
    'trailing dot with slash' => 'https://my-app.laravel.cloud./',
]);

test('rejects everything outside the exact HTTPS single-label Cloud origin', function (string $input) {
    expect(LaravelCloudUrl::tryFrom($input))->toBeNull();
})->with([
    'plain HTTP' => 'http://my-app.laravel.cloud',
    'bare Cloud apex' => 'https://laravel.cloud',
    'multi-level subdomain' => 'https://foo.bar.laravel.cloud',
    'port' => 'https://my-app.laravel.cloud:8443',
    'user info' => 'https://user@my-app.laravel.cloud',
    'credentials' => 'https://user:pass@my-app.laravel.cloud',
    'non-root path' => 'https://my-app.laravel.cloud/health',
    'query string' => 'https://my-app.laravel.cloud?token=1',
    'fragment' => 'https://my-app.laravel.cloud#top',
    'lookalike suffix host' => 'https://my-app.laravel.cloud.evil.test',
    'adjacent suffix host' => 'https://my-app.laravel-cloud.test',
    'unrelated host' => 'https://evil.test',
    'cyrillic label' => 'https://аpp.laravel.cloud',
    'cyrillic brand' => 'https://my-app.larаvel.cloud',
    'leading whitespace' => ' https://my-app.laravel.cloud',
    'embedded whitespace' => "https://my-app.\tlaravel.cloud",
    'trailing newline' => "https://my-app.laravel.cloud\n",
    'empty string' => '',
    'missing scheme' => '//my-app.laravel.cloud',
    'double trailing dot' => 'https://my-app.laravel.cloud..',
    'leading hyphen label' => 'https://-my-app.laravel.cloud',
    'trailing hyphen label' => 'https://my-app-.laravel.cloud',
    'label longer than 63 characters' => 'https://'.str_repeat('a', 64).'.laravel.cloud',
    'input longer than 255 characters' => 'https://'.str_repeat('a', 240).'.laravel.cloud',
]);

test('from throws for rejected values while tryFrom stays null', function () {
    expect(fn () => LaravelCloudUrl::from('https://evil.test'))
        ->toThrow(InvalidArgumentException::class);
});

test('the validation rule reports one stable message for any rejected value', function (mixed $value) {
    $rule = new LaravelCloudUrlRule;
    $failed = false;

    $rule->validate('laravel_cloud_url', $value, function (string $message) use (&$failed, &$captured): void {
        $failed = true;
        $captured = $message;
    });

    expect($failed)->toBeTrue()
        ->and($captured)->toBe('Enter the HTTPS `*.laravel.cloud` URL assigned to this environment.');
})->with([
    'non-string' => 123,
    'invalid URL' => 'https://evil.test',
    'schemeless' => 'my-app.laravel.cloud',
]);

test('the validation rule accepts a canonical Cloud URL', function () {
    $rule = new LaravelCloudUrlRule;
    $failed = false;

    $rule->validate('laravel_cloud_url', 'https://my-app.laravel.cloud', function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});
