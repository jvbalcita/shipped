# Shipped SEO and Share Card plan

Date: 2026-08-24

Author: Codex

Source: Approved planning session and ADR 0011

Status: Implementation complete; compatibility rollout pending

Branch: jvbalcita/feat/shipped-seo-share-cards

## Goal

Make Shipped's strongest public records easy to discover, understand, and share without creating a thin programmatic SEO surface.

The first release should give Home, Discover, eligible Creator pages, discoverable Projects, and published Releases:

- A stable title and description
- A canonical URL
- Explicit indexation behavior
- Accurate Open Graph and Twitter metadata
- Appropriate structured data
- Inclusion in a dynamic sitemap when the record is currently public
- Useful internal links between related public records

The first Share Card release should preserve the existing Creator and 1200 by 630 project cards as documented, deterministic systems and add the approved Site and Release variants.

## Success definition

Success is not the number of generated URLs. The first release is successful when:

1. A crawler can start at Home and reach the canonical public record graph through HTML links.
2. Every indexable page has one canonical URL and a metadata description grounded in visible content.
3. Filtered Discover URLs remain useful to people but do not create accidental indexable duplicates.
4. The sitemap contains only currently eligible public URLs.
5. A Share Card never exposes private, stale, or non-discoverable project data.
6. The card remains visually legible with long names, missing optional fields, and no cover image.
7. The system can be tested without live model calls, external analytics, or manual per-project image editing.

## Current state evidence

The repository currently has:

- Public routes for Home, Discover, Creator, Project, Release, project Share Card, covers, manifests, and badges.
- Creator Shipping Profiles and approved Ship Stories are now part of the upstream public-record contract.
- Project Share Card rendering through a self-contained SVG Blade view at 1200 by 630.
- Creator Share Card rendering already exists and is being preserved behind the public profile contract.
- Server-provided ogTitle, ogDescription, and ogImage props in the main Blade shell.
- Twitter summary_large_image output and SVG image dimensions in the Blade shell.
- A public robots.txt with an open crawl rule but no sitemap declaration.
- No dynamic sitemap endpoint.
- No consistent canonical URL, og:url, og:image:alt, or page-specific robots contract.
- No deliberate JSON-LD policy across public pages.
- Discover filters that are useful for browsing but should not automatically become search landing pages.

The implementation must preserve the existing public route shapes and the established industrial/editorial visual language.

## Product and search positioning

Recommended public positioning:

The verified launch registry for Laravel projects.

This phrase is a positioning anchor, not a requirement to repeat the same keywords on every page. Page copy should describe the actual record:

- Home explains what Shipped verifies and why a public launch record is useful.
- Discover explains how visitors browse real Laravel projects.
- Creator pages describe the builder and link to their public projects.
- Project pages lead with the project's own name, tagline, description, category, verification state, and releases.
- Release pages explain what changed and link back to the project.

Avoid generic copy such as “best apps,” “top startups,” or “all software tools” unless the product actually supports those claims.

## Public page matrix

| Surface | Index when | Title direction | Description direction | Canonical | Share Card |
| --- | --- | --- | --- | --- | --- |
| Home | Always | Shipped — The verified launch registry for Laravel projects | What Shipped is, who it is for, and the role of Laravel Cloud verification | Home | Site variant |
| Discover | Base route | Discover verified Laravel projects — Shipped | How visitors browse public launches and filter them for product use | Base Discover | Site or Discover variant |
| Discover with query/filter/sort/page | Never in the initial release | Normal page title may reflect the browsing state | Useful UI copy is allowed | Base Discover | Inherit site/project context only |
| Creator | Creator has at least one discoverable Project | Creator name and username — Shipped | Who the creator is and the public work they have shipped | Creator route | Creator variant, only when public work exists |
| Creator with no public work | Reachable if product needs it; otherwise not a search destination | Normal product title | Honest empty-state copy | Creator route | None |
| Project | Discoverable Project | Project name by creator — Shipped | Project tagline plus a concise verified-launch context | Project route | Project variant |
| Published Release | Release is published and parent Project is discoverable | Release title — Project name — Shipped | Release summary and parent project context | Release route | Release variant |
| Private, stale, failed, or unverified Project | Not indexable | No public metadata contract | Return the existing privacy/404 behavior | None | None |

The exact punctuation can follow the existing title helper once implemented; the contract is the page identity and order, not keyword stuffing.

## Metadata contract

Create one small, testable metadata contract at the server boundary. It may be a value object or a focused builder if the existing flat Inertia props would otherwise be duplicated. Do not introduce a broad SEO framework.

Every public HTML response should be able to provide:

- title
- meta description
- canonical URL
- robots directive
- Open Graph type
- Open Graph URL
- Open Graph title
- Open Graph description
- Open Graph image URL
- Open Graph image MIME type
- Open Graph image width and height
- Open Graph image alt text
- Twitter card type
- Twitter title
- Twitter description
- Twitter image

