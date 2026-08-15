# 13 — Reviews

**What to build:** An authenticated creator can leave one Review per project: a required 1–5 integer rating plus an optional plain-text body (max ~1000 chars). The author can edit or delete their review anytime; deleting removes its rating from the aggregate, editing re-aggregates live. The project's average rating renders on the Show page and Discover cards.

**Blocked by:** 08 — Project pricing + logo + launch_date

**Status:** ready-for-agent

- [ ] `reviews` table: `id`, `project_id` FK cascade, `user_id` FK cascade, `rating` tinyint 1–5 required, `body` text nullable, timestamps; UNIQUE `(project_id, user_id)`
- [ ] Review model; Project `hasMany` reviews; average rating accessor/aggregate
- [ ] Resource-style ProjectReviewController (store/update/destroy) with Form Requests and ReviewPolicy (author-only update/delete)
- [ ] Show page exposes a review form (one per creator) and a reviews list with average
- [ ] Discover cards show the aggregate average when reviews exist
- [ ] Feature tests cover one-per-creator constraint, rating validation, author-only edit/delete, aggregate update on edit/delete, and authorization failure
