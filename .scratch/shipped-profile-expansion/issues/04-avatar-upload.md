# 04 — Avatar upload

**What to build:** A creator can upload a profile avatar from the Profile settings page. The image is stored as a Laravel Storage path on `users.avatar_path`, validated as jpg/png/webp up to 3 MB with a randomized filename, and rendered on the public creator page and anywhere the creator's identity appears.

**Blocked by:** None — can start immediately.

**Status:** ready-for-agent

- [ ] Avatar upload accepted via Profile settings (reuse existing FileUpload component patterns)
- [ ] Validation enforces jpg/png/webp, max 3 MB; filename is randomized and never trusted
- [ ] Image stored via Laravel Storage; `users.avatar_path` holds the path
- [ ] Previous avatar file is cleaned up on replace
- [ ] Public creator page and shared identity UI render the avatar
- [ ] Feature tests cover upload success, validation failure (type/size), and replace/cleanup
