# 02 — Username change-with-reservation flow

**What to build:** A creator can change their username from the Profile settings page. When they do, the old username is held in a `reserved_usernames` table for a 30-day window so it cannot be immediately squatted. After the window expires the old username is released and available again. Rate-limiting prevents rapid cycling.

**Blocked by:** 01 — Username rename + user-chosen at registration

**Status:** ready-for-agent

- [ ] `reserved_usernames` table holds released usernames with an expiry timestamp
- [ ] Profile settings exposes a change-username form
- [ ] Changing username reserves the old value for 30 days and updates the creator's `username`
- [ ] Registration and change-username both reject usernames currently held in the reservation table
- [ ] A scheduled command (or equivalent) purges expired reservations so old usernames become available
- [ ] Rate limit prevents more than one username change per cooldown period
- [ ] Feature tests cover reservation, squat-prevention, expiry release, and rate limiting
