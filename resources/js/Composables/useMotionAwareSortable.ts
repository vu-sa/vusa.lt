import { watch } from 'vue';
import { unrefElement } from '@vueuse/core';
import { useSortable } from '@vueuse/integrations/useSortable';

import { useAccessibilityPreferences } from './useAccessibilityPreferences';

export const useMotionAwareSortable: typeof useSortable = (target, list, options = {}) => {
  const { isReducedMotion } = useAccessibilityPreferences();
  const animation = options.animation ?? 0;
  const sortable = useSortable(target, list, {
    ...options,
    animation: isReducedMotion.value ? 0 : animation,
  });

  watch([isReducedMotion, () => typeof target === 'string' ? target : unrefElement(target)], () => {
    sortable.option('animation', isReducedMotion.value ? 0 : animation);
  }, { flush: 'post' });

  return sortable;
};
