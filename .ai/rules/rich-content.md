---
paths:
  - 'resources/js/Components/RichContent/**'
---

# Rich Content

## Band chrome is derived, not authored — set `bandRole`, never re-add background/padding
A block's ground (tint/border/bleed) is computed by `bandLayout.ts`'s `resolveBands()` from document position — it is NOT a per-block authored setting. Do not restore `options.background`/`padding`/`rounded`/`divider`/`bleed` (removed by the 2026-09-04 migration `collapse_section_chrome_into_band_presentation`).
- Authors choose only `options.presentation: 'auto'|'plain'`; `emphasis` is reserved for CTA bands. Automatic bands keep the standard padding; plain blocks may set `options.plainPadding: 'none'|'compact'|'default'`.
- A new content type that renders its own full-bleed section sets `bandRole: 'band'` in its `Types/index.ts` registry entry (or a function form when the answer depends on a variant, like `hero`/`spotify-embed`).
- Its display component takes a `band?: BandResolution` prop and passes it to `RCSection.vue` (`:band`), or binds `band.classes` / reads `band.tint`/`band.bleeds` directly when it keeps its own markup (`EventCalendarElement.vue`, `CtaBandDisplay.vue`). Never hardcode `bg-secondary/40`/`border-y`/`rc-viewport`; use `BAND_GROUND_CLASS`/`BAND_PADDING` from `sectionClasses.ts`.
- `RichContentParser.vue` computes the band map once and forwards `:band` through `RichContentBlock.vue`, gated like `:resolved` (undeclared types get `undefined`).

## Use the published display for editor previews
Render live blocks through BlockPreviewRenderer on the public surface. Register inlineEditable only for displays that implement the edit contract; preview mode must render the published state without hotspots, placeholders, or toolbars. Route full-screen Save through RichContentEditor and RichContentFormElement to the parent form.

## Keep inline editing on the canvas and structured editing in the side dialog
Use RCInlineText or a single active TipTap field for direct text edits, contextual popovers for visible structured elements, and RCBlockToolbarShell for whole-block controls. Keep ContentEditorFactory as the structured fallback in RCSideBySideDialog. Anchor popovers to the visible trigger, using the block root only until the trigger mounts.

## Keep HTML field rendering and editing consistent
Render stored HTML strings with v-html in every display branch and strip tags before testing whether they are empty. Use the same Tiptap preset in every editor surface for a field; short styled lines use preset="marks" with toolbar="bubble" so hidden block extensions cannot activate.

## Keep repeatable block controls in sync
Give new inline-editable repeatable blocks visible starter items. Apply the same item limit in structured forms and canvas controls, including the add handler. Put compact add, remove, and reorder controls in the block toolbar; use focused dialogs for secondary fields rather than DynamicListInput inside a popover.

## Use shared optional section toolbar controls
For blocks with usesSectionChrome, use RCSectionToolbarOptions in More Options for adding or removing the optional header while keeping presentation and padding available. Keep row and column controls anchored to their own visible canvas elements rather than folding them into block-level options.
