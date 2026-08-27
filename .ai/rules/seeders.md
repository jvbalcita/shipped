---
paths:
  - 'database/seeders/**'
---

# Seeders

## Run TechnologySeeder alongside CategorySeeder in production
Production seeding requires both curated seeders: `php artisan db:seed --class=CategorySeeder --force` and `php artisan db:seed --class=TechnologySeeder --force`. Both are idempotent (firstOrCreate on unique slug) and use WithoutModelEvents. TechnologySeeder owns the Built With stack vocabulary; additive deploys that ship new vocabulary entries must run it before the application code serves /built-with pages.
