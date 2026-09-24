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

function factory() {
  const form = createMockForm({
    title: { lt: 'Stipendijos', en: '' },
    brought_by_students: false,
    description: { lt: '', en: '' },
    student_position: { lt: '', en: '' },
    votes: [{ id: 'v1', is_main: true, title: { lt: '', en: '' }, note: { lt: '', en: '' } }],
  });
  const saveThen = vi.fn((callback: () => void) => callback());
  const wrapper = mount(AgendaItemSheetForm, {
    props: { open: true, form, saveThen },
    global: { stubs: { ...commonStubs, SheetForm: SheetFormStub, LocaleFlag: { template: '<span />' } } },
  });

  return { wrapper, form, saveThen };
}

describe('AgendaItemSheetForm', () => {
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
});
