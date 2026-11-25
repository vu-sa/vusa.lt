import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { nextTick } from 'vue';

import PublicEmptyState from '../PublicEmptyState.vue';
import PublicSearchInput from '../PublicSearchInput.vue';
import PublicSortMenu from '../PublicSortMenu.vue';

const passthrough = { template: '<div><slot /></div>' };

describe('PublicSearchInput', () => {
  const mountInput = (modelValue: string) => mount(PublicSearchInput, {
    props: { 'modelValue': modelValue, 'placeholder': 'Ieškoti naujienų', 'onUpdate:modelValue': () => {} },
  });

  it('names the field by its placeholder and offers clearing only with text', async () => {
    const wrapper = mountInput('');
    expect(wrapper.get('input').attributes('aria-label')).toBe('Ieškoti naujienų');
    expect(wrapper.find('button').exists()).toBe(false);

    await wrapper.setProps({ modelValue: 'atstovai' });
    await wrapper.get('button').trigger('click');
    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['']);
    expect(wrapper.emitted('clear')).toHaveLength(1);
  });

  it('submits the query on Enter', async () => {
    const wrapper = mountInput('atstovai');
    await wrapper.get('input').trigger('keydown', { key: 'Enter' });
    expect(wrapper.emitted('submit')).toEqual([['atstovai']]);
  });
});

describe('PublicSortMenu', () => {
  const options = [
    { value: 'relevance', label: 'Pagal aktualumą' },
    { value: 'newest', label: 'Naujausi viršuje' },
  ];
  const mountMenu = (modelValue: string) => mount(PublicSortMenu, {
    props: { modelValue, options, defaultValue: 'relevance' },
    global: { stubs: { Popover: passthrough, PopoverTrigger: passthrough, PopoverContent: passthrough } },
  });

  it('marks the chosen order and emits a new one', async () => {
    const wrapper = mountMenu('relevance');
    const radios = wrapper.findAll('[role="radio"]');
    expect(radios.map(radio => radio.attributes('aria-checked'))).toEqual(['true', 'false']);

    await radios[1]!.trigger('click');
    expect(wrapper.emitted('update:modelValue')).toEqual([['newest']]);
  });

  it('tints the trigger only once moved off the default order', async () => {
    const wrapper = mountMenu('relevance');
    const trigger = () => wrapper.get('[data-slot="public-sort-menu"]');
    expect(trigger().classes()).not.toContain('text-brand');

    await wrapper.setProps({ modelValue: 'newest' });
    await nextTick();
    expect(trigger().classes()).toContain('text-brand');
  });
});

describe('PublicEmptyState', () => {
  it('renders the heading at the requested level with its actions', () => {
    const wrapper = mount(PublicEmptyState, {
      props: { title: 'Naujienų nerasta', description: 'Pabandyk kitą frazę.', headingTag: 'h3' },
      slots: { default: '<button>Išvalyti filtrus</button>' },
    });
    expect(wrapper.get('h3').text()).toBe('Naujienų nerasta');
    expect(wrapper.text()).toContain('Pabandyk kitą frazę.');
    expect(wrapper.get('button').text()).toBe('Išvalyti filtrus');
  });
});
