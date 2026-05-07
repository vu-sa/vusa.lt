import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';

import SingleSelect from '@/Components/ui/single-select/SingleSelect.vue';
import { commonStubs } from '@/tests/stubs';

describe('SingleSelect.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  const options = [
    { id: 1, name: 'Institution A', tenant: { shortname: 'VU SA' } },
    { id: 2, name: 'Institution B', tenant: { shortname: 'VU MIF' } },
    { id: 3, name: 'Institution C', tenant: { shortname: 'VU IF' } },
  ];

  const createWrapper = (props: Record<string, any> = {}) => {
    return mount(SingleSelect, {
      props: {
        modelValue: null,
        options,
        labelField: 'name',
        valueField: 'id',
        placeholder: 'Select...',
        emptyText: 'No results',
        ...props,
      },
      attachTo: document.body,
      global: {
        stubs: {
          ...commonStubs,
        },
      },
    });
  };

  beforeEach(() => {
    if (!Element.prototype.scrollIntoView) {
      Element.prototype.scrollIntoView = vi.fn() as any;
    }
    vi.spyOn(Element.prototype, 'scrollIntoView').mockImplementation(() => {});
  });

  afterEach(() => {
    wrapper?.unmount();
    vi.restoreAllMocks();
  });

  describe('rendering', () => {
    it('mounts without errors', () => {
      wrapper = createWrapper();
      expect(wrapper.find('[role="combobox"]').exists()).toBe(true);
    });

    it('shows placeholder in input when no item is selected', () => {
      wrapper = createWrapper();
      expect(wrapper.find('input').attributes('placeholder')).toBe('Select...');
    });

    it('hides input when disabled', () => {
      wrapper = createWrapper({ disabled: true });
      expect(wrapper.find('input').exists()).toBe(false);
    });
  });

  describe('internal logic', () => {
    it('getItemLabel returns correct label', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      expect(vm.getItemLabel(options[0])).toBe('Institution A');
      expect(vm.getItemLabel(options[1])).toBe('Institution B');
    });

    it('getItemValue returns correct value', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      expect(vm.getItemValue(options[0])).toBe(1);
      expect(vm.getItemValue(options[1])).toBe(2);
    });

    it('getItemValue handles null safely', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      expect(vm.getItemValue(null)).toBeNull();
      expect(vm.getItemValue(undefined)).toBeNull();
    });

    it('displayValue formats selected item text', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      expect(vm.displayValue(options[0])).toBe('Institution A');
      expect(vm.displayValue(null)).toBe('');
    });
  });

  describe('filtered options', () => {
    it('returns all options when searchTerm is empty', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      expect(vm.filteredOptions).toEqual(options);
    });

    it('filters options by label when searchTerm is set', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      vm.searchTerm = 'B';
      await nextTick();

      expect(vm.filteredOptions).toHaveLength(1);
      expect(vm.filteredOptions[0]).toEqual(options[1]);
    });

    it('is case-insensitive', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      vm.searchTerm = 'b';
      await nextTick();
      expect(vm.filteredOptions).toHaveLength(1);

      vm.searchTerm = 'B';
      await nextTick();
      expect(vm.filteredOptions).toHaveLength(1);
    });

    it('returns empty array when nothing matches', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      vm.searchTerm = 'xyz';
      await nextTick();

      expect(vm.filteredOptions).toEqual([]);
    });
  });

  describe('open state', () => {
    it('clears searchTerm when opened', async () => {
      wrapper = createWrapper({ modelValue: options[0] });
      await nextTick();

      const vm = wrapper.vm as any;
      // searchTerm is filter-only and starts empty regardless of pre-selected value
      expect(vm.searchTerm).toBe('');

      vm.open = true;
      await nextTick();

      expect(vm.searchTerm).toBe('');
    });

    it('getDisplayValue returns selected label for the closed-state input text', async () => {
      wrapper = createWrapper({ modelValue: options[1] });
      await nextTick();

      const vm = wrapper.vm as any;
      // reka-ui calls displayValue(selectedItem) via its resetSearchTermOnBlur hook;
      // verify the helper produces the correct string
      expect(vm.displayValue(options[1])).toBe('Institution B');
    });

    it('shows all options when opened with a selection', async () => {
      wrapper = createWrapper({ modelValue: options[0] });
      await nextTick();

      const vm = wrapper.vm as any;
      // searchTerm starts empty so all options are available from the start
      expect(vm.filteredOptions).toEqual(options);

      vm.open = true;
      await nextTick();

      expect(vm.filteredOptions).toEqual(options);
    });
  });

  describe('v-model', () => {
    it('emits update:modelValue when selectedItem changes', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      vm.selectedItem = options[1];
      await nextTick();

      const emitted = wrapper.emitted('update:modelValue');
      expect(emitted).toBeTruthy();
      expect(emitted && emitted[0] ? emitted[0][0] : undefined).toEqual(options[1]);
    });

    it('syncs internal selectedItem when modelValue prop changes', async () => {
      wrapper = createWrapper();
      await nextTick();

      await wrapper.setProps({ modelValue: options[2] });
      await nextTick();

      expect((wrapper.vm as any).selectedItem).toEqual(options[2]);
    });

    it('does not infinite loop when modelValue matches internal state', async () => {
      wrapper = createWrapper({ modelValue: options[0] });
      await nextTick();

      const emitCount = wrapper.emitted('update:modelValue')?.length ?? 0;
      await wrapper.setProps({ modelValue: options[0] });
      await nextTick();

      expect(wrapper.emitted('update:modelValue')?.length ?? 0).toBe(emitCount);
    });
  });

  describe('reset', () => {
    it('exposes reset method to clear selection', async () => {
      wrapper = createWrapper({ modelValue: options[0] });
      await nextTick();

      const vm = wrapper.vm as any;
      expect(vm.selectedItem).toEqual(options[0]);

      vm.reset();
      await nextTick();

      expect(vm.selectedItem).toBeNull();
      const emitted = wrapper.emitted('update:modelValue');
      expect(emitted).toBeTruthy();
      expect(emitted!.some((e: any) => e[0] === null)).toBe(true);
    });
  });

  describe('virtualization', () => {
    it('enables virtualization when options exceed threshold', () => {
      const manyOptions = Array.from({ length: 60 }, (_, i) => ({
        id: i + 1,
        name: `Item ${i + 1}`,
      }));

      wrapper = createWrapper({ options: manyOptions, virtualizationThreshold: 50 });
      expect((wrapper.vm as any).shouldVirtualize).toBe(true);
    });

    it('disables virtualization when options are below threshold', () => {
      wrapper = createWrapper();
      expect((wrapper.vm as any).shouldVirtualize).toBe(false);
    });
  });

  describe('custom slot', () => {
    it('accepts option slot', () => {
      wrapper = mount(SingleSelect, {
        props: {
          modelValue: null,
          options,
          labelField: 'name',
          valueField: 'id',
          placeholder: 'Select...',
        },
        slots: {
          option: '<template #option="{ item }"><span class="custom-option">{{ item.name }}</span></template>',
        },
        attachTo: document.body,
        global: { stubs: { ...commonStubs } },
      });

      expect(wrapper.vm.$slots.option).toBeDefined();
    });
  });
});
