import { computed, onBeforeUnmount, ref } from 'vue';

function isKeyboardFocus(target: EventTarget | null): boolean {
  try {
    return target instanceof Element && target.matches(':focus-visible');
  }
  catch {
    return false;
  }
}

/**
 * A node's details follow the pointer (or keyboard focus), not the selection, so they
 * never stay stuck open; a short grace lets the pointer cross onto the popover's link.
 */
export function useNodeDetails() {
  const hovered = ref(false);
  const focused = ref(false);
  let closing: ReturnType<typeof setTimeout> | undefined;

  const enter = (): void => {
    clearTimeout(closing);
    hovered.value = true;
  };
  const leave = (): void => {
    clearTimeout(closing);
    closing = setTimeout(() => { hovered.value = false; }, 150);
  };
  onBeforeUnmount(() => clearTimeout(closing));

  return {
    open: computed(() => hovered.value || focused.value),
    enter,
    leave,
    // Only keyboard focus: a click also focuses the button and would pin the details open.
    onFocus: (event: FocusEvent) => { focused.value = isKeyboardFocus(event.target); },
    onBlur: () => { focused.value = false; },
  };
}
