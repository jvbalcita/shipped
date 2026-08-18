---
paths:
  - 'app/Models/*.php,app/Http/Controllers/**'
---

# Controllers

## User model is NOT unguarded — set email_verified_at explicitly
Despite .ai/guidelines/laravel.md claiming `Model::unguard()` globally, there is no unguard call anywhere. User limits mass assignment via the `#[Fillable([...])]` attribute, which excludes `email_verified_at`. Mass-assigning it via `create()`/`fill()` is silently dropped. Follow `SecurityController::updateEmail`: assign the property directly and `save()`.
