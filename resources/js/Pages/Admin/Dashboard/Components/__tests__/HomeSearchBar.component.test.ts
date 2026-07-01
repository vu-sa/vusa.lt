import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick, ref } from 'vue';
import { router } from '@inertiajs/vue3';

import HomeSearchBar from '@/Pages/Admin/Dashboard/Components/HomeSearchBar.vue';

const dismissMock = vi.fn();

vi.mock('@/Composables/useFeatureSpotlight', () => ({
  useFeatureSpotlight: vi.fn(() => ({
    isVisible: ref(true),
    isDismissed: ref(false),
    isPopoverOpen: ref(false),
    targetRef: ref(null),
    title: '',
    description: '',
    position: 'bottom',
    dismiss: dismissMock,
    reset: vi.fn(),
    showPopover: vi.fn(),
    hidePopover: vi.fn(),
  })),
}));

describe('HomeSearchBar.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  const createWrapper = () => {
    return mount(HomeSearchBar, {
      global: {
        stubs: {
          SpotlightPopover: {
            template: '<div class="spotlight-popover"><slot /></div>',
          },
        },
      },
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    wrapper?.unmount();
  });

  describe('navigation', () => {
    it('navigates to search.index without query on click', async () => {
      wrapper = createWrapper();
      const input = wrapper.find('input');

      await input.trigger('click');

      expect(router.visit).toHaveBeenCalledWith('/mocked-route/search.index');
    });

    it('navigates to search.index with query on enter', async () => {
      wrapper = createWrapper();
      const input = wrapper.find('input');

      await input.setValue('test query');
      await input.trigger('keydown.enter');

      expect(router.visit).toHaveBeenCalledWith('/mocked-route/search.index?q=test%20query');
    });

    it('navigates to search.index with query on input when text exists', async () => {
      wrapper = createWrapper();
      const input = wrapper.find('input');

      await input.setValue('hello');
      await input.trigger('input');

      expect(router.visit).toHaveBeenCalledWith('/mocked-route/search.index?q=hello');
    });

    it('does not navigate on input when text is empty', async () => {
      wrapper = createWrapper();
      const input = wrapper.find('input');

      await input.setValue('');
      await input.trigger('input');

      expect(router.visit).not.toHaveBeenCalled();
    });

    it('dismisses spotlight when interacting', async () => {
      wrapper = createWrapper();
      const input = wrapper.find('input');

      await input.trigger('click');

      expect(dismissMock).toHaveBeenCalled();
    });

    it('clears local value after navigation', async () => {
      wrapper = createWrapper();
      const input = wrapper.find('input');

      await input.setValue('something');
      await input.trigger('keydown.enter');

      expect((input.element as HTMLInputElement).value).toBe('');
    });
  });
});
