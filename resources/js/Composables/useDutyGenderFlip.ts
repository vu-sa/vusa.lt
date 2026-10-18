import { computed, onMounted, onScopeDispose, ref, watch, type ComputedRef } from 'vue';

import { useAccessibilityPreferences } from './useAccessibilityPreferences';

const FLIP_INTERVAL_MS = 5000;

const FEMININE_FIRST_PROBABILITY = 0.71;

// Share one timer so position names change together, even in long lists.
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

/** Only animated labels subscribe; reduced motion keeps the masculine form static. */
export function useDutyGenderFlip(enabled: ComputedRef<boolean> = computed(() => true)): { showFeminine: ComputedRef<boolean> } {
  const { isReducedMotion } = useAccessibilityPreferences();
  let stopWatch: (() => void) | undefined;
  onMounted(() => {
    stopWatch = watch(
      [enabled, isReducedMotion],
      ([active, reduced], _previous, onCleanup) => {
        if (!active || reduced) return;

        subscriberCount += 1;
        if (subscriberCount === 1) {
          showFeminine.value = Math.random() < FEMININE_FIRST_PROBABILITY;
          startInterval();
        }
        onCleanup(() => {
          subscriberCount -= 1;
          if (subscriberCount === 0) stopInterval();
        });
      },
      { immediate: true },
    );
  });
  onScopeDispose(() => stopWatch?.());

  return { showFeminine: computed(() => isReducedMotion.value ? false : showFeminine.value) };
}
