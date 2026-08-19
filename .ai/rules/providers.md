---
paths:
  - 'database/seeders/**,app/Providers/**'
---

# Providers

## Production seeding pattern and the destructive-commands toggle
db:seed is NOT blocked by DB::prohibitDestructiveCommands (it only prohibits db:wipe and migrate:fresh/refresh/reset/rollback), and db:seed asks for confirmation in production unless --force. Seeders are split: CategorySeeder is idempotent (firstOrCreate on slug) and production-safe; DemoLaunchSeeder (studio@shipped.test user + demo launches) only runs when app()->environment(['local', 'testing']). SHIPPED_ALLOW_DESTRUCTIVE_COMMANDS (config shipped.allow_destructive_commands, default false) is an emergency-only escape hatch for the prohibited destructive commands — seeding never needs it. Production runbook: `php artisan db:seed --class=CategorySeeder --force`.
