import { afterEach, describe, expect, it, vi } from 'vitest';
import { effectScope, nextTick, ref } from 'vue';
import { useSortable } from '@vueuse/integrations/useSortable';

import { useAccessibilityPreferences } from '../useAccessibilityPreferences';
import { useMotionAwareSortable } from '../useMotionAwareSortable';

vi.mock('@vueuse/integrations/useSortable', () => ({
  useSortable: vi.fn(() => ({ option: vi.fn(), stop: vi.fn(), start: vi.fn() })),
}));

afterEach(() => useAccessibilityPreferences().reset());

describe('motion-aware sorting', () => {
  it('removes sorting animation and follows preference and element changes', async () => {
    const preferences = useAccessibilityPreferences();
    preferences.reduceMotion.value = true;
    const scope = effectScope();
    const target = ref<HTMLElement | null>(null);
    const sortable = scope.run(() => useMotionAwareSortable(target, ref([1, 2]), { animation: 150 }))!;
    try {
      expect(useSortable).toHaveBeenLastCalledWith(target, expect.anything(), expect.objectContaining({ animation: 0 }));
      preferences.reduceMotion.value = false;
      await nextTick();
      expect(sortable.option).toHaveBeenLastCalledWith('animation', 150);
      target.value = document.createElement('div');
      await nextTick();
      expect(sortable.option).toHaveBeenLastCalledWith('animation', 150);
      preferences.reduceMotion.value = true;
      await nextTick();
      expect(sortable.option).toHaveBeenLastCalledWith('animation', 0);
    }
    finally {
      scope.stop();
    }
  });
});
