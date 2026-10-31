import { mount } from '@vue/test-utils';
import { CalendarDateTime } from '@internationalized/date';
import { computed } from 'vue';
import { afterEach, describe, expect, it, vi } from 'vitest';

import DatePicker from '../DatePicker.vue';
import DateTimePicker from '../DateTimePicker.vue';
import DateRangePicker from '../DateRangePicker.vue';
import TimePicker from '../../time-picker/TimePicker.vue';

import { useCoarsePointer } from '@/Composables/useCoarsePointer';

vi.mock('@/Composables/useCoarsePointer', () => ({
  useCoarsePointer: vi.fn(() => computed(() => false)),
}));

afterEach(() => vi.mocked(useCoarsePointer).mockReturnValue(computed(() => false)));

describe('admin date and time pickers', () => {
  it('keeps partial keyboard input visible until a valid date commits', async () => {
    const wrapper = mount(DatePicker, {
      props: {
        modelValue: '2026-09-19',
        'onUpdate:modelValue': value => { void wrapper.setProps({ modelValue: value }); },
      },
    });
    const input = wrapper.find('input');

    for (const value of ['2026-09-1', '2026-09-', '', '2', '20', '202', '2026', '2026-', '2026-1', '2026-10', '2026-10-', '2026-10-2']) {
      await input.setValue(value);

      expect(input.element.value).toBe(value);
      expect(wrapper.emitted('update:modelValue')).toBeUndefined();
      expect(wrapper.emitted('change')).toBeUndefined();
    }

    await input.setValue('2026-10-20');
    await input.trigger('blur');

    expect(input.element.value).toBe('2026-10-20');
    expect(wrapper.emitted('update:modelValue')).toEqual([[new Date('2026-10-20T12:00:00Z')]]);
    expect(wrapper.emitted('change')).toEqual([[new Date('2026-10-20T12:00:00Z')]]);
  });

  it.each(['2026-09-1', '2026-02-30', '2026-09-20extra'])('reverts invalid text %s on blur without clearing the model', async (value) => {
    const wrapper = mount(DatePicker, { props: { modelValue: '2026-09-19' } });
    const input = wrapper.find('input');

    await input.setValue(value);
    expect(input.element.value).toBe(value);
    await input.trigger('blur');

    expect(input.element.value).toBe('2026-09-19');
    expect(wrapper.emitted('update:modelValue')).toBeUndefined();
    expect(wrapper.emitted('blur')).toHaveLength(1);
  });

  it('commits an intentionally emptied text input on blur', async () => {
    const wrapper = mount(DatePicker, { props: { modelValue: '2026-09-19', clearable: true } });
    const input = wrapper.find('input');

    await input.setValue('');
    expect(wrapper.emitted('update:modelValue')).toBeUndefined();
    await input.trigger('blur');

    expect(wrapper.emitted('update:modelValue')).toEqual([[undefined]]);
    expect(wrapper.emitted('change')).toEqual([[undefined]]);
  });

  it('clears immediately with the clear button', async () => {
    const wrapper = mount(DatePicker, { props: { modelValue: '2026-09-19', clearable: true } });

    await wrapper.find('button[aria-label="Išvalyti"]').trigger('click');

    expect(wrapper.find('input').element.value).toBe('');
    expect(wrapper.emitted('update:modelValue')).toEqual([[undefined]]);
  });

  it('updates a draft when the parent replaces the date', async () => {
    const wrapper = mount(DatePicker, { props: { modelValue: '2026-09-19' } });

    await wrapper.find('input').setValue('2026-09-');
    await wrapper.setProps({ modelValue: '2025-07-01' });

    expect(wrapper.find('input').element.value).toBe('2025-07-01');
  });

  it('preserves immediate date and clear events for the native input', async () => {
    vi.mocked(useCoarsePointer).mockReturnValue(computed(() => true));
    const wrapper = mount(DatePicker);
    const input = wrapper.find('input[type="date"]');

    await input.setValue('2026-09-19');
    await input.setValue('');

    const events = [[new Date('2026-09-19T12:00:00Z')], [undefined]];
    expect(wrapper.emitted('update:modelValue')).toEqual(events);
    expect(wrapper.emitted('change')).toEqual(events);
  });

  it('preserves the native range picker dates and time components', async () => {
    vi.mocked(useCoarsePointer).mockReturnValue(computed(() => true));
    const wrapper = mount(DateRangePicker, {
      props: {
        modelValue: {
          start: new CalendarDateTime(2026, 9, 19, 9, 30),
          end: new CalendarDateTime(2026, 9, 20, 18),
        },
      },
    });
    const inputs = wrapper.findAll('input[type="date"]');

    await inputs[0].setValue('2026-09-18');

    const events = [[{
      start: new CalendarDateTime(2026, 9, 18, 9, 30),
      end: new CalendarDateTime(2026, 9, 20, 18),
    }]];
    expect(wrapper.emitted('update:modelValue')).toEqual(events);
    expect(wrapper.emitted('change')).toEqual(events);

    await inputs[1].setValue('');

    expect(wrapper.emitted('update:modelValue')).toEqual(events);
  });

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

it('puts form validation attributes on the date input', () => {
  const wrapper = mount(DatePicker, { attrs: { 'required': true, 'aria-invalid': true, 'aria-describedby': 'date-error' } });

  expect(wrapper.find('input').attributes()).toMatchObject({ 'required': '', 'aria-invalid': 'true', 'aria-describedby': 'date-error' });
  expect(wrapper.attributes('aria-describedby')).toBeUndefined();
});
