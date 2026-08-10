import { describe, it, expect, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';

import FormElement from '@/Components/AdminForms/FormElement.vue';
import { commonStubs } from '@/tests/stubs';

describe('FormElement.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  afterEach(() => {
    wrapper?.unmount();
  });

  const createWrapper = (props = {}, slots = {}) => {
    return mount(FormElement, {
      props,
      slots: {
        title: 'Section Title',
        default: '<div data-testid="slot-content">Form fields</div>',
        ...slots,
      },
      global: {
        stubs: {
          ...commonStubs,
          IFluentCheckmark16Filled: { template: '<span class="icon-check" />' },
        },
      },
    });
  };

  describe('layout structure', () => {
    it('places title in the left column (aside) when there is a description', () => {
      wrapper = createWrapper({}, {
        description: '<p data-testid="desc">Description text</p>',
      });

      const h3 = wrapper.find('h3');
      const aside = wrapper.find('aside');
      const slotContent = wrapper.find('[data-testid="slot-content"]');

      // Title should be inside the aside (left column)
      expect(aside.exists()).toBe(true);
      expect(aside.element.contains(h3.element)).toBe(true);

      // Slot content should NOT be inside the aside
      expect(aside.element.contains(slotContent.element)).toBe(false);
    });

    it('places title above slot content when there is no description', () => {
      wrapper = createWrapper();

      const h3 = wrapper.find('h3');
      const slotContent = wrapper.find('[data-testid="slot-content"]');
      const mainColumn = slotContent.element.parentElement;

      // No aside when no description
      expect(wrapper.find('aside').exists()).toBe(false);

      // Title should be in the same column as the slot content
      expect(mainColumn?.contains(h3.element)).toBe(true);
    });

    it('places title above slot content when noSider is true', () => {
      wrapper = createWrapper({ noSider: true }, {
        description: '<p>Description text</p>',
      });

      const h3 = wrapper.find('h3');
      const slotContent = wrapper.find('[data-testid="slot-content"]');
      const mainColumn = slotContent.element.parentElement;

      expect(wrapper.find('aside').exists()).toBe(false);
      expect(mainColumn?.contains(h3.element)).toBe(true);
    });

    it('renders only one title when there is a description sidebar', () => {
      wrapper = createWrapper({}, {
        description: '<p>Description text</p>',
      });

      expect(wrapper.findAll('h3')).toHaveLength(1);
    });

    it('renders only one title when there is no description sidebar', () => {
      wrapper = createWrapper();

      expect(wrapper.findAll('h3')).toHaveLength(1);
    });
  });

  describe('section indicator', () => {
    it('renders section indicator when sectionNumber is provided', () => {
      wrapper = createWrapper({ sectionNumber: 1 });

      expect(wrapper.find('.size-9').exists()).toBe(true);
      expect(wrapper.text()).toContain('1');
    });

    it('does not render section indicator when sectionNumber and icon are absent', () => {
      wrapper = createWrapper();

      expect(wrapper.find('.size-9').exists()).toBe(false);
    });

    it('renders completed styling when isComplete is true', () => {
      wrapper = createWrapper({ sectionNumber: 1, isComplete: true });

      expect(wrapper.find('.bg-emerald-500').exists()).toBe(true);
    });
  });

  describe('variants', () => {
    it('applies highlighted variant classes', () => {
      wrapper = createWrapper({ variant: 'highlighted' });

      expect(wrapper.find('.rounded-xl').exists()).toBe(true);
    });

    it('applies subtle variant classes', () => {
      wrapper = createWrapper({ variant: 'subtle' });

      expect(wrapper.find('.opacity-75').exists()).toBe(true);
    });
  });

  describe('separator', () => {
    it('renders separator by default', () => {
      wrapper = createWrapper();

      expect(wrapper.find('[data-orientation="horizontal"]').exists()).toBe(true);
    });

    it('hides separator when noDivider is true', () => {
      wrapper = createWrapper({ noDivider: true });

      expect(wrapper.find('[data-orientation="horizontal"]').exists()).toBe(false);
    });
  });

  describe('without title slot', () => {
    it('renders slot content even when no title is provided', () => {
      wrapper = mount(FormElement, {
        slots: {
          default: '<div data-testid="slot-content">Content only</div>',
        },
        global: {
          stubs: commonStubs,
        },
      });

      expect(wrapper.find('[data-testid="slot-content"]').exists()).toBe(true);
      expect(wrapper.find('h3').exists()).toBe(false);
    });
  });
});
