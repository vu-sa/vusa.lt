import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import { commonStubs } from '@/tests/stubs';
import DataTableFilter from '@/Components/ui/data-table/DataTableFilter.vue';

const dropdownStubs = {
  DropdownMenu: { template: '<div><slot /></div>' },
  DropdownMenuTrigger: { template: '<div><slot /></div>' },
  DropdownMenuContent: { template: '<div><slot /></div>' },
  DropdownMenuItem: { template: '<div role="menuitem" @click="$emit(\'click\')"><slot /></div>' },
  DropdownMenuSeparator: { template: '<hr>' },
};

const options = [
  { label: 'Vilniaus universitetas', value: 1 },
  { label: 'Filosofijos fakultetas', value: 2 },
  { label: 'Fizikos fakultetas', value: 3 },
];

function mountFilter(props: Record<string, unknown> = {}) {
  return mount(DataTableFilter, {
    props: {
      value: [],
      options,
      multiple: true,
      ...props,
    },
    global: {
      stubs: {
        ...commonStubs,
        ...dropdownStubs,
      },
    },
  });
}

describe('DataTableFilter search', () => {
  it('does not render a search input by default', () => {
    const wrapper = mountFilter();

    expect(wrapper.find('input[type="text"], input:not([type])').exists()).toBe(false);
  });

  it('renders a search input when searchable', () => {
    const wrapper = mountFilter({ searchable: true });

    expect(wrapper.find('input').exists()).toBe(true);
  });

  it('filters options by search term (case-insensitive)', async () => {
    const wrapper = mountFilter({ searchable: true });

    await wrapper.find('input').setValue('fakult');

    const labels = wrapper.findAll('label').map(label => label.text());
    expect(labels).toEqual(['Filosofijos fakultetas', 'Fizikos fakultetas']);
  });

  it('shows an empty state when no options match', async () => {
    const wrapper = mountFilter({ searchable: true });

    await wrapper.find('input').setValue('xyz-no-match');

    expect(wrapper.findAll('label')).toHaveLength(0);
    expect(wrapper.text()).toContain('Nerasta');
  });

  it('keeps selected values hidden by the search term when applying', async () => {
    const wrapper = mountFilter({ searchable: true, value: [1] });

    // Hide the selected "Vilniaus universitetas" option, then select another one
    await wrapper.find('input').setValue('Filosofijos');
    await wrapper.find('button[id="filter-2"], [id="filter-2"]').trigger('click');

    const applyButton = wrapper.findAll('button').find(button => button.text() === 'Apply');
    expect(applyButton).toBeDefined();
    await applyButton!.trigger('click');

    const applied = wrapper.emitted('apply')?.at(-1)?.[0] as number[];
    expect(applied).toContain(1);
    expect(applied).toContain(2);
  });

  it('stops propagation of character keystrokes from the search input', () => {
    const wrapper = mountFilter({ searchable: true });

    // DropdownMenu typeahead listens on ancestors — propagation must be stopped
    const ancestorListener = vi.fn();
    wrapper.element.addEventListener('keydown', ancestorListener);

    const input = wrapper.find('input');
    input.element.dispatchEvent(new KeyboardEvent('keydown', { key: 'a', bubbles: true, cancelable: true }));

    expect(ancestorListener).not.toHaveBeenCalled();
  });

  it('does not stop propagation of Escape', () => {
    const wrapper = mountFilter({ searchable: true });

    const ancestorListener = vi.fn();
    wrapper.element.addEventListener('keydown', ancestorListener);

    const input = wrapper.find('input');
    input.element.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true, cancelable: true }));

    expect(ancestorListener).toHaveBeenCalledTimes(1);
  });

  it('filters options in single-select mode', async () => {
    const wrapper = mountFilter({ searchable: true, multiple: false, value: null });

    await wrapper.find('input').setValue('Vilniaus');

    const items = wrapper.findAll('[role="menuitem"]');
    // One matching option + the always-visible "Clear Selection" item
    expect(items.some(item => item.text() === 'Vilniaus universitetas')).toBe(true);
    expect(items.some(item => item.text() === 'Filosofijos fakultetas')).toBe(false);
  });
});
