<?php

use App\Services\LaravelCloud\CloudHostResolver;
use App\Services\LaravelCloud\CloudUrlProbeOutcome;
use App\Services\LaravelCloud\LaravelCloudUrl;
use App\Services\LaravelCloud\LaravelCloudUrlProbe;
use GuzzleHttp\Psr7\Stream;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

function publicResolver(array $addresses = ['93.184.216.34']): CloudHostResolver
{
    return new class($addresses) implements CloudHostResolver
    {
        public function __construct(private array $addresses) {}

        public function addresses(string $host): array
        {
            return $this->addresses;
        }
    };
}

test('a successful HEAD probe is reachable and sends only one hardened request', function () {
    Http::preventStrayRequests();
    Http::fake(['https://my-app-main.laravel.cloud' => Http::response('ok', 200)]);

    $result = (new LaravelCloudUrlProbe(publicResolver()))
        ->probe(LaravelCloudUrl::from('https://my-app-main.laravel.cloud'));

    expect($result->outcome)->toBe(CloudUrlProbeOutcome::Reachable)
        ->and($result->httpStatus)->toBe(200)
        ->and($result->failureCode)->toBeNull()
        ->and($result->durationMs)->toBeInt();

    Http::assertSentCount(1);
    Http::assertSent(function (Request $request): bool {
        return $request->method() === 'HEAD'
            && $request->url() === 'https://my-app-main.laravel.cloud'
            && $request->hasHeader('User-Agent', 'Shipped-Cloud-Verifier/1.0');
    });
});

test('a HEAD rejection falls back to a bounded GET only for 405 and 501', function (int $headStatus) {
    Http::preventStrayRequests();
    Http::fake(['https://my-app-main.laravel.cloud' => Http::sequence([
        Http::response('', $headStatus),
        Http::response('deployment is live', 200),
    ])]);

    $result = (new LaravelCloudUrlProbe(publicResolver()))
        ->probe(LaravelCloudUrl::from('https://my-app-main.laravel.cloud'));

    expect($result->outcome)->toBe(CloudUrlProbeOutcome::Reachable)
        ->and($result->httpStatus)->toBe(200);

    Http::assertSentCount(2);
    Http::assertSentInOrder([
        fn (Request $request) => $request->method() === 'HEAD',
        fn (Request $request) => $request->method() === 'GET',
    ]);
})->with([[405], [501]]);

test('an oversized fallback response is drained to the ceiling and closed', function () {
    Http::preventStrayRequests();

    $resource = fopen('php://temp', 'w+b');
    fwrite($resource, str_repeat('x', 128 * 1024));
    rewind($resource);

    $stream = new class($resource) extends Stream
    {
        public int $bytesRead = 0;

        public bool $wasClosed = false;

        public function read($length): string
        {
            $chunk = parent::read($length);
            $this->bytesRead += strlen($chunk);

            return $chunk;
        }

        public function close(): void
        {
            $this->wasClosed = true;

            parent::close();
        }
    };

    Http::fake(['https://my-app-main.laravel.cloud' => Http::sequence([
        Http::response('', 405),
        Http::response($stream, 200),
    ])]);

    $result = (new LaravelCloudUrlProbe(publicResolver()))
        ->probe(LaravelCloudUrl::from('https://my-app-main.laravel.cloud'));

    expect($result->outcome)->toBe(CloudUrlProbeOutcome::Reachable)
        ->and($stream->bytesRead)->toBeLessThanOrEqual(64 * 1024)
        ->and($stream->wasClosed)->toBeTrue();
});

test('redirects are never followed and count as definitive failures', function () {
    Http::preventStrayRequests();
    Http::fake(['https://my-app-main.laravel.cloud' => Http::response('', 301, ['Location' => 'https://example.com'])]);

    $result = (new LaravelCloudUrlProbe(publicResolver()))
        ->probe(LaravelCloudUrl::from('https://my-app-main.laravel.cloud'));

    expect($result->outcome)->toBe(CloudUrlProbeOutcome::DefinitiveFailure)
        ->and($result->httpStatus)->toBe(301)
        ->and($result->failureCode)->toBe('http_rejected');

    Http::assertSentCount(1);
});

test('definitive client responses fail the probe exactly once', function (int $status) {
    Http::preventStrayRequests();
    Http::fake(['https://my-app-main.laravel.cloud' => Http::response('', $status)]);

    $result = (new LaravelCloudUrlProbe(publicResolver()))
        ->probe(LaravelCloudUrl::from('https://my-app-main.laravel.cloud'));

    expect($result->outcome)->toBe(CloudUrlProbeOutcome::DefinitiveFailure)
        ->and($result->failureCode)->toBe('http_rejected');

    Http::assertSentCount(1);
})->with([[301], [400], [401], [403], [404], [410]]);

