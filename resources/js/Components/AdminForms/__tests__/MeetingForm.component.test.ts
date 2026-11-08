import { useForm } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { createMockForm, type MockForm } from '@/tests/helpers/createMockForm';
import { MeetingKind, MeetingType } from '@/Types/enums';
import { useMeetingForm } from '@/Composables/useMeetingForm';
import { commonStubs } from '@/tests/stubs';

import MeetingForm from '../MeetingForm.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

type Meeting = InstanceType<typeof MeetingForm>['$props']['meeting'];

const meeting: Meeting = {
  id: 'meet-1',
  start_time: '2026-05-14 10:00:00',
  type: MeetingType.InPerson,
  meeting_kind: null,
  inherited_meeting_kind: MeetingKind.Assembly,
  created_at: '2026-05-01T09:00:00.000Z',
  updated_at: '2026-05-01T09:00:00.000Z',
  description: { lt: 'Lietuviškas aprašymas', en: 'English description' },
};

const SheetFormStub = {
  name: 'SheetForm',
  props: ['open', 'dirty', 'processing'],
  template: '<form v-if="open" data-testid="meeting-sheet" @submit.prevent="$emit(\'submit\')"><slot /></form>',
};

let form: MockForm;
let transform: (data: Record<string, unknown>) => unknown;

function factory(overrides: Partial<Meeting> = {}) {
  const record = { ...meeting, ...overrides };
  form = createMockForm({
    start_time: new Date(record.start_time),
    type: record.type,
    meeting_kind: record.meeting_kind,
    description: { ...record.description },
  });
  form.transform.mockImplementation((callback) => {
    transform = callback;
    return form;
  });
  vi.mocked(useForm).mockReturnValue(form as never);

  return mount(MeetingForm, {
    props: { meeting: record, open: true },
    global: { stubs: { ...commonStubs, SheetForm: SheetFormStub } },
  });
}

beforeEach(() => vi.clearAllMocks());

describe('MeetingForm', () => {
  it('keeps both description locales when switching and saving', async () => {
    const wrapper = factory();
    expect((wrapper.find('textarea').element as HTMLTextAreaElement).value).toBe('Lietuviškas aprašymas');

    await wrapper.findComponent({ name: 'SimpleLocaleButton' }).vm.$emit('update:locale', 'en');
    expect((wrapper.find('textarea').element as HTMLTextAreaElement).value).toBe('English description');
    expect(form.patch).not.toHaveBeenCalled();

    await wrapper.find('textarea').setValue('Updated English');
    await wrapper.find('form').trigger('submit');

    expect(transform(form.data())).toMatchObject({
      description: { lt: 'Lietuviškas aprašymas', en: 'Updated English' },
      type: 'in-person',
    });
    expect(form.patch).toHaveBeenCalledWith(expect.any(String), expect.objectContaining({ preserveScroll: true }));
  });

  it('sends the selected meeting kind', async () => {
    const wrapper = factory();
    await wrapper.get('select#meeting-kind').setValue(MeetingKind.Conference);
    await wrapper.find('form').trigger('submit');

    expect(transform(form.data())).toMatchObject({ meeting_kind: MeetingKind.Conference });
  });

  it('sends null when the institution default is selected', async () => {
    const wrapper = factory({ meeting_kind: MeetingKind.Conference });
    const select = wrapper.get('select#meeting-kind');
    const inherited = select.findAll('option')[0];
    await select.setValue(inherited.attributes('value'));
    await wrapper.find('form').trigger('submit');

    expect(transform(form.data())).toMatchObject({ meeting_kind: null });
  });

  it('hides the kind select for decisions by email', () => {
    const wrapper = factory({ type: MeetingType.Email });
    expect(wrapper.find('select#meeting-kind').exists()).toBe(false);
  });

  it('refreshes the kind defaults when the sheet opens', async () => {
    const wrapper = factory();
    await wrapper.setProps({ open: false });
    await wrapper.setProps({ meeting: { ...meeting, meeting_kind: MeetingKind.Conference }, open: true });

    expect(form.defaults).toHaveBeenCalledWith(expect.objectContaining({ meeting_kind: MeetingKind.Conference }));
  });

  it('requires a date before submitting', async () => {
    const wrapper = factory();
    form.start_time = undefined;

    await wrapper.find('form').trigger('submit');

    expect(form.setError).toHaveBeenCalledWith('start_time', expect.any(String));
    expect(form.patch).not.toHaveBeenCalled();
  });

  it('closes the sheet only after a successful save', async () => {
    const wrapper = factory();
    await wrapper.find('form').trigger('submit');

    expect(wrapper.emitted('update:open')).toBeUndefined();
    const options = form.patch.mock.calls[0][1] as { onSuccess: () => void };
    options.onSuccess();
    expect(wrapper.emitted('update:open')?.[0]).toEqual([false]);
  });

  it('accepts a localized string as the Lithuanian description', () => {
    const { getInitialValues } = useMeetingForm();
    expect(getInitialValues({ ...meeting, description: 'Senas formatas' }).description)
      .toEqual({ lt: 'Senas formatas', en: '' });
  });
});