Rules:

- Titles are unique to the visible record and remain readable when truncated by a browser.
- Descriptions use visible, record-backed copy and have a bounded fallback when optional content is missing.
- Canonical and og:url use the same named-route URL.
- The image alt text describes the card's subject, not decorative implementation details.
- Open Graph type is website unless a more specific type is supported by accurate visible data.
- No page emits an image URL for a record that is not currently eligible for public display.
- Metadata is available to the initial HTML response; it must not depend on a client-only navigation pass.

## Indexation and canonical rules

Implement a single visibility predicate or equivalent shared query rule for the public graph. It should be reused by page controllers, the sitemap, structured data, and Share Card authorization.

The predicate must account for:

- Discoverability
- Current verification state
- Published Release requirement
- Release publication state
- Parent Project visibility
- Creator public-work requirement where applicable

Indexation behavior:

- Home and base Discover: index,follow.
- Eligible Creator, Project, and Release pages: index,follow.
- Discover query, filter, sort, and pagination URLs: noindex,follow.
- Empty or non-useful Creator pages: noindex,follow or the existing public route's non-indexable state.
- Private or ineligible records: do not leak metadata; preserve the route's existing 404/private behavior.

Do not rely on robots.txt to hide pages that have already been linked. Use noindex for reachable but non-indexable browsing states.

## Sitemap and robots

Add a dynamic sitemap endpoint at /sitemap.xml with named-route URL generation.

The sitemap should:

- Include Home and base Discover.
- Include only eligible Creator pages.
- Include discoverable Projects.
- Include published Releases with discoverable parents.
- Use absolute URLs from the configured application URL.
- Include lastmod only when it represents a reliable public-record update.
- Omit changefreq and priority unless there is a demonstrated product reason to maintain them.
- Be deterministic for the same database state.
- Be cacheable for a short period without serving private records after a visibility transition.

Update robots.txt to advertise the absolute sitemap URL. Keep the crawl rule simple. Do not add broad disallows for query strings as a substitute for page-level noindex.

Add feature coverage for:

- Eligible records appearing.
- Private, stale, failed, or incomplete records being absent.
- A Release being absent when its parent Project is not discoverable.
- Stable XML content type and valid XML.
- Absolute canonical loc values.

## Structured data

Add JSON-LD from server-rendered partials or a small page-specific presenter. Keep it aligned with visible content.

Initial contract:

- Home: Organization and WebSite identity only for fields Shipped actually knows.
- Creator: ProfilePage after the current Creator page includes the profile fields and public work that the structured data describes.
- Creator, Project, and Release: BreadcrumbList when the breadcrumb is visible.
- Project: SoftwareApplication only when the application name, URL, description, category, and any pricing data are true and visible.

Do not emit:

- Made-up aggregate ratings.
- Reviews that are not visibly shown on the page.
- Invented authorship or organization relationships.
- Unsupported verification schema claims.
- Empty arrays or placeholder values solely to make the JSON-LD look complete.

Use the canonical public URL as the stable @id. Escape JSON safely and test malformed user text, Unicode, quotes, and HTML-like descriptions.

## Internal linking

The public graph should be understandable without JavaScript:

- Home links to Discover and explains the verified registry.
- Discover cards link to the canonical Project route and show the Creator link when available.
- Creator pages link to each discoverable Project.
- Project pages link to the Creator, all eligible Releases, and the external live application where the existing UX allows it.
- Release pages link back to the Project and Creator.
- Breadcrumbs match the visible links and route hierarchy.

Do not add a large directory footer of every tag, pricing value, or filter combination. Link only to destinations that help a visitor make the next relevant decision.

## Programmatic SEO gates

Initial scope:

- Do not create tag pages.
- Do not create pricing pages.
- Do not create location pages.
- Do not create persona pages.
- Do not create comparison pages.
- Do not index arbitrary query strings.

Category hub gate:

- At least 8 discoverable Projects in the category.
- At least 3 represented Creators.
- A unique introductory section written for the category.
- At least three meaningful internal links beyond the repeated result cards.
- A distinct title, description, canonical route, and breadcrumb.
- A feature test proving below-threshold categories stay absent or noindex.

The category hub is a later decision, not an automatic consequence of storing a Category field. If the gate is met, add one category template and measure it before adding more collection dimensions.

## Share Card design system

### Visual direction

Retain Shipped's paper-and-ink, Swiss/editorial launch-record language:

- Canvas: 1200 by 630
- Paper: #f4f4f0
- Ink: #050505
- Accent: #e61919
- Strong structural rules and serial/editorial labels
- No gradients
- No decorative stock imagery
- No dependency on remotely loaded fonts
- Generous safe margins of approximately 80 to 92 pixels

