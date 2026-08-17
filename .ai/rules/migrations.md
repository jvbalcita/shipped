---
paths:
  - 'database/migrations/**'
---

# Migrations

## Keep MySQL migration retries safe after partial DDL
MySQL may commit earlier schema statements before a later migration statement fails. For pending migrations that alter foreign-keyed indexes, drop the foreign key in a separate statement before its supporting index, and guard each already-applied schema step so a known partial Laravel Cloud state can resume safely.
