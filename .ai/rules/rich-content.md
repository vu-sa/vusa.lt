---
paths:
  - 'resources/js/Components/RichContent/**'
---

# Rich Content

## Band chrome is derived, not authored — set `bandRole`, never re-add background/padding
A block's ground (tint/border/bleed) is computed by `bandLayout.ts`'s `resolveBands()` from document position — it is NOT a per-block authored setting. `options.background`/`padding`/`rounded`/`divider`/`bleed` were removed (2026-09-04 migration `collapse_section_chrome_into_band_presentation`); the only author override left is `options.presentation: 'auto'|'plain'|'emphasis'`.

When adding a new content type that should render its own full-bleed section chrome:
- Set `bandRole: 'band'` in its `Types/index.ts` registry entry (or a function form if the answer depends on a variant, like `hero`/`spotify-embed`).
- Its display component takes a `band?: BandResolution` prop and either passes it straight to `RCSection.vue` (`:band`) or reads `band.classes`/`band.tint`/`band.bleeds` directly if it doesn't use `RCSection`.
- Never hardcode `bg-secondary/40`/`border-y`/`rc-viewport` chrome yourself — use `BAND_GROUND_CLASS`/`BAND_PADDING` from `sectionClasses.ts`, or just bind `band.classes`.
- `RichContentParser.vue` computes the band map once (`resolveBands`) and forwards `:band` down through `RichContentBlock.vue`, gated the same way `:resolved` is (undeclared types get `undefined`, never a stringified object).
- Exception: `EventCalendarElement.vue` is deliberately NOT registered as a band — it always wants a tint regardless of alternation, since it sits outside the `SectionOptions` system entirely.

## Band chrome is derived; plain padding is authored
Resolve band ground from document order in bandLayout.ts. Authors may choose only presentation auto|plain; emphasis is reserved for CTA bands. Automatic always uses the standard band padding, while plain may use options.plainPadding none|compact|default.

## Band chrome is derived, not authored — set `bandRole`, never re-add background/padding
A block's ground (tint/border/bleed) is computed by bandLayout.ts from document position. The only presentation values are auto|plain; CTA bands alone use emphasis. Do not restore general background/rounded/divider/bleed controls. Plain blocks may author options.plainPadding as none|compact|default; automatic bands keep fixed padding.
