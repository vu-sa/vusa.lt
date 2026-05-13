import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import NumberField from '@/Components/ui/number-field/NumberField.vue';

describe('NumberField.vue', () => {
  it('renders empty input and enables + when modelValue is null', () => {
    const wrapper = mount(NumberField, { props: { modelValue: null, min: 1 } });
    const input = wrapper.find('input');
    expect(input.element.value).toBe('');
    const [minus, plus] = wrapper.findAll('button');
    expect(minus.attributes('disabled')).toBeDefined();
    expect(plus.attributes('disabled')).toBeUndefined();
  });

  it('renders empty input and enables + when modelValue is undefined', () => {
    const wrapper = mount(NumberField, { props: { modelValue: undefined, min: 1 } });
    const input = wrapper.find('input');
    expect(input.element.value).toBe('');
    const [, plus] = wrapper.findAll('button');
    expect(plus.attributes('disabled')).toBeUndefined();
  });

  it('renders numeric value and enables both buttons when in range', () => {
    const wrapper = mount(NumberField, { props: { modelValue: 5, min: 1, max: 10 } });
    expect(wrapper.find('input').element.value).toBe('5');
    const [minus, plus] = wrapper.findAll('button');
    expect(minus.attributes('disabled')).toBeUndefined();
    expect(plus.attributes('disabled')).toBeUndefined();
  });

  it('disables - when modelValue equals min', () => {
    const wrapper = mount(NumberField, { props: { modelValue: 0, min: 0 } });
    expect(wrapper.find('input').element.value).toBe('0');
    const [minus] = wrapper.findAll('button');
    expect(minus.attributes('disabled')).toBeDefined();
  });

  it('clicking + when modelValue is null emits min', async () => {
    const wrapper = mount(NumberField, { props: { modelValue: null, min: 1 } });
    const [, plus] = wrapper.findAll('button');
    await plus.trigger('click');
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([1]);
  });

  it('clicking + on a number emits incremented value', async () => {
    const wrapper = mount(NumberField, { props: { modelValue: 3, min: 1 } });
    const [, plus] = wrapper.findAll('button');
    await plus.trigger('click');
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([4]);
  });

  it('clearing the input emits null', async () => {
    const wrapper = mount(NumberField, { props: { modelValue: 5, min: 0 } });
    const input = wrapper.find('input');
    await input.setValue('');
    await input.trigger('input');
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([null]);
  });
});
