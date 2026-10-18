import { computed, onScopeDispose, ref, toValue, watch, type MaybeRefOrGetter } from 'vue';

import { useAccessibilityPreferences } from './useAccessibilityPreferences';

export function useMotionAwareAutoplay(
  advance: () => void,
  enabled: MaybeRefOrGetter<boolean>,
  delay: MaybeRefOrGetter<number>,
) {
  const { isReducedMotion } = useAccessibilityPreferences();
  const hovered = ref(false);
  const focused = ref(false);
  const dragging = ref(false);
  const canPlay = computed(() => toValue(enabled) && !isReducedMotion.value
    && !hovered.value && !focused.value && !dragging.value);
  let timer: ReturnType<typeof setTimeout> | undefined;
  let disposed = false;

  function stop(): void {
    clearTimeout(timer);
    timer = undefined;
  }

  function restart(): void {
    stop();
    if (disposed || !canPlay.value) return;
    timer = setTimeout(() => {
      if (canPlay.value) advance();
      restart();
    }, toValue(delay));
  }

  const pause = () => {
    dragging.value = true;
  };
  const resume = () => {
    dragging.value = false;
  };
  const events = {
    mouseenter: () => { hovered.value = true; },
    mouseleave: () => { hovered.value = false; },
    focusin: () => { focused.value = true; },
    focusout: (event: FocusEvent) => {
      if (!(event.currentTarget as HTMLElement).contains(event.relatedTarget as Node | null)) focused.value = false;
    },
    pointerdown: pause,
    pointerup: resume,
    pointercancel: resume,
  };

  watch([canPlay, () => toValue(delay)], restart, { immediate: true, flush: 'sync' });
  onScopeDispose(() => {
    disposed = true;
    stop();
  });

  return { events, restart, pause, resume };
}
