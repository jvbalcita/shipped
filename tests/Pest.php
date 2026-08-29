<?php

use App\Models\Category;
use App\Models\User;
use App\Services\LaravelCloud\CloudHostResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change what classes and traits to use via the "pest()" function to bind different classes, traits.

*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        // Verification probes resolve DNS through this seam; feature tests
        // must stay deterministic and never contact a real resolver or URL.
        $this->app->bind(CloudHostResolver::class, fn () => new class implements CloudHostResolver
        {
            public function addresses(string $host): array
            {
                return ['93.184.216.34'];
            }
        });

        // Tests load the real .env, so a developer's SHIPPED_CURATORS would
        // silently make low-ID users curators (own-content 403s become
        // redirects). Tests that need a curator set config() explicitly.
        config()->set('shipped.curators', []);
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function verifiedUser(): User
{
    return User::factory()->create(['email_verified_at' => now()]);
}

/**
 * A payload satisfying every StoreProjectRequest requirement: cover
 * image, at least one screenshot, and a live URL.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validProjectPayload(Category $category, array $overrides = []): array
{
    return array_merge([
        'name' => 'Test Launch',
        'tagline' => 'A one-liner.',
        'description' => 'Description body.',
        'category_id' => $category->id,
        'live_url' => 'https://example.test',
        'cover_image' => UploadedFile::fake()->image('cover.png'),
        'screenshots' => [UploadedFile::fake()->image('shot.png')],
    ], $overrides);
}