test('retryable statuses map to their failure codes without auto retrying', function (int $status, string $failureCode) {
    Http::preventStrayRequests();
    Http::fake(['https://my-app-main.laravel.cloud' => Http::response('', $status)]);

    $result = (new LaravelCloudUrlProbe(publicResolver()))
        ->probe(LaravelCloudUrl::from('https://my-app-main.laravel.cloud'));

    expect($result->outcome)->toBe(CloudUrlProbeOutcome::RetryableFailure)
        ->and($result->failureCode)->toBe($failureCode);

    Http::assertSentCount(1);
})->with([
    'request timeout' => [408, 'timeout'],
    'too early' => [425, 'timeout'],
    'rate limited' => [429, 'rate_limited'],
    'server error' => [500, 'server_error'],
    'unavailable' => [503, 'server_error'],
]);

test('network failures classify into retryable failure codes', function (string $curlMessage, string $failureCode) {
    Http::preventStrayRequests();
    Http::fake(['https://my-app-main.laravel.cloud' => Http::failedConnection($curlMessage)]);

    $result = (new LaravelCloudUrlProbe(publicResolver()))
        ->probe(LaravelCloudUrl::from('https://my-app-main.laravel.cloud'));

    expect($result->outcome)->toBe(CloudUrlProbeOutcome::RetryableFailure)
        ->and($result->failureCode)->toBe($failureCode);
})->with([
    'timeout' => ['cURL error 28: Operation timed out after 8001 milliseconds', 'timeout'],
    'dns' => ['cURL error 6: Could not resolve host: my-app-main.laravel.cloud', 'dns_unavailable'],
    'tls' => ['cURL error 60: SSL certificate problem: self signed certificate', 'tls_error'],
    'connection refused' => ['cURL error 7: Failed to connect to my-app-main.laravel.cloud port 443', 'connection_failed'],
]);

test('a host with no DNS answers is retryable and never touches the network', function () {
    Http::preventStrayRequests();

    $result = (new LaravelCloudUrlProbe(publicResolver([])))
        ->probe(LaravelCloudUrl::from('https://my-app-main.laravel.cloud'));

    expect($result->outcome)->toBe(CloudUrlProbeOutcome::RetryableFailure)
        ->and($result->failureCode)->toBe('dns_unavailable');

    Http::assertSentCount(0);
});

test('any non-public resolved address blocks the request before HTTP is sent', function (array $addresses) {
    Http::preventStrayRequests();

    $result = (new LaravelCloudUrlProbe(publicResolver($addresses)))
        ->probe(LaravelCloudUrl::from('https://my-app-main.laravel.cloud'));

    expect($result->outcome)->toBe(CloudUrlProbeOutcome::DefinitiveFailure)
        ->and($result->failureCode)->toBe('non_public_address');

    Http::assertSentCount(0);
})->with([
    'private IPv4 only' => [['10.0.0.5']],
    'loopback only' => [['127.0.0.1']],
    'link-local IPv6 only' => [['fe80::1']],
    'unique-local IPv6 only' => [['fc00::1']],
    'documentation TEST-NET-1' => [['192.0.2.10']],
    'documentation TEST-NET-2' => [['198.51.100.7']],
    'documentation TEST-NET-3' => [['203.0.113.254']],
    'documentation IPv6' => [['2001:db8:1f::1']],
    'IPv4 multicast' => [['224.0.0.1']],
    'RFC 6598 shared IPv4' => [['100.64.0.1']],
    'RFC 2544 benchmarking IPv4' => [['198.18.0.1']],
    'IPv4-mapped private IPv6' => [['::ffff:10.0.0.1']],
    'IPv4-mapped loopback IPv6' => [['::ffff:127.0.0.1']],
    'public answer poisoned by a private answer' => [['93.184.216.34', '192.168.1.5']],
    'public answer poisoned by a loopback IPv6 answer' => [['8.8.8.8', '::1']],
]);

test('a host resolving to only public addresses proceeds to the request', function () {
    Http::preventStrayRequests();
    Http::fake(['https://my-app-main.laravel.cloud' => Http::response('', 204)]);

    $result = (new LaravelCloudUrlProbe(publicResolver(['93.184.216.34', '2606:4700::1111'])))
        ->probe(LaravelCloudUrl::from('https://my-app-main.laravel.cloud'));

    expect($result->outcome)->toBe(CloudUrlProbeOutcome::Reachable)
        ->and($result->httpStatus)->toBe(204);
});
