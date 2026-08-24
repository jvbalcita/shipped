<?php

namespace App\Services\Seo;

use Illuminate\Support\Str;

final readonly class SeoMetadata
{
    /**
     * @param  list<array<string, mixed>>  $jsonLd
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $canonicalUrl,
        public string $robots = 'index,follow',
        public ?string $image = null,
        public ?string $imageAlt = null,
        public string $imageType = 'image/svg+xml',
        public int $imageWidth = 1200,
        public int $imageHeight = 630,
        public array $jsonLd = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'canonical' => $this->canonicalUrl,
            'robots' => $this->robots,
            'ogType' => 'website',
            'ogUrl' => $this->canonicalUrl,
            'ogTitle' => $this->title,
            'ogDescription' => $this->description,
            'image' => $this->image,
            'imageAlt' => $this->imageAlt,
            'imageType' => $this->imageType,
            'imageWidth' => $this->imageWidth,
            'imageHeight' => $this->imageHeight,
            'twitterCard' => $this->image === null ? 'summary' : 'summary_large_image',
            'twitterTitle' => $this->title,
            'twitterDescription' => $this->description,
            'twitterImage' => $this->image,
            'jsonLd' => $this->jsonLd,
        ];
    }

    /**
     * Preserve the existing top-level props while callers migrate to seo.
     *
     * @return array<string, string|null>
     */
    public function legacyProps(): array
    {
        return [
            'ogTitle' => $this->title,
            'ogDescription' => $this->description,
            'ogImage' => $this->image,
        ];
    }

    public static function summary(?string $value, string $fallback, int $limit = 160): string
    {
        $summary = Str::squish(strip_tags((string) $value));

        return Str::limit($summary !== '' ? $summary : $fallback, $limit, '…');
    }

    /**
     * @param  list<array{name: string, url: string}>  $items
     * @return array<string, mixed>
     */
    public static function breadcrumbList(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)
                ->values()
                ->map(fn (array $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'item' => $item['url'],
                ])
                ->all(),
        ];
    }
}
