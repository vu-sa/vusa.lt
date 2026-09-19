import { onBeforeUnmount, ref } from 'vue';

/** A click right after a hover-open is the same gesture, not a request to close it again. */
const CLICK_AFTER_OPEN_MS = 350;

/**
 * Controlled open state for a popover that opens on hover, focus and click. Wire `openNow` /
 * `scheduleClose` to mouseenter/leave and focus events on both trigger and content (the content
 * teleports out of the trigger's subtree), and `onOpenChange` to the popover's `update:open`.
 *
 * The same idiom as `Public/Nav/PadalinysSelector.vue`, kept separate because the public and
 * admin component tiers never import each other.
 */
export function useHoverPopover(closeDelayMs = 150) {
  const open = ref(false);
  /** Whether the current open came from the pointer, so focus should stay put (no ring on hover). */
  const openedByHover = ref(false);
  let closeTimer: ReturnType<typeof setTimeout> | undefined;
  let openedAt = 0;

  const cancelClose = () => {
    clearTimeout(closeTimer);
    closeTimer = undefined;
  };

  const set = (value: boolean) => {
    if (value && !open.value) {
      openedAt = Date.now();
    }

    open.value = value;
  };

  const openNow = () => {
    cancelClose();

    if (!open.value) {
      openedByHover.value = true;
    }

    set(true);
  };

  const close = () => {
    cancelClose();
    set(false);
  };

  const scheduleClose = () => {
    cancelClose();
    closeTimer = setTimeout(close, closeDelayMs);
  };

  const onOpenChange = (value: boolean) => {
    cancelClose();

    if (!value && Date.now() - openedAt < CLICK_AFTER_OPEN_MS) {
      return;
    }

    if (value && !open.value) {
      openedByHover.value = false;
    }

    set(value);
  };

  onBeforeUnmount(cancelClose);

  return { open, openedByHover, openNow, cancelClose, close, scheduleClose, onOpenChange };
}
