---
paths:
  - 'resources/js/**/Public/**'
---

# Public

## Public component tier: Base primitives, story-per-primitive
Public components have their own primitives tier, `Components/Public/Base/`, parallel to (and never importing from/to) admin's `Patterns/`. New generic, domain-free public UI goes there; anything calling `route()` or referencing `App.Entities.*` belongs in an entity-scoped `Public/<area>/` folder instead, not `Base/`.

Every `Public/Base/**` primitive ships a Storybook story covering its variants with `parameters: { a11y: { test: 'error' } }` — jsdom can't resolve Tailwind's `dark:` variant, so Storybook's Surface/Theme toolbar is the only place either theme is actually checked. Components elsewhere under `Public/**` (e.g. `Public/Nav/`) aren't held to this by tooling yet, but should still get a story per `.storybook/CLAUDE.md`'s decision tree (visual appearance or user interaction → story) — `PadalinysSelector` and `LocaleButton`/`LocaleFlag` don't have one today and are the known gap.

## Public overrides of shadcn ui/ variants need explicit dark:
A `dark:`-prefixed class inside a shared `ui/` primitive (e.g. the ghost button variant's `dark:hover:text-zinc-50`, `NavigationMenuLink`'s `dark:` hover fill) beats an unprefixed override passed in from a public consumer, regardless of source order in the compiled CSS. Any public component overriding a shadcn variant's colour must repeat that override under `dark:` too, or it silently reverts to the primitive's default in dark mode. Has bitten `navigationMenuTriggerStyle`, `navButtonClass`, and `HeroButtons` so far — expect it on any new shadcn variant override.

## Public chrome shares one measure and one hover-bleed idiom
- One measure: the header, `SecondMenu`, and every `SectionBand` share `max-w-7xl px-5 sm:px-6 lg:px-8`. Don't invent a different max-width for a new public band — it will visibly misalign against everything else on the page.
- Full-width list rows (event lists, mega-menu columns) that need their hover fill to bleed slightly past the visible content, without touching the section's own outer edge, use `-mx-<n> px-<n>` (equal and opposite) rather than adding plain padding — plain padding would shift the content itself out of alignment with headings above it. Keep `n` smaller than the section's own horizontal padding so the bleed stays inside the band. Precedent: `EventCalendarElement.vue`, `RCEventList/EventListDisplay.vue`.

## Anchor/img sizing traps in the public header chrome
- A plain `<a>` (e.g. `SmartLink`) wrapping an image/logo is an inline element whose box height follows the surrounding line-height, not its content — it renders visibly taller than what's inside it. Fix by making the anchor itself `inline-flex items-center`, not by resizing the child.
- An `<img>` needs its `width`/`height` HTML attributes to match the real source asset's aspect ratio (or an explicit `aspect-[...]` class) — mismatched attributes set an implicit browser aspect-ratio that silently stretches/squashes the image once only one CSS dimension (e.g. `h-*` + `w-auto`) is set.
- Reka-ui `Popover` has no native hover trigger (unlike `NavigationMenu`). To open one on hover: make it controlled (`:open`, not just `@update:open`), add `mouseenter`/`mouseleave` handlers (with a ~150ms close delay) to *both* the trigger and `PopoverContent` (content teleports out of the trigger's DOM subtree, so a wrapping listener alone misses it), and mirror them with `focus`/`blur` (trigger) + `focusin`/`focusout` (content) — `vuejs-accessibility/mouse-events-have-key-events` enforces the latter. Precedent: `PadalinysSelector.vue`.

## ui/button force-squares any descendant svg without size-*
`ui/button`'s base class ships `[&_svg:not([class*='size-'])]:size-4` — any descendant `<svg>` lacking a `size-*` class gets force-squared to 16×16, regardless of its own `w-*`/`h-*`/`aspect-*` classes (the selector wins on specificity). Only matches the literal `svg` tag, not a `<span>`-built graphic. A non-square SVG dropped inside a `Button` (e.g. a flag icon) needs `!w-<n> !h-<n>` (important) to override it — a plain `size-*` class isn't an option since it's always square. Bit `LocaleFlag`'s Union Jack.

## Ink scrim on photo cards must stay strong past mid-height
The default photo-card gradient (`navLinkStyles.ts`'s `gradientClass.bottom`) must not fade to fully transparent by the card's vertical midpoint, or it reads as "no scrim at all" next to the hero's stronger treatment — this happened once (`from-ink/60 via-ink/20 to-transparent`) and was invisible on real photos even though the code was "correct" (right token, right direction). Match the hero's own vertical layer for strength: `from-ink via-ink/70 to-transparent` (see `HeroCarouselDisplay.vue`). Verify any future change with a real screenshot (`.design-reference/menu-shot.mjs` pattern), not just by reading the class name — a weak gradient compiles fine and looks fine in isolation on a light background, but disappears against a real photo.
