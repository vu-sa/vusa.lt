import { computed, onScopeDispose, ref, watch, type ComputedRef } from 'vue';
import { usePreferredReducedMotion } from '@vueuse/core';

const FLIP_INTERVAL_MS = 5000;

// Module-level singleton: every InflectedDutyName on a page shares one interval and one
// flag, so a table full of duty names flips in unison instead of drifting independently,
// and cost stays at one timer regardless of how many labels are mounted.
const showFeminine = ref(false);
let subscriberCount = 0;
let intervalId: ReturnType<typeof setInterval> | null = null;

function startInterval() {
  if (intervalId !== null) {
    return;
  }
  intervalId = setInterval(() => {
    showFeminine.value = !showFeminine.value;
  }, FLIP_INTERVAL_MS);
}

function stopInterval() {
  if (intervalId !== null) {
    clearInterval(intervalId);
    intervalId = null;
  }
  showFeminine.value = false;
}

/**
 * Drives the masculine/feminine crossfade in InflectedDutyName.vue from one shared
 * timer, so many instances on the same page (a duty table, a wizard step) change
 * together rather than as independent flicker. Respects `prefers-reduced-motion`:
 * when set, the interval never starts and every instance stays on the masculine form.
 */
export function useDutyGenderFlip(): { showFeminine: ComputedRef<boolean> } {
  const reducedMotion = usePreferredReducedMotion();

  subscriberCount += 1;
  if (subscriberCount === 1 && reducedMotion.value !== 'reduce') {
    startInterval();
  }

  const stopWatch = watch(reducedMotion, (preference) => {
    if (preference === 'reduce') {
      stopInterval();
    }
    else if (subscriberCount > 0) {
      startInterval();
    }
  });

  onScopeDispose(() => {
    stopWatch();
    subscriberCount = Math.max(0, subscriberCount - 1);
    if (subscriberCount === 0) {
      stopInterval();
    }
  });

  return { showFeminine: computed(() => showFeminine.value) };
}
