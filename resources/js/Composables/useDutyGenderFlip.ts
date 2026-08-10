import { computed, onScopeDispose, ref, watch, type ComputedRef } from 'vue';
import { usePreferredReducedMotion } from '@vueuse/core';

const FLIP_INTERVAL_MS = 5000;

// ~71% of VU SR members have a feminine surname ending (-aitė, -ytė, -ė, -a, …) and ~29%
// a masculine one (-as, -is, -ys, -us), measured against the active user base. The first
// form a reader lands on is drawn from that split, so the duty name initially reads the way
// it does for most holders instead of always defaulting to the masculine dictionary form.
const FEMININE_FIRST_PROBABILITY = 0.71;

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
  if (subscriberCount === 1) {
    if (reducedMotion.value === 'reduce') {
      showFeminine.value = false;
    }
    else {
      // The first subscriber on the page rolls the initial form, weighted by the real
      // feminine/masculine split of the membership, before the shared flip timer starts.
      showFeminine.value = Math.random() < FEMININE_FIRST_PROBABILITY;
      startInterval();
    }
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
