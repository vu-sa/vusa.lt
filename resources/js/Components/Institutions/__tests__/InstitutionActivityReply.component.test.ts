import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';
import InstitutionActivityReply from '../InstitutionActivityReply.vue';
import { DatePicker } from '@/Components/ui/date-picker';
import { TimePicker } from '@/Components/ui/time-picker';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
const props = {
  action: '/reply?signature=signed', csrf: 'csrf-token', start: '2026-09-01', end: '2026-10-03', locale: 'lt', chosen: 'met',
  choices: [{ value: 'met', label: 'Add meetings', hint: '', submit: 'Save' }, { value: 'complete', label: 'Everything recorded', hint: 'Confirm', submit: 'Confirm' }],
  types: [{ value: 'in-person', label: 'In person' }, { value: 'email', label: 'Email' }],
  text: { add_meeting: 'Add row', remove_meeting: 'Remove row' }, errors: { 'meetings.0.time': 'Time required' }, knownDates: ['2026-09-15'],
  oldMeetings: [{ date: '2026-09-20', type: 'in-person', time: '' }, { date: '2026-09-25', type: 'email' }],
};

describe('signed activity reply', () => {
  it('preserves rows and errors with signed action CSRF and native POST', () => {
    const wrapper = mount(InstitutionActivityReply, { props, global: { stubs: commonStubs } });
    expect(wrapper.find('form').attributes()).toMatchObject({ method: 'POST', action: props.action });
    expect(wrapper.find('input[name="_token"]').attributes('value')).toBe('csrf-token');
    expect(wrapper.findAllComponents(DatePicker)).toHaveLength(2);
    expect(wrapper.text()).toContain('Time required', '2026-09-15');
    expect(wrapper.find('input[name="meetings[1][date]"]').attributes('value')).toBe('2026-09-25');
  });

  it('wires the locale bounds and 24-hour time and omits time for email decisions', async () => {
    const wrapper = mount(InstitutionActivityReply, { props, global: { stubs: commonStubs } });
    expect(wrapper.findComponent(DatePicker).props('locale')).toBe('lt');
    expect(wrapper.findComponent(DatePicker).props('minDate').toString()).toBe('2026-09-01');
    expect(wrapper.findComponent(DatePicker).props('maxDate').toString()).toBe('2026-10-03');
    expect(wrapper.findAllComponents(TimePicker)).toHaveLength(1);
    await wrapper.find('select').setValue('email');
    expect(wrapper.findAllComponents(TimePicker)).toHaveLength(0);
    expect(wrapper.findAll('input[name$="[time]"]')).toHaveLength(0);
  });

  it('adds rows and exposes the selected and expanded answer', async () => {
    const wrapper = mount(InstitutionActivityReply, { props, global: { stubs: commonStubs } });
    const button = wrapper.findAll('button').find(item => item.text() === 'Add row')!;
    await button.trigger('click');
    expect(wrapper.findAllComponents(DatePicker)).toHaveLength(3);
    await wrapper.find('button[aria-controls="answer-complete"]').trigger('click');
    expect(wrapper.find('input[name="answer"]').attributes('value')).toBe('complete');
    expect(wrapper.find('button[aria-controls="answer-complete"]').attributes('aria-expanded')).toBe('true');
  });
});
