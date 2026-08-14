<?php

namespace App\Services;

/**
 * Narrows TipTap-produced project descriptions for safe public rendering.
 *
 * TipTap's curated extension set already limits output to a small tag allowlist
 * (b/strong, i/em, ul, ol, li, a, blockquote, p, br). This is defense-in-depth
 * for the vectors called out in ADR 0004: it strips <script> blocks, inline
 * event-handler attributes (on*), and javascript:/vbscript: URLs, so markup
 * injected outside the editor (e.g. posted directly to the API) cannot execute
 * when the Show page renders it via v-html. It is intentionally narrow and is
 * not a general-purpose HTML purifier.
 */
class HtmlSanitizer
{
    public static function sanitize(string $html): string
    {
        // Drop <script>...</script> blocks entirely.
        $html = preg_replace('#<script[\s\S]*?</script\s*>#i', '', $html) ?? $html;

        // Strip inline event-handler attributes (on*="...", on*='...', on*=value).
        $html = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html) ?? $html;

        // Neutralize javascript:/vbscript: URLs in href/src.
        $html = preg_replace(
            '#(href|src)\s*=\s*("(?:javascript|vbscript):[^"]*"|\'(?:javascript|vbscript):[^\']*\')#i',
            '$1="#"',
            $html,
        ) ?? $html;

        return trim($html);
    }
}
