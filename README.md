# Shipped

[![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![Inertia v3](https://img.shields.io/badge/Inertia-v3-9553E9?logo=inertia&logoColor=white)](https://inertiajs.com)
[![Vue 3](https://img.shields.io/badge/Vue-3-4FC08D?logo=vuedotjs&logoColor=white)](https://vuejs.org)
[![Tailwind CSS 4](https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Tests](https://github.com/jvbalcita/shipped/actions/workflows/tests.yml/badge.svg)](https://github.com/jvbalcita/shipped/actions/workflows/tests.yml)

**Shipped** is a public registry for independent Laravel launches. Creators record a project, write its release story, verify its Laravel Cloud deployment, and publish a shareable page the community can discover and cheer.

## What it does

- Creates private launch records with cover images, live URLs, source links, and categories.
- Publishes release notes immediately or on a schedule.
- Verifies a live URL against a connected Laravel Cloud environment before public listing.
- Provides a searchable public registry, creator profiles, launch pages, and one-cheer-per-member support.
- Uses a responsive Swiss industrial print interface across the public site, Studio, Composer, and auth flows.

## Stack

| Layer         | Choice                                                                       |
| ------------- | ---------------------------------------------------------------------------- |
| Backend       | Laravel 13, PHP 8.3+                                                         |
| Frontend      | Inertia v3, Vue 3, TypeScript, Tailwind CSS 4                                |
| UI primitives | shadcn-vue / Reka UI                                                         |
| Auth          | Laravel Fortify, passkeys, two-factor authentication                         |
| Storage       | Laravel Filesystem with local public storage or S3-compatible Object Storage |
| Tests         | Pest 4, PHPStan, Pint, ESLint, Prettier                                      |

## Requirements

- PHP 8.3 or newer
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
AWS_SECRET_ACCESS_KEY=...
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
4. Build frontend assets during deployment with `npm ci && npm run build`.
5. Run `php artisan migrate --force` as a deploy command.
6. Run a queue worker and scheduler if you enable scheduled release processing in your environment.

Laravel Cloud API tokens supplied by creators are encrypted at rest, never displayed after submission, and Shipped uses them only for read-only Cloud requests. Their actual scope remains controlled by Laravel Cloud.

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

## Contributing

1. Create a branch from `main`.
2. Keep a change focused; run the relevant checks above.
3. Use Conventional Commit messages.
4. Include screenshots for visual changes and note any environment variables or deployment changes in the pull request.

## Security

Never commit `.env` files, API tokens, Object Storage credentials, or private project data. Report vulnerabilities privately to the repository owner rather than opening a public issue.
