import { useMediaQuery } from '@vueuse/core';

/** The one "phone or narrow tablet" query the admin shell uses (below Tailwind's `md`). */
export function useIsMobile() {
  return useMediaQuery('(max-width: 767px)');
}
