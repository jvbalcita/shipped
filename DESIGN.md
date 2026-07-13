# Shipped Design System — Swiss Industrial Print

Shipped is a public registry for products that have crossed the line from work-in-progress to launch. The interface should feel like a printed dispatch sheet: exact, physical, and confident.

## Foundations

- **Substrate:** `#F4F4F0` paper; do not offer dark mode.
- **Ink:** `#050505` for all text and structural lines.
- **Signal:** `#E61919` is the only accent. It marks actions, active states, warnings, and critical launch metadata.
- **Geometry:** every visible corner is square. Use 1px black structural rules and `grid gap-px` compartments instead of soft cards.
- **No:** gradients, shadows, blurred glass, pastel surfaces, pill controls, or decorative rounded cards.

## Typography

- **Display:** Archivo Black, uppercase, `clamp(3rem, 9vw, 10rem)`, tracking `-.06em`, line-height `.82`.
- **Interface/body:** IBM Plex Mono. Labels, navigation, buttons, dates, and metadata are uppercase with `.08em` tracking.
- **Reading copy:** IBM Plex Mono at a comfortable `0.875rem–1rem` with generous line-height. Use sentence case only for long-form release notes.

## Layout

- The desktop canvas is a 12-column grid; use full-width rules to separate page zones.
- Pair deliberately dense metadata rows with broad whitespace around display typography.
- Every page starts with a small red technical label, then a strong title or identifier.
- On mobile, preserve the rules and type hierarchy; collapse columns, not the system.
- **Frame closure:** content rails always terminate at a deliberate 1px bottom rule. Leave a consistent paper buffer before the shared footer; never let vertical rails drift into it.

## Components

- All interactive controls are Shipped-customized shadcn-vue primitives.
- Buttons are rectangular and use direct action language. Primary actions are red; secondary actions are paper with black borders.
- Inputs use a black outline, square corners, clear labels, and red invalid/focus treatment.
- Dialogs, drawers, and menus use paper surfaces with black structural lines.
- Image areas use real covers when available. Demo and missing covers use typographic launch plates, never generic empty gradients.

## Motion and accessibility

- Motion is short and purposeful: one page-arrival reveal and launch-card hover feedback are enough. Respect `prefers-reduced-motion`.
- Keyboard focus is a 3px red offset outline. Do not rely on color alone for status.
- Icons supplement text; all icon-only controls require an accessible name.
