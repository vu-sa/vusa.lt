/**
 * Shell focus mode: while a form is open, the admin shell trades its navigation
 * chrome (workspace picker, palette, section tabs, breadcrumbs, bottom nav) for the
 * form's own bar, which the form teleports into `#shell-form-bar`.
 *
 * @example
 * // AdminLayout.vue (provider):
 * createShellFocusProvider()
 *
 * // FormPage.vue:
 * const shellFocus = useShellFocus()
 * if (shellFocus) onUnmounted(shellFocus.enter())
 */

import { computed, inject, provide, ref, type ComputedRef, type InjectionKey } from 'vue';

export const SHELL_FORM_BAR_ID = 'shell-form-bar';

export interface ShellFocusContext {
  isFocused: ComputedRef<boolean>;
  /** Returns the release function. */
  enter: () => () => void;
}

const SHELL_FOCUS_INJECTION_KEY: InjectionKey<ShellFocusContext> = Symbol('shell-focus');

export function createShellFocusProvider(): ShellFocusContext {
  // A counter, not a flag: on a form → form visit the next page sets up before the
  // previous one unmounts, so a flag would be cleared by the page that is leaving.
  const holders = ref(0);

  const context: ShellFocusContext = {
    isFocused: computed(() => holders.value > 0),
    enter: () => {
      holders.value++;
      let released = false;

      return () => {
        if (!released) {
          released = true;
          holders.value--;
        }
      };
    },
  };

  provide(SHELL_FOCUS_INJECTION_KEY, context);

  return context;
}

/** `null` outside the admin shell (tests, Storybook), where the form renders its bar inline. */
export function useShellFocus(): ShellFocusContext | null {
  return inject(SHELL_FOCUS_INJECTION_KEY, null);
}
