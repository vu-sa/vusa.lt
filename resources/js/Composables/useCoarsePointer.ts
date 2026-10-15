import { useMediaQuery } from '@vueuse/core';

/** Native date and time controls are more usable with a touch keyboard and OS picker. */
export function useCoarsePointer() {
  return useMediaQuery('(pointer: coarse)');
}
