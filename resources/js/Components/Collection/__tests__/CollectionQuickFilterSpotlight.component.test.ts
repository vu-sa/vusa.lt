import { mount } from '@vue/test-utils';
import { ref } from 'vue';
import { describe, expect, it, vi } from 'vitest';

import CollectionQuickFilterSpotlight from '../CollectionQuickFilterSpotlight.vue';

const state = vi.hoisted(() => ({ dismiss: vi.fn() }));
vi.mock('@/Composables/useFeatureSpotlight', () => ({
  useFeatureSpotlight: () => ({ isDismissed: ref(false), dismiss: state.dismiss }),
}));

describe('CollectionQuickFilterSpotlight', () => {
  it('dismisses discovery when the filter is used and emits the filter id', async () => {
    const spotlight = { id: 'user-duplicates-v1', title: 'Dublikatai', description: 'Rask sutampančius profilius.' };
    const wrapper = mount(CollectionQuickFilterSpotlight, {
      props: { filter: { id: 'duplicates', label: 'Galimi dublikatai', active: false }, spotlight },
      global: { stubs: { SpotlightPopover: { template: '<div><slot /></div>' } } },
    });

    await wrapper.find('button').trigger('click');

    expect(state.dismiss).toHaveBeenCalledOnce();
    expect(wrapper.emitted('toggle')).toEqual([['duplicates']]);
    expect(wrapper.find('button').attributes('aria-pressed')).toBe('false');
  });
});