The card should be recognizable at thumbnail size: one dominant subject, one short supporting line, and a stable Shipped mark. Decorative details must never compete with the project or release title.

### Variants

Site variant:

- Small label identifying the registry
- Dominant Shipped name
- One-line positioning statement
- Minimal editorial rule system

Project variant:

- Category or record label
- Dominant project name
- Bounded tagline
- Creator username
- Verification-aware public context
- Stable Shipped mark and optional serial/date treatment

Release variant:

- Release label
- Dominant release title
- Parent project name
- Publication context or short release summary
- Creator username
- Stable Shipped mark

Creator variant:

- Preserve the existing Shipping Profile card now that the public profile contract is approved.
- Reference public profile fields and public-work counts only.
- Keep the page metadata image conditional on at least one discoverable Project.

### Content constraints

The renderer must handle:

- Long project and release names through deterministic wrapping or bounded truncation.
- Missing tagline, category, creator avatar, cover, or optional metadata.
- Unicode, punctuation, quotes, and line breaks.
- Very short names without awkward empty space.
- Text that would collide with rules, logos, or the safe margin.
- A missing or failed optional image without a broken-image icon.

Prefer typographic fallback over a cropped image when the image is absent or unsafe to crop. If a cover is used, apply a stable crop and a contrast treatment that preserves text legibility.

### Delivery contract

- Keep the existing SVG Blade template as the design source.
- Use explicit variant inputs rather than branching on arbitrary request parameters.
- Return the accurate image content type and dimensions.
- Use a stable route with a cache key/version that changes when public record data changes.
- Add a short cache lifetime and invalidate or version the card when the underlying public record changes.
- Never render private fields, draft release notes, or stale verification details.
- Do not add PNG/JPEG dependencies until a compatibility probe shows SVG delivery is insufficient.

Compatibility probe:

1. Render representative cards locally for Site, Project, and Release variants.
2. Inspect SVG dimensions, text wrapping, escaped content, and response headers.
3. Validate the card with at least one representative social crawler/debugger and one local rasterized preview.
4. Add raster output only if SVG is rejected, not previewed, or rendered incorrectly.
5. If raster output is needed, keep the same template fixtures and compare SVG and raster snapshots for the same data.

## Implementation file map

Likely files for the implementation session:

- app/Http/Controllers/WelcomeController.php
- app/Http/Controllers/DiscoverController.php
- app/Http/Controllers/CreatorController.php
- app/Http/Controllers/ProjectController.php
- app/Http/Controllers/ReleaseController.php
- app/Http/Controllers/OgController.php
- routes/web.php
- public/robots.txt or the existing robots response path
- resources/views/app.blade.php
- resources/views/og/project.blade.php
- resources/views/og/creator.blade.php
- resources/views/og/site.blade.php
- resources/views/og/release.blade.php
- resources/js/components/shipped/Breadcrumbs.vue
- resources/views/seo/json-ld.blade.php or focused page partials
- resources/views/sitemap.xml.blade.php, if a view-backed XML response fits the repository convention
- app/Http/Controllers/SitemapController.php
- app/Services/Seo/SeoMetadata.php or the smallest equivalent value object/presenter if duplication warrants it
- tests/Feature/SeoMetadataTest.php
- tests/Feature/SitemapTest.php
- tests/Feature/OgImageTest.php
- tests/Feature/ShippedMvpTest.php, only where existing public-route assertions are the right seam

Before creating a new service or presenter, inspect existing response-shaping conventions. A small shared contract is useful; a large SEO abstraction is not.

## Task breakdown

### 1. Lock the server-side SEO contract — complete

- Map each public controller to the page matrix.
- Centralize title, description, canonical, robots, and social image props enough to prevent drift.
- Preserve initial-HTML metadata for crawlers and link previews.
- Add tests for every page type and for missing optional data.

Done when:

- All eligible public pages emit complete metadata.
- Filtered Discover pages emit noindex and the base canonical.
- Ineligible records cannot receive public metadata.

### 2. Implement canonical and indexation behavior — complete

- Use named routes for canonical URLs.
- Add og:url and og:image:alt.
- Make the robots directive explicit per page.
- Confirm the public record visibility rule is shared rather than duplicated in each controller.

Done when:

- Canonical URLs match the actual public route.
- No private, stale, or incomplete Project or Release emits an indexable response.

### 3. Add sitemap and robots integration — complete

- Add /sitemap.xml.
- Include only eligible canonical public routes.
- Add the absolute sitemap URL to robots.txt.
- Add XML response and eligibility tests.

Done when:

- The sitemap is valid XML, cacheable, deterministic, and free of ineligible records.

### 4. Add conservative structured data — complete

- Add Home identity data.
- Add breadcrumbs only where visible.
- Add Creator ProfilePage only when the visible Creator page supports it.
- Add SoftwareApplication only for accurate Project fields.
- Add fixture coverage for escaped and Unicode values.

