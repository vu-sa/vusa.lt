import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import { TimePicker } from '@/Components/ui/time-picker';

describe('TimePicker', () => {
  it('keeps the same input room whether or not a time is set, so the field never narrows', async () => {
    const wrapper = mount(TimePicker, { props: { clearable: true } });

    expect(wrapper.find('input').classes()).toContain('pr-11');
    expect(wrapper.find('button').exists()).toBe(false);

    await wrapper.setProps({ modelValue: { hour: 14, minute: 30 } });

    expect(wrapper.find('input').classes()).toContain('pr-11');
    expect(wrapper.find('button').classes()).toContain('absolute');

    await wrapper.find('button').trigger('click');
    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([undefined]);
  });
});

describe('TimePicker typing', () => {
  const type = async (wrapper: ReturnType<typeof mount>, value: string) => {
    const input = wrapper.find('input');
    await input.setValue(value);
    return input.element as HTMLInputElement;
  };

  it('keeps only digits and one colon', async () => {
    const wrapper = mount(TimePicker);

    expect((await type(wrapper, '1a4:3b0:')).value).toBe('14:30');
  });

  it('saves a full time as soon as it is typed, off the minute step', async () => {
    const wrapper = mount(TimePicker, { props: { minuteStep: 5 } });

    await type(wrapper, '14:32');

    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([{ hour: 14, minute: 32 }]);
  });

  it.each([
    ['9', '09:00', { hour: 9, minute: 0 }],
    ['14', '14:00', { hour: 14, minute: 0 }],
    ['930', '09:30', { hour: 9, minute: 30 }],
    ['1430', '14:30', { hour: 14, minute: 30 }],
    ['9:30', '09:30', { hour: 9, minute: 30 }],
  ])('completes %s to %s when leaving the field', async (typed, shown, time) => {
    const wrapper = mount(TimePicker);

    const input = await type(wrapper, typed);
    await wrapper.find('input').trigger('blur');

    expect(input.value).toBe(shown);
    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([time]);
  });

  it.each(['25', '14:3', '1470'])('puts back the saved time when %s is not a time', async (typed) => {
    const wrapper = mount(TimePicker, { props: { modelValue: { hour: 10, minute: 15 } } });

    const input = await type(wrapper, typed);
    await wrapper.find('input').trigger('blur');

    expect(input.value).toBe('10:15');
    expect(wrapper.emitted('update:modelValue')).toBeUndefined();
  });

  it('clears a clearable time when the field is emptied, and restores a required one', async () => {
    const clearable = mount(TimePicker, { props: { modelValue: { hour: 10, minute: 15 }, clearable: true } });
    await type(clearable, '');
    await clearable.find('input').trigger('blur');
    expect(clearable.emitted('update:modelValue')?.at(-1)).toEqual([undefined]);

    const required = mount(TimePicker, { props: { modelValue: { hour: 10, minute: 15 } } });
    const input = await type(required, '');
    await required.find('input').trigger('blur');
    expect(input.value).toBe('10:15');
    expect(required.emitted('update:modelValue')).toBeUndefined();
  });
});

it('labels the input itself, so a form label and screen readers reach the field', () => {
  const wrapper = mount(TimePicker, { attrs: { 'id': 'start', 'aria-label': 'Pradžia' } });

  expect(wrapper.find('input').attributes()).toMatchObject({ 'id': 'start', 'aria-label': 'Pradžia' });
});

describe('TimePicker suggestions', () => {
  const options = (wrapper: ReturnType<typeof mount>) => wrapper.findAll('option').map(option => option.attributes('value'));

  it('offers the whole day by default', () => {
    const list = options(mount(TimePicker, { props: { minuteStep: 30 } }));

    expect(list[0]).toBe('00:00');
    expect(list.at(-1)).toBe('23:30');
  });

  it('starts at the anchor, rounded down to the step, and runs the given hours forward', () => {
    const list = options(mount(TimePicker, { props: { minuteStep: 15, suggestFrom: { hour: 14, minute: 10 }, suggestionHours: 2 } }));

    expect(list[0]).toBe('14:00');
    expect(list.at(-1)).toBe('16:00');
  });

  it('stops at the end of the day', () => {
    const list = options(mount(TimePicker, { props: { minuteStep: 30, suggestFrom: { hour: 22, minute: 0 } } }));

    expect(list.at(-1)).toBe('23:30');
  });
});
