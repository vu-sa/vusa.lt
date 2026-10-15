import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import AgendaItemVotesSheetForm from '@/Components/AgendaItems/AgendaItemVotesSheetForm.vue';
import { createMockForm } from '@/tests/helpers/createMockForm';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const SheetFormStub = {
  name: 'SheetForm',
  props: ['open', 'dirty', 'processing'],
  emits: ['submit', 'cancel', 'update:open'],
  template: '<form @submit.prevent="$emit(\'submit\')"><slot /><button class="save" type="submit" /></form>',
};

const vote = (id: string, overrides: Record<string, unknown> = {}) => ({
  id,
  is_main: false,
  is_consensus: false,
  title: { lt: `Balsavimas ${id}`, en: '' },
  note: { lt: '', en: '' },
  decision: 'positive',
  student_vote: null,
  student_benefit: null,
  ...overrides,
});

function factory() {
  const form = createMockForm({
    votes: [vote('a', { is_main: true }), vote('b', { decision: 'negative' })],
  });
  const saveThen = vi.fn((callback: () => void) => callback());
  const wrapper = mount(AgendaItemVotesSheetForm, {
    props: { open: true, form, saveThen },
    global: { stubs: { ...commonStubs, SheetForm: SheetFormStub, LocaleFlag: { template: '<span />' } } },
  });

  return { wrapper, form, saveThen };
}

describe('AgendaItemVotesSheetForm', () => {
  it('leaves the live votes alone until saved, then writes order, main and titles back with their answers', async () => {
    const { wrapper, form, saveThen } = factory();

    await wrapper.findAll('[aria-label="Perkelti žemyn"]')[0]!.trigger('click');
    await wrapper.findAll('[data-testid="manage-vote-main"]')[0]!.trigger('click');
    await wrapper.findAll('input')[0]!.setValue('Pirmas');
    expect(form.votes.map((v: { id: string }) => v.id)).toEqual(['a', 'b']);

    await wrapper.find('form').trigger('submit');

    expect(form.votes).toMatchObject([
      { id: 'b', is_main: true, decision: 'negative', title: { lt: 'Pirmas' } },
      { id: 'a', is_main: false, decision: 'positive' },
    ]);
    expect(saveThen).toHaveBeenCalledOnce();
    expect(wrapper.emitted('update:open')).toEqual([[false]]);
  });

  it('promotes the next vote when the main one is removed', async () => {
    const { wrapper, form } = factory();

    await wrapper.findAll('[aria-label="meetings.item.remove_vote"]')[0]!.trigger('click');
    await wrapper.find('form').trigger('submit');

    expect(form.votes).toHaveLength(1);
    expect(form.votes[0]).toMatchObject({ id: 'b', is_main: true });
  });
});
