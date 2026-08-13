# 14 — Comments + polymorphic Cheers migration

**What to build:** An authenticated creator can leave a short plain-text Comment on a project and reply once (single-level — reply to a top-level comment only, no deeper nesting). Authors may edit within a 15-minute Edit Window; delete is always available and preserves a `[deleted]` placeholder when the comment has replies. The existing `cheers` table is migrated from `project_id` to a polymorphic `cheerable_type`/`cheerable_id` pair so the same Cheer model serves both Project and Comment targets. Project cheer routes keep working; a new comment-cheer route is added.

**Blocked by:** 08 — Project pricing + logo + launch_date

**Status:** ready-for-agent

- [ ] `comments` table: `id`, `project_id` FK cascade, `user_id` FK cascade, `parent_id` nullable self-FK, `body` text max 500, `deleted_at` (or equivalent placeholder strategy), timestamps
- [ ] Comment model with single-level reply constraint enforced in validation (`parent_id` must reference a top-level comment or be null)
- [ ] ProjectCommentController (store/update/destroy) with Form Requests and CommentPolicy (15-min edit window, always-delete, author-only)
- [ ] Deleted comments with replies show a `[deleted]` placeholder; orphaned threads stay intact
- [ ] `cheers` table migrated to polymorphic `cheerable_type`/`cheerable_id`; UNIQUE `(user_id, cheerable_type, cheerable_id)`; existing project cheers preserved
- [ ] ProjectCheerController updated for the polymorphic shape; comment-cheer store/destroy route added
- [ ] Show page renders the discussion thread with reply and cheer controls
- [ ] Feature tests cover single-level enforcement, edit window lock, delete-placeholder, polymorphic project cheer still works, comment cheer toggle, and authorization failure
- [ ] Follows ADR 0003 (polymorphic cheers)
