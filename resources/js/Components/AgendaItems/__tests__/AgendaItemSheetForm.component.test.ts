import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import AgendaItemSheetForm from '@/Components/AgendaItems/AgendaItemSheetForm.vue';
import { createMockForm } from '@/tests/helpers/createMockForm';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const SheetFormStub = {
  name: 'SheetForm',
  props: ['open', 'dirty', 'processing'],
  emits: ['submit', 'cancel', 'update:open'],
  template: '<form @submit.prevent="$emit(\'submit\')"><slot /><slot name="danger-zone" /><button class="save" type="submit" /></form>',
};

function factory(props: Record<string, unknown> = {}) {
  const form = createMockForm({
    title: { lt: 'Stipendijos', en: '' },
    is_private: false,
    public_title: { lt: '', en: '' },
    brought_by_students: false,
    description: { lt: '', en: '' },
    student_position: { lt: '', en: '' },
    votes: [{ id: 'v1', is_main: true, title: { lt: '', en: '' }, note: { lt: '', en: '' } }],
  });
  const saveThen = vi.fn((callback: () => void) => callback());
  const wrapper = mount(AgendaItemSheetForm, {
    props: { open: true, form, saveThen, ...props },
    global: { stubs: { ...commonStubs, SheetForm: SheetFormStub, LocaleFlag: { template: '<span />' } } },
  });

  return { wrapper, form, saveThen };
}

describe('AgendaItemSheetForm', () => {
  it('explains the selected visibility and future publication of a private meeting', async () => {
    const { wrapper } = factory({ isPublic: false });
    expect(wrapper.text()).toContain('meetings.privacy.disabled_lead');
    const details = wrapper.find('[data-testid="agenda-item-privacy-details"]');
    expect(details.element.tagName).toBe('DETAILS');
    expect(details.attributes('open')).toBeUndefined();
    expect(details.find('summary').text()).toContain('meetings.privacy.details_label');
    expect(details.text()).toContain('meetings.privacy.nonpublic_hint');
    await wrapper.findComponent({ name: 'Switch' }).vm.$emit('update:modelValue', true);
    await wrapper.vm.$nextTick();
    expect(wrapper.text()).toContain('meetings.privacy.enabled_lead');
    expect(wrapper.text()).not.toContain('meetings.privacy.disabled_lead');
    await wrapper.setProps({ isPublic: true });
    expect(wrapper.text()).not.toContain('meetings.privacy.nonpublic_hint');
  });

  it('saves privacy with the draft and never previews the original title', async () => {
    const { wrapper, form } = factory({ order: 3 });
    await wrapper.findComponent({ name: 'Switch' }).vm.$emit('update:modelValue', true);
    await wrapper.vm.$nextTick();

    const preview = wrapper.find('[data-testid="agenda-item-public-preview"]');
    expect(preview.text()).toContain('3.');
    expect(preview.text()).toContain('meetings.privacy.hidden_title');
    expect(preview.text()).not.toContain('Stipendijos');
    expect(form.is_private).toBe(false);
    expect(wrapper.text()).toContain('meetings.privacy.audience');
    expect(wrapper.text()).toContain('meetings.privacy.no_personal_data');

    await wrapper.find('#agenda-item-public-title').setValue('Darbo klausimas');
    await wrapper.find('[data-testid="agenda-item-locale-en"]').trigger('click');
    await wrapper.find('#agenda-item-public-title').setValue('Working item');
    await wrapper.find('form').trigger('submit');
    expect(form.is_private).toBe(true);
    expect(form.public_title).toEqual({ lt: 'Darbo klausimas', en: 'Working item' });
  });

  it('discarding the privacy draft leaves the saved visibility untouched', async () => {
    const { wrapper, form } = factory();
    wrapper.findComponent({ name: 'Switch' }).vm.$emit('update:modelValue', true);
    await wrapper.vm.$nextTick();
    wrapper.findComponent({ name: 'SheetForm' }).vm.$emit('cancel');
    await wrapper.vm.$nextTick();
    expect(form.is_private).toBe(false);
    expect(wrapper.find('#agenda-item-public-title').exists()).toBe(false);
  });

  /** The page autosaves every change; typing must not, so the sheet writes a draft back on Išsaugoti. */
  it('leaves the live form alone until saved, then writes back and closes', async () => {
    const { wrapper, form, saveThen } = factory();

    await wrapper.find('#agenda-item-description').setValue('Naujas aprašymas');
    expect(form.description.lt).toBe('');
    expect(wrapper.findComponent({ name: 'SheetForm' }).props('dirty')).toBe(true);

    await wrapper.find('form').trigger('submit');

    expect(form.description.lt).toBe('Naujas aprašymas');
    expect(saveThen).toHaveBeenCalledOnce();
    expect(wrapper.emitted('update:open')).toEqual([[false]]);
  });

  it('writes the English translation after switching language', async () => {
    const { wrapper, form } = factory();

    await wrapper.find('[data-testid="agenda-item-locale-en"]').trigger('click');
    await wrapper.find('#agenda-item-title').setValue('Scholarships');
    await wrapper.find('form').trigger('submit');

    expect(form.title).toEqual({ lt: 'Stipendijos', en: 'Scholarships' });
  });

  it('opens the time suggestions at the meeting, and the end ones at the chosen start', async () => {
    const { wrapper } = factory({ meetingStartTime: '14:00' });
    const [start, end] = wrapper.findAllComponents({ name: 'TimePicker' });

    expect(start!.props('suggestFrom')).toEqual({ hour: 14, minute: 0 });

    await wrapper.find('input#agenda-item-start-time').setValue('15:10');

    expect(end!.props('suggestFrom')).toEqual({ hour: 15, minute: 10 });
  });

  it('prefers the previous item\'s end over the meeting start, and falls back to the morning', () => {
    const following = factory({ meetingStartTime: '14:00', defaultStartTime: '15:30' }).wrapper;
    expect(following.findComponent({ name: 'TimePicker' }).props('suggestFrom')).toEqual({ hour: 15, minute: 30 });

    const unknown = factory().wrapper;
    expect(unknown.findComponent({ name: 'TimePicker' }).props('suggestFrom')).toEqual({ hour: 8, minute: 0 });
  });
});
