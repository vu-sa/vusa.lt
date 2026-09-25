import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import DatePicker from '../DatePicker.vue';
import DateTimePicker from '../DateTimePicker.vue';
import TimePicker from '../../time-picker/TimePicker.vue';

describe('admin date and time pickers', () => {
  it('emits a noon-UTC Date for a typed ISO date', async () => {
    const wrapper = mount(DatePicker);

    await wrapper.find('input').setValue('2026-09-19');

    const date = wrapper.emitted('update:modelValue')?.[0]?.[0] as Date;
    expect(date.toISOString()).toBe('2026-09-19T12:00:00.000Z');
  });

  it.each([
    [undefined, 'h-11', 'size-11'],
    ['sm', 'h-9', 'size-9'],
  ] as const)('sizes the calendar button to the field (size %s)', (size, inputHeight, buttonSize) => {
    const wrapper = mount(DatePicker, { props: { size } });

    expect(wrapper.find('input').classes()).toContain(inputHeight);
    expect(wrapper.find('button').classes()).toContain(buttonSize);
  });

  it('accepts a typed time matching the configured interval', async () => {
    const wrapper = mount(TimePicker, { props: { minuteStep: 15 } });

    await wrapper.find('input').setValue('18:15');

    expect(wrapper.emitted('update:modelValue')).toEqual([[{ hour: 18, minute: 15 }]]);
  });

  it('renders date and time as separate controls', () => {
    const wrapper = mount(DateTimePicker, { props: { modelValue: new Date(2026, 8, 19, 18) } });

    expect(wrapper.findAll('input')).toHaveLength(2);
  });
});
