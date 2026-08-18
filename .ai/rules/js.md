---
paths:
  - 'resources/js/**'
---

# Js

## Wayfinder objects on plain anchors produce [object Object] hrefs
Wayfinder route functions (e.g. `redirect({provider})`) return a `{url, method}` object, not a string. Binding one to a plain `<a :href>` renders `href="[object Object]"` → browser navigates to `/[object Object]` → app 404. Use `route.url(args)` for plain anchors, or pass the object to Inertia's `<Link>`/`TextLink` (which understand it). POST-only routes must be form submits (useForm/`<Form>`), never GET anchors.
