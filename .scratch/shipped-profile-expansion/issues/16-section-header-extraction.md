# 16 — SectionHeader.vue extraction

**What to build:** The duplicated "technical-label + display-type headline" section header markup that appears across public/studio pages is extracted into a shared `SectionHeader.vue` component and replaced at every call site. Navigation shell (`PublicShell`) is already shared — this only targets the inner section header pattern.

**Blocked by:** None — can start immediately.

**Status:** ready-for-agent

- [ ] Shared `SectionHeader` component accepts the technical label and display headline (and any existing variants used across pages)
- [ ] All current call sites (Welcome, Discover/Index, Projects/Index, Projects/Edit, Creators/Show, ShippedSettingsLayout, and any others found) use the shared component
- [ ] Visual output is unchanged (no design regression)
- [ ] No remaining duplicated section-header markup at the replaced call sites
