import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { Star } from 'lucide-vue-next';
import { h } from 'vue';

import NativeSelect from '../NativeSelect.vue';

describe('NativeSelect.vue', () => {
  const options = [
    { value: 1, label: 'VU SA MIF' },
    { value: 2, label: 'VU SA FF' },
    { value: 3, label: 'VU SA TF' },
  ];

  it('renders a native select element with provided options', () => {
    const wrapper = mount(NativeSelect, {
      props: {
        options,
        modelValue: 1,
      },
    });

    const select = wrapper.find('select');
    expect(select.exists()).toBe(true);

    const renderedOptions = wrapper.findAll('option');
    expect(renderedOptions).toHaveLength(3);
    expect(renderedOptions[0].text()).toBe('VU SA MIF');
    expect(renderedOptions[0].element.value).toBe('1');
  });

  it('updates modelValue on selection change', async () => {
    const wrapper = mount(NativeSelect, {
      props: {
        options,
        modelValue: 1,
      },
    });

    const select = wrapper.find('select');
    await select.setValue('2');

    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([2]);
    expect(wrapper.emitted('change')).toBeTruthy();
  });

  it('renders a disabled placeholder option when specified', () => {
    const wrapper = mount(NativeSelect, {
      props: {
        options,
        placeholder: 'Pasirinkti padalinį...',
        placeholderValue: '',
        modelValue: '',
      },
    });

    const renderedOptions = wrapper.findAll('option');
    expect(renderedOptions).toHaveLength(4);
    expect(renderedOptions[0].text()).toBe('Pasirinkti padalinį...');
    expect(renderedOptions[0].attributes('disabled')).toBeDefined();
  });

  it('renders leading icon when provided', () => {
    const wrapper = mount(NativeSelect, {
      props: {
        options,
        icon: Star,
      },
    });

    expect(wrapper.findComponent(Star).exists()).toBe(true);
    expect(wrapper.find('select').classes()).toContain('pl-9');
  });

  it('applies compact size classes when size is sm', () => {
    const wrapper = mount(NativeSelect, {
      props: {
        options,
        size: 'sm',
      },
    });

    expect(wrapper.find('select').classes()).toContain('h-8');
  });

  it('supports raw slots for options', () => {
    const wrapper = mount(NativeSelect, {
      slots: {
        default: () => [
          h('option', { value: 'custom-1' }, 'Custom 1'),
          h('option', { value: 'custom-2' }, 'Custom 2'),
        ],
      },
    });

    const renderedOptions = wrapper.findAll('option');
    expect(renderedOptions).toHaveLength(2);
    expect(renderedOptions[0].text()).toBe('Custom 1');
  });
});
