---
paths:
  - 'app/Models/*.php'
---

# Models

## Use the default storage disk (FILESYSTEM_DISK), never hardcode 'public'
For all user-facing uploads (avatars, logos, screenshots, covers), store AND build URLs through the default disk: `Storage::disk()` (or bare `Storage::`). Never hardcode `Storage::disk('public')`. Relying on `FILESYSTEM_DISK` keeps local on `public` and lets production swap to S3 with zero code changes. Accessors like `getUrlAttribute()` should use `Storage::disk()->url($this->path)` to match `Project::logoUrl()`/`coverImageUrl()`.
