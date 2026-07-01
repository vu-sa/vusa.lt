import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref } from 'vue';

import CommandPaletteTrigger from '@/Components/CommandPalette/CommandPaletteTrigger.vue';
import { commonStubs } from '@/tests/stubs';

const toggleMock = vi.fn();

vi.mock('@/Composables/useCommandPalette', () => ({
  useCommandPalette: vi.fn(() => ({
    isOpen: ref(false),
    query: ref(''),
    recentItems: ref([]),
    open: vi.fn(),
    close: vi.fn(),
    toggle: toggleMock,
    addRecentItem: vi.fn(),
    clearRecentItems: vi.fn(),
  })),
}));

describe('CommandPaletteTrigger.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  const createWrapper = () => {
    return mount(CommandPaletteTrigger, {
      global: {
        stubs: commonStubs,
      },
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    wrapper?.unmount();
  });

  describe('rendering', () => {
    it('renders a button with search icon', () => {
      wrapper = createWrapper();
      const button = wrapper.find('button');

      expect(button.exists()).toBe(true);
      expect(button.find('.lucide-search').exists()).toBe(true);
    });

    it('does not render a text label', () => {
      wrapper = createWrapper();

      expect(wrapper.text()).not.toContain('Ieškoti...');
    });

    it('does not render a keyboard shortcut badge inside the button', () => {
      wrapper = createWrapper();
      const kbd = wrapper.find('button kbd');

      expect(kbd.exists()).toBe(false);
    });
  });

  describe('interaction', () => {
    it('toggles the command palette on click', async () => {
      wrapper = createWrapper();
      const button = wrapper.find('button');

      await button.trigger('click');

      expect(toggleMock).toHaveBeenCalled();
    });
  });
});
