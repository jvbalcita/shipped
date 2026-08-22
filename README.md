# Shipped

[![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP 8.4.1+](https://img.shields.io/badge/PHP-8.4.1%2B-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![Inertia v3](https://img.shields.io/badge/Inertia-v3-9553E9?logo=inertia&logoColor=white)](https://inertiajs.com)
[![Vue 3](https://img.shields.io/badge/Vue-3-4FC08D?logo=vuedotjs&logoColor=white)](https://vuejs.org)
[![Tailwind CSS 4](https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Tests](https://github.com/jvbalcita/shipped/actions/workflows/tests.yml/badge.svg)](https://github.com/jvbalcita/shipped/actions/workflows/tests.yml)

**Shipped** is a public registry for independent Laravel launches. Creators record a project, write its release story, verify its Laravel Cloud deployment, and publish a shareable page the community can discover and cheer.

## What it does

- Creates private launch records with real evidence: cover image, screenshots with captions, and a live or source URL are required before a record exists.
- Offers sign-in and account linking through GitHub alongside email, passkeys, and two-factor authentication. Google appears only when `GOOGLE_CLIENT_ID` is configured.
- Lets GitHub-linked creators pick their repository from a searchable dropdown of their public repos while composing a launch.
- Publishes release notes immediately or on a schedule.
- Verifies a live URL by requiring a reachable Laravel Cloud URL whose normalized project name matches the live hostname before public listing.
- Provides a searchable public registry, creator profiles, and launch pages with a screenshot gallery and fullscreen preview.
- Builds community loops: polymorphic cheers, reviews, comments, follows, and a private activity feed.
- Ships a live **verification badge** (above) that creators drop into their READMEs.
- Uses a responsive Swiss industrial print interface across the public site, Studio, Composer, and auth flows.

## Stack

| Layer         | Choice                                                                       |
| ------------- | ---------------------------------------------------------------------------- |
| Backend       | Laravel 13, PHP 8.4.1+                                                       |
| Frontend      | Inertia v3, Vue 3, TypeScript, Tailwind CSS 4                                |
| UI primitives | shadcn-vue / Reka UI                                                         |
| Auth          | Laravel Fortify, passkeys, two-factor, GitHub OAuth via Socialite (Google when credentials exist) |
| Storage       | Laravel Filesystem with local public storage or S3-compatible Object Storage |
| Tests         | Pest 4, PHPStan, Pint, ESLint, Prettier                                      |

## Requirements

- PHP 8.4.1 or newer
- Composer 2
- Node.js 22 or newer
- SQLite for the fastest local start, or PostgreSQL/MySQL

## Local development

```bash
git clone https://github.com/jvbalcita/shipped.git
cd shipped

composer install
npm install

cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

Start the Laravel and Vite development processes:

```bash
composer run dev
```

Open the URL reported by Laravel. The seeded demo launches make the public registry immediately explorable.

> Running under Laravel Sail instead? Use `./vendor/bin/sail npm run dev` — the
> Sail container owns the Vite port, so a host-side `npm run dev` will conflict.

## Project cover storage

Shipped deliberately uses Laravel's **default filesystem disk** for every project cover. There is no application-specific disk setting to keep in sync.

### Local development

The supplied `.env.example` sets:

```dotenv
FILESYSTEM_DISK=public
```

Run `php artisan storage:link` once. Covers are stored in `storage/app/public/project-covers` and served from `/storage`.

### Laravel Cloud Object Storage

Installations include `league/flysystem-aws-s3-v3`, so use Laravel Cloud Object Storage—or another S3-compatible provider—by setting the standard Laravel filesystem variables in the production environment:

```dotenv
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=***
AWS_DEFAULT_REGION=...
AWS_BUCKET=...
AWS_ENDPOINT=...
AWS_URL=...
AWS_USE_PATH_STYLE_ENDPOINT=false
```

`AWS_ENDPOINT` is the S3-compatible API endpoint. Set `AWS_URL` to the browser-accessible Object Storage or CDN base URL for public cover images. Do not commit these values. Laravel Cloud environment variables are the appropriate place for production secrets.

## Laravel Cloud deployment

1. Create a Laravel Cloud application from this repository and attach a database.
2. Add the production `APP_*`, `DB_*`, mail, queue, cache, and Object Storage variables in Laravel Cloud.
3. Set `FILESYSTEM_DISK=s3` and the standard `AWS_*` values shown above if covers should use Object Storage.
4. To enable GitHub sign-in and the repository picker, set `GITHUB_CLIENT_ID` / `GITHUB_CLIENT_SECRET` / `GITHUB_REDIRECT_URI`. Google appears on `/login` only when the `GOOGLE_*` equivalents are also set. Providers without credentials are hidden automatically.
5. Build frontend assets during deployment with `npm ci && npm run build`.
6. Run `php artisan migrate --force` as a deploy command.
7. Seed the curated categories once — the launch composer's category dropdown is empty until this runs:
   ```bash
   php artisan db:seed --class=CategorySeeder --force
   ```
   A bare `db:seed --force` is equally safe: `DatabaseSeeder` only runs production-safe seeders, and demo content is limited to local/testing environments.
8. When upgrading an installation with legacy token-backed verification, preview and apply the URL evidence backfill before enabling the new scheduled recheck:
   ```bash
   php artisan shipped:backfill-cloud-verification-urls --dry-run
   php artisan shipped:backfill-cloud-verification-urls --apply --verify
   ```
   Review the manual-required and failed project IDs. Those projects remain private until a creator submits a matching Laravel Cloud URL; `--apply` never preserves old public verification by itself.
9. Run a queue worker and scheduler if you enable scheduled release processing in your environment.

Legacy Laravel Cloud API tokens are encrypted at rest, never displayed after submission, and remain available only for read-only environment backfill. New URL verification does not request an API token. The URL probe rejects redirects, non-public DNS destinations, and oversized fallback bodies, and it is rate-limited per creator/project.

## The verification badge

Every discoverable project gets a self-hosted SVG badge (`GET /badges/{slug}.svg`)
that reflects live verification status — LIVE ON CLOUD, STALE, VERIFICATION
FAILED, or UNVERIFIED. Grab the copy-ready markdown from Creator Studio. The
markdown is built from `APP_URL`, so it points at your real domain in
production (locally it uses `shipped.test:8087`, which only resolves on this
machine). The live-on-Cloud badge means the Cloud URL was reachable; it does
not prove Laravel Cloud account ownership.

## Quality checks

```bash
# PHP formatting and static analysis
composer lint:check
composer types:check

# Frontend formatting, linting, and TypeScript
npm run format:check
npm run lint:check
npm run types:check
npm run build

# Application tests
php artisan test --compact
```

## Useful routes

| Route                   | Purpose                           |
| ----------------------- | --------------------------------- |
| `/`                     | Product introduction              |
| `/discover`             | Searchable public launch registry |
| `/dashboard`            | Authenticated Creator Studio      |
| `/projects/create`      | Guided launch composer            |
| `/@{creator}/{project}` | Public project launch page        |
| `/@{creator}`           | Creator profile                   |
| `/feed`                 | Private activity feed             |
| `/badges/{slug}.svg`    | Verification badge                |

## Docs

- [Vision & roadmap](docs/adr/0001-verify-projects-through-laravel-cloud.md) — product direction
- [Implementation plans](docs/superpowers/plans/) — follow/feed, badge, manifest, notifications
- [ADRs](docs/adr/) — architecture decision records

## Contributing

1. Create a branch from `main`.
2. Keep a change focused; run the relevant checks above.
3. Use Conventional Commit messages.
4. Include screenshots for visual changes and note any environment variables or deployment changes in the pull request.

## Security

Never commit `.env` files, API tokens, Object Storage credentials, or private project data. Report vulnerabilities privately to the repository owner rather than opening a public issue.
