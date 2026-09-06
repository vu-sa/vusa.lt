import { inject, ref, type InjectionKey } from 'vue';

/**
 * Document-wide "one active thing at a time" state for the full-screen editor's
 * contextual popovers and inline-editable text claims. Folding both into the same ref
 * (via the `kind` discriminant) is deliberate: opening a button's popover automatically
 * releases a live-editing title field, and vice versa — never two heavy editing surfaces
 * open together, and it's one thing to reason about instead of two.
 */
export type HotspotKind = 'popover' | 'text';

interface ActiveHotspot {
  id: string;
  kind: HotspotKind;
}

export function useActiveHotspot() {
  const active = ref<ActiveHotspot | null>(null);

  function openPopover(id: string): void {
    active.value = { id, kind: 'popover' };
  }

  function openTextField(id: string): void {
    active.value = { id, kind: 'text' };
  }

  function close(id?: string): void {
    if (id === undefined || active.value?.id === id) active.value = null;
  }

  function isPopoverOpen(id: string): boolean {
    return active.value?.kind === 'popover' && active.value.id === id;
  }

  function isTextFieldLive(id: string): boolean {
    return active.value?.kind === 'text' && active.value.id === id;
  }

  return { active, openPopover, openTextField, close, isPopoverOpen, isTextFieldLive };
}

export type UseActiveHotspotReturn = ReturnType<typeof useActiveHotspot>;

/**
 * `RCFullscreenEditor.vue` provides this once, at the document root. Only leaf hotspot
 * components (button/image hotspots, the block toolbar, a claimed title field) inject it
 * — intermediate layers never need to know it exists, so it's never prop-drilled through
 * components also used by the public site or forms mode.
 */
export const ACTIVE_HOTSPOT_KEY: InjectionKey<UseActiveHotspotReturn> = Symbol('rc-active-hotspot');

/** Convenience wrapper — leaves that only ever mount inside `RCFullscreenEditor.vue`'s
 *  subtree can rely on the provider always being present (fail loud on a wiring bug,
 *  rather than silently no-op with `inject()`'s default "return undefined" behavior). */
export function injectActiveHotspot(): UseActiveHotspotReturn {
  const hotspots = inject(ACTIVE_HOTSPOT_KEY);
  if (!hotspots) {
    throw new Error('injectActiveHotspot() called outside RCFullscreenEditor.vue\'s provide() — this component only mounts inside the full-screen editor.');
  }
  return hotspots;
}