Done when:

- JSON-LD describes visible content and remains valid for adversarial user text.

### 5. Formalize Share Card variants — complete

- Extract bounded, explicit card data from public record presenters or equivalent.
- Preserve the current project visual system.
- Add Site and Release variants.
- Preserve the existing Creator variant and make its metadata eligibility explicit.
- Add visibility and cache-header tests.

Done when:

- Same input produces stable output.
- Long, missing, and Unicode content remains legible and escaped.
- Ineligible records do not render shareable cards.

### 6. Run visual and compatibility QA — local implementation complete; external QA pending

- Render fixture cards for short, long, missing, Unicode, and image-present data.
- Inspect at full size and thumbnail size.
- Verify SVG headers, dimensions, and crawler behavior.
- Decide on raster fallback from evidence.

Done when:

- The card is legible in the tested preview consumers.
- A raster fallback is either unnecessary and documented or implemented from the same source template.

### 7. Improve public copy and linking — complete

- Add short, truthful explanatory copy to Home and Discover.
- Link Creator, Project, and Release pages through the public graph.
- Keep filter controls useful without making every combination an SEO landing page.

Done when:

- A non-JavaScript crawler can traverse the intended public graph.
- Copy differentiates Shipped from a generic app directory.

### 8. Roll out and measure — pending deployment

- Deploy the metadata and sitemap changes with the existing release process.
- Submit the sitemap in Search Console.
- Watch index coverage, valid/invalid structured data, impressions, clicks, and click-through rate.
- Review the first cohort before opening category hubs or other programmatic collections.

Decision gate:

- Continue if eligible records are indexed and queries show relevant launch intent.
- Narrow if pages are indexed but queries are generic or duplicate-heavy.
- Pause expansion if visibility rules, verification freshness, or page quality are not trusted.

## Verification plan

The implementation session should run the repository's focused checks, not claim success from this planning document:

    vendor/bin/sail artisan test --compact tests/Feature/SeoMetadataTest.php tests/Feature/SitemapTest.php tests/Feature/ShippedMvpTest.php
    vendor/bin/sail bin pint --dirty --format agent
    npm run types:check
    npm run build

Also perform:

- XML validation of the generated sitemap with xmllint when available.
- HTTP header checks for canonical pages, noindex filtered pages, sitemap, robots, and Share Card routes.
- Local visual inspection of generated card fixtures.
- A representative social crawler/debugger check after deployment.
- A final diff review that separates task-related changes from existing user work.

If the repository uses a different existing static-analysis or type-check command, follow the package scripts and report the exact command actually run.

## Acceptance criteria

- [x] Home has truthful title, description, canonical, robots, Open Graph, Twitter, and home Share Card metadata.
- [x] Base Discover is indexable and linked from Home.
- [x] Discover query, filter, sort, and pagination variants are noindex,follow and excluded from the sitemap.
- [x] Eligible Creator pages have stable metadata and are indexable only when they contain public work.
- [x] Discoverable Projects have unique record-backed metadata and a deterministic Share Card.
- [x] Published Releases under discoverable Projects have canonical metadata, breadcrumbs, sitemap entries, and Release Share Cards.
- [x] Private, stale, failed, or incomplete records cannot leak indexable metadata or Share Cards.
- [x] Sitemap output is valid XML, uses absolute canonical URLs, and contains only eligible routes.
- [x] robots.txt advertises the sitemap.
- [x] JSON-LD is valid, escaped, visible-content-backed, and conservative.
- [x] Share Cards remain 1200 by 630, use the established Shipped visual language, and handle bounded content safely.
- [ ] SVG delivery is compatibility-tested before adding a raster dependency.
- [x] Focused automated tests and the repository's relevant PHP formatting/static checks pass; unrelated frontend failures are reported separately.

## Out of scope

- Generated tag, pricing, location, persona, comparison, or arbitrary filter pages.
- A blog, glossary, or editorial content engine.
- AI-generated SEO copy or Share Card copy.
- A user-editable Share Card designer.
- An analytics platform or new tracking SDK.
- Team, organization, or agency profile SEO.
- Automatic republishing based on verification or sitemap generation.
- A PNG/JPEG rendering dependency before compatibility evidence requires it.
- A redesign of the existing Project page or Share Card visual language.

## Self-review

- The plan names the public surfaces, visibility boundary, data source, files, tests, visual QA, rollout gate, and deferrals.
- The plan treats pSEO as a quality and supply gate, not a URL-generation exercise.
- The plan now preserves the upstream Creator Shipping Profile and Creator Share Card seams while keeping indexation conditional on public work.
- Local implementation is complete on `jvbalcita/feat/shipped-seo-share-cards`; deployment, Search Console submission, and representative social-crawler validation remain rollout work.
