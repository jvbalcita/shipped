# 12 — TipTap description editor

**What to build:** A creator edits a project's description with a TipTap rich-text editor restricted to bold, italic, bullet list, ordered list, numbered list, link, and blockquote. The HTML is stored in the existing `description` text column and rendered on the Show page via sanitized `v-html` plus scoped prose styles that fit the Swiss Industrial Print aesthetic. Headings, images, tables, mention, strikethrough, underline, text color, and highlight are disabled.

**Blocked by:** 08 — Project pricing + logo + launch_date

**Status:** ready-for-agent

- [ ] TipTap Vue 3 packages installed with only the curated extension set enabled
- [ ] Create/Edit description field uses the TipTap editor (no free-form HTML input)
- [ ] Description stored as HTML in `projects.description`
- [ ] Show page renders description via sanitized `v-html` (strips script tags and event-handler attributes) with scoped prose styles
- [ ] Existing plain-text descriptions continue to render correctly after the change
- [ ] Feature tests cover store/update with allowed markup and assert dangerous markup is stripped on render
- [ ] Follows ADR 0004 (TipTap HTML description)
