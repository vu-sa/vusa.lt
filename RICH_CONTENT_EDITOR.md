# Rich-content editor conventions

The full-screen editor is a WYSIWYG canvas, not a second form. It renders the same display
component and public design surface as the published page. The regular block form remains the
complete, structured editing fallback.

## When migrating a block

1. Keep its display component the single source of published markup. Do not create a separate
   full-screen rendering branch.
2. Register `inlineEditable: true` only when the display explicitly supports `editable`,
   `blockKey`, and its update contract. `BlockPreviewRenderer` gates those props, preventing
   undeclared props from reaching the DOM.
3. Put direct text edits in the display with `RCInlineText`; use one live TipTap field at a time
   for rich text, claimed through `useActiveHotspot`.
4. Put structured fields (images, buttons, selects, lists) in contextual popovers anchored to the
   visible thing they edit. Keep the controls out of published layout flow.
5. Use `RCAddPlaceholder` only in editable mode. A block must render its normal empty/public
   state when `editable` is false so the full-screen preview is clean.
6. Keep whole-block controls in `RCBlockToolbarShell`; type-specific controls belong in its slot
   or a type toolbar such as `HeroBlockToolbar`.

## Preview contract

The full-screen header owns the preview toggle. It passes `preview` through
`RCFullscreenBlock` to `BlockPreviewRenderer`; the renderer then disables the editable contract.
This removes hotspots, placeholders, insert affordances, and block toolbars without adding
per-element preview code. Future migrated blocks must treat `editable: false` as a complete,
published-state preview.

The same header owns theme switching and Save. Save emits through `RichContentEditor` and
`RichContentFormElement` to the surrounding Page, News, or home-page form; never add a
block-specific save endpoint.

## Regular form convention

The non-full-screen form is the exhaustive editor. Group related controls in a multiple-open
accordion, in this order, if applicable:

1. General — layout, width, presentation, and other block-level choices.
2. Text — text fields and text-placement options.
3. Image and associated elements — source, alt text, focus, overlays, and decorations.
4. Buttons — calls to action and their links.

Use concise, labelled icon controls alongside an existing image; retain text labels when there is
no image yet or when the action would otherwise be ambiguous. Every icon button needs an accessible
name and tooltip.

## Layout and presentation

Band background and padding come from `bandLayout.ts`; do not restore per-block section chrome.
Register `bandRole` for blocks that render a band. The split Hero image uses a 16:10 frame so its
two-column layout remains landscape rather than becoming a tall square.

## Anchors

Popover anchors must be the visible trigger whenever one exists. A block wrapper is only a
mount-time fallback. This is especially important for image rails and the whole-block “more
options” button: the popover should appear next to the control the author clicked.

## HTML text fields: presets, v-html, and emptiness

A field whose stored value is an HTML string (TiptapEditor with `html`) needs the same treatment
in every branch that renders it, or the two surfaces drift:

- **Every** render of the value — including the editable placeholder/static branch, not just the
  "public" one — must use `v-html`, never `{{ }}`. Interpolating an HTML string prints the literal
  tags (`<p>Aprašymas</p>`) instead of the formatted text. This is easy to miss on a field that
  used to be plain text (a bare `Input`) and only later became HTML: grep every
  `{{ field }}` for that field name when migrating it.
- **Emptiness checks must strip tags first.** `value?.trim()` on `'<p></p>'` is truthy — an
  "empty" doc still has a wrapper tag. Reuse the `elementText()` pattern (strip
  `<[^>]*>`, then trim) for any `hasX` computed gating a placeholder.
- **Restrict the extensions, not just the visible toolbar.** `toolbar="bubble"` hides the fixed
  toolbar, and `TiptapFormattingButtons` only ever renders bold/italic/underline regardless of
  preset — but the `compact`/`full` presets still *register* headings, lists, images, etc., so
  their markdown input rules (`"# "`, `"- "`, `"> "`) still fire even with nothing on screen to
  trigger them deliberately. For a field that must stay a single styled line (a hero title, a
  short description), use `preset="marks"` (bold/italic/underline only, nothing else registered)
  — not `compact` — and always pair it with `toolbar="bubble"`, in **every** surface that edits
  the field (the full-screen editor's live field *and* the regular form's own `TiptapEditor`).
  Two surfaces editing the same field with different presets is the bug, not a variant.

## RCInlineText / any "restart with fresh state" field: seed on every (re)attach, not just mount

`RCInlineText`'s contenteditable branch is behind `v-if`/`v-else` (`editable` toggle). Toggling
`editable` off and back on does **not** remount the component — it swaps which branch of the
*same* component instance is active, which means Vue tears down and recreates only the DOM node
for that branch. `onMounted` fires once, at the component's actual creation, so anything it does
(`elRef.value.textContent = props.modelValue`) never reruns on the second and later entries into
edit mode — the freshly created node starts empty. Symptom: type something, switch to preview,
switch back to edit — the field looks blank even though the underlying reactive value is correct
(view mode, which re-renders via a real interpolation, still shows it fine).

The fix is a **function ref** (`:ref="setElRef"`) instead of a static one (`ref="elRef"`) —
Vue calls a function ref on every (re)attach, not just the first mount, so seed the DOM there
instead of in `onMounted`. Guard it exactly like the external-change `watch` (skip while
focused, skip if already correct) — a function ref is also called on ordinary reactive
re-renders, not only branch swaps, so an unguarded assignment mid-keystroke would reset the caret.
Any other field that reconstructs its edit-mode DOM node via `v-if`/`v-else` on the same
instance (rather than being unmounted/remounted by a parent's `v-if`) needs the same pattern.

## Capping a repeatable list: both surfaces, one number

A field editable from both the regular form (`DynamicListInput`, which already takes a `max`
prop) and the full-screen hotspot editor (a bespoke add affordance, e.g.
`HeroButtonsEditable`'s `RCAddPlaceholder`) needs the cap enforced — and hidden once reached —
in both places, with the same number. Don't rely on the form's `max` alone: the full-screen
editor's own "add" handler must refuse past the cap too (defense in depth against a stray
extra call), and its add affordance must disappear at the same count the form's does, or one
surface stays permissive after the other has already capped out.

## Bubble menu buttons: one border, not two

`BubbleMenu`'s own container is already a bordered, shadowed pill (`rounded-lg border
bg-white p-1 shadow-md`). Anything inside it must be borderless — a nested `ButtonGroup` of
`outline`/`default` buttons (the fixed toolbar's look) draws a *second* bordered pill inside the
first ("a wrap in a wrap"). `TiptapFormattingButtons` and `TipTapMarkButton` take a `bubble` prop
that switches `size`/`variant` from the toolbar's `sm`/`outline`/`default` to the bubble's
`icon-sm`/`ghost`/`secondary` — pass `bubble` wherever a control is mounted inside a
`BubbleMenu`, never inside the fixed toolbar.
