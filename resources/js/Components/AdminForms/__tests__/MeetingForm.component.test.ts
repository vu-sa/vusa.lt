import { useForm } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import MeetingForm from '../MeetingForm.vue';

import { createMockForm, type MockForm } from '@/tests/helpers/createMockForm';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const meeting = {
  id: 'meet-1',
  start_time: '2026-05-14 10:00:00',
  type: 'in-person',
  description: { lt: 'Lietuviškas aprašymas', en: 'English description' },
};

const SheetFormStub = {
  name: 'SheetForm',
  props: ['open', 'dirty', 'processing'],
  template: '<form v-if="open" data-testid="meeting-sheet" @submit.prevent="$emit(\'submit\')"><slot /></form>',
};

let form: MockForm;
let transform: (data: Record<string, unknown>) => unknown;

function factory(overrides: Record<string, unknown> = {}) {
  const record = { ...meeting, ...overrides };
  form = createMockForm({
    start_time: new Date(record.start_time),
    type: record.type,
    description: typeof record.description === 'string'
      ? { lt: record.description, en: '' }
      : { ...record.description },
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
    const wrapper = factory({ description: 'Senas formatas' });
    expect((wrapper.find('textarea').element as HTMLTextAreaElement).value).toBe('Senas formatas');
  });
});
