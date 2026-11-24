import { readonly, ref } from 'vue';

/** How long a shaken tree makes it snow. */
export const GUST_MS = 30_000;

const gusting = ref(false);
/** Stays true after the first snowfall, so the shell loads the snow layer only for someone who asked for it. */
const started = ref(false);
let timer: ReturnType<typeof setTimeout> | null = null;

const clear = () => {
  if (timer !== null) {
    clearTimeout(timer);
    timer = null;
  }
};

/**
 * The snow easter egg: touching the fir sets off a heavy snowfall for this person only, and touching it again stops it.
 * One shared flag that outlives page visits, so the shell's single snow layer keeps falling across navigation.
 */
export function useSeasonSnow() {
  const start = () => {
    gusting.value = true;
    started.value = true;
    clear();
    timer = setTimeout(() => {
      gusting.value = false;
      timer = null;
    }, GUST_MS);
  };

  const stop = () => {
    gusting.value = false;
    clear();
  };

  const toggle = () => (gusting.value ? stop() : start());

  return { gusting: readonly(gusting), started: readonly(started), start, stop, toggle };
}
