<?php

namespace App\Services;

use App\Models\Project;

/**
 * Composes every shareable asset of a project's Launch Kit: the README badge
 * snippet, the share text, and the URLs of the generated SVG assets. Assets
 * gated on public discoverability (badge, launch card, manifest) are null
 * until the project is filed.
 */
final class LaunchKitAssets
{
    /**
     * @return array{is_discoverable: bool, badge_markdown: string|null, share_text: string, canonical_url: string, card_url: string|null, cover_url: string, manifest_url: string|null}
     */
    public function props(Project $project): array
    {
        $discoverable = $project->isPubliclyDiscoverable();
        $canonicalUrl = route('projects.show', [$project->creator, $project]);

        return [
            'is_discoverable' => $discoverable,
            'badge_markdown' => $this->badgeMarkdown($project),
            'share_text' => trim($project->name.' — '.$project->tagline).' '.$canonicalUrl,
            'canonical_url' => $canonicalUrl,
            'card_url' => $discoverable
                ? route('og.project', [$project->creator, $project])
                : null,
            // The cover plate serves to the owner even before filing.
            'cover_url' => route('cover.project', [$project->creator, $project]),
            'manifest_url' => $discoverable
                ? route('manifests.show', [$project->creator, $project])
                : null,
        ];
    }

    public function badgeMarkdown(Project $project): ?string
    {
        if (! $project->isPubliclyDiscoverable()) {
            return null;
        }

        return sprintf(
            '[![Shipped](%s)](%s)',
            route('badges.show', $project),
            route('projects.show', [$project->creator, $project]),
        );
    }
}
