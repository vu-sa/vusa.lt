import { afterEach, describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import type { InertiaForm } from '@inertiajs/vue3';
import { defineComponent, markRaw, nextTick } from 'vue';

import ProblemForm from '@/Components/AdminForms/ProblemForm.vue';
import UserSearchField from '@/Features/Admin/AdminSearch/Components/Select/UserSearchField.vue';
import type { NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { createMockForm } from '@/tests/helpers/createMockForm';
import { commonStubs } from '@/tests/stubs';

const CollectionSelectDialogStub = defineComponent({
  name: 'CollectionSelectDialog',
  props: {
    open: Boolean,
    collection: String,
    multiple: Boolean,
    baseFilterBy: String,
    initialHits: Array,
  },
  emits: ['update:open', 'confirm'],
  template: '<div><slot name="trigger" /></div>',
});

const stubs = {
  ...commonStubs,
  AdminForm: { template: '<form @submit.prevent><slot /></form>' },
  FormElement: { template: '<section><slot name="title" /><slot name="description" /><slot /></section>' },
  MultiLocaleInput: { template: '<div />' },
  MultiLocaleTiptapFormItem: { template: '<div />' },
  MultiSelect: { template: '<div />' },
  CollectionSelectDialog: CollectionSelectDialogStub,
  Select: { template: '<div><slot /></div>' },
  SelectContent: { template: '<div><slot /></div>' },
  SelectItem: { template: '<div><slot /></div>' },
  SelectTrigger: { template: '<div><slot /></div>' },
  SelectValue: { template: '<div />' },
};

const emptyProblem = () => ({
  id: undefined,
  title: { lt: '', en: '' },
  description: { lt: '', en: '' },
  solution: { lt: '', en: '' },
  steps_taken: { lt: '', en: '' },
  tenant_id: null,
  responsible_user_id: null,
  occurred_at: '',
  resolved_at: null,
  status: 'open',
  categories: [],
  institutions: [],
});

const mountForm = (overrides: Record<string, unknown> = {}) => mount(ProblemForm, {
  props: {
    form: createMockForm(emptyProblem()) as unknown as InertiaForm<ReturnType<typeof emptyProblem>>,
    tenants: [],
    categories: [],
    institutions: [],
    ...overrides,
  },
  global: { stubs },
});

const institutionHit = (id: string, title: string): NormalizedSearchHit => ({
  id: `institutions-${id}`,
  recordId: id,
  collection: 'institutions',
  icon: markRaw(defineComponent({ template: '<i />' })),
  title,
  raw: {},
});

describe('ProblemForm.vue — calendar dates', () => {
  afterEach(() => vi.useRealTimers());

  it.each(['2026-03-15', '2026-03-15T00:00:00+02:00'])('preserves the existing occurred date %s', (occurred_at) => {
    const wrapper = mountForm({ problem: { ...emptyProblem(), occurred_at, resolved_at: '2026-03-16' } });

    expect((wrapper.get('#problem-occurred-at').element as HTMLInputElement).value).toBe('2026-03-15');
    expect((wrapper.get('#problem-resolved-at').element as HTMLInputElement).value).toBe('2026-03-16');
    wrapper.unmount();
  });

  it('defaults to today in Vilnius just after midnight', () => {
    vi.useFakeTimers();
    vi.setSystemTime(new Date('2026-03-14T22:30:00Z'));
    const wrapper = mountForm();

    expect((wrapper.get('#problem-occurred-at').element as HTMLInputElement).value).toBe('2026-03-15');
    wrapper.unmount();
  });
});

describe('ProblemForm.vue — mobile field spacing', () => {
  it('gives the paired date fields a row gap, not just a column gap', () => {
    // `gap-x-4` alone leaves no space between the fields once `lg:grid-cols-2` collapses on mobile.
    const wrapper = mountForm();

    const pairedGrids = wrapper.findAll('.grid.lg\\:grid-cols-2');
    expect(pairedGrids.length).toBe(1);

    for (const grid of pairedGrids) {
      expect(grid.classes()).toContain('gap-4');
      expect(grid.classes()).not.toContain('gap-x-4');
    }
  });
});

describe('ProblemForm.vue — field requirements', () => {
  it('links internal support requests to the support request list', () => {
    const wrapper = mountForm();
    const supportLink = wrapper.findAll('a').find(link => link.text() === 'problems.internal_support.link');

    expect(supportLink?.attributes('href')).toBe(route('mySupportRequests.index'));
    expect(wrapper.text()).toContain('problems.internal_support.guidance');
  });

  it('explains the asterisk once and leaves optional field names unmarked', () => {
    const wrapper = mountForm();
    const optionalFieldIds = [
      'problem-steps-taken',
      'problem-solution',
      'problem-responsible-user',
      'problem-resolved-at',
      'problem-categories',
      'problem-institutions',
    ];

    expect(wrapper.get('[data-testid="form-page-required-note"]').text()).toBe('forms.required_note');
    expect(wrapper.text()).not.toContain('problems.form.translation_requirement');

    for (const id of optionalFieldIds) {
      const label = wrapper.get(`label[for="${id}"]`);
      expect(label.text()).not.toContain('neprivaloma');
      expect(label.text()).not.toContain('*');
    }

    expect(wrapper.get('label[for="problem-title"]').text()).toContain('*');
    expect(wrapper.get('label[for="problem-description"]').text()).toContain('*');
  });

  it('does not mark the current language as required when the other language is filled', () => {
    const form = createMockForm({
      ...emptyProblem(),
      title: { lt: '', en: 'Problem title' },
      description: { lt: '', en: 'Problem description' },
    });
    const wrapper = mountForm({ form });

    expect(wrapper.get('label[for="problem-title"]').text()).not.toContain('*');
    expect(wrapper.get('label[for="problem-description"]').text()).not.toContain('*');
  });
});

describe('ProblemForm.vue — institution picker', () => {
  it('preserves numeric tenant ids and clears institutions when the tenant changes', async () => {
    const wrapper = mountForm({
      problem: { ...emptyProblem(), tenant_id: 3, institutions: ['inst-1'] },
      tenants: [{ id: 3, shortname: 'MIF' }, { id: 4, shortname: 'FF' }],
      institutions: [{ id: 'inst-1', name: 'Faculty council', tenant_id: 3 }],
    });
    const { form } = wrapper.vm as unknown as {
      form: { tenant_id: number | null; institutions: string[] };
    };

    await wrapper.get('select#problem-tenant').setValue('4');
    expect(form.tenant_id).toBe(4);
    expect(form.institutions).toEqual([]);
    expect(wrapper.findComponent(CollectionSelectDialogStub).props('initialHits')).toEqual([]);
    await wrapper.get('form').trigger('submit');
    expect(wrapper.emitted('submit:form')?.[0]?.[0]).toMatchObject({ tenant_id: 4, institutions: [] });
    wrapper.unmount();
  });

  it('uses the searchable collection picker with saved institutions and tenant scope', async () => {
    const form = createMockForm({ ...emptyProblem(), tenant_id: 3, institutions: ['inst-1'] });
    const wrapper = mountForm({
      form,
      tenants: [{ id: 3, shortname: 'MIF' }, { id: 4, shortname: 'VU SA' }],
      institutions: [{ id: 'inst-1', name: 'Faculty council', tenant_id: 3 }],
    });

    const picker = wrapper.findComponent(CollectionSelectDialogStub);
    expect(picker.props('collection')).toBe('institutions');
    expect(picker.props('multiple')).toBe(true);
    expect(picker.props('baseFilterBy')).toBe('tenant_ids:=[3]');
    expect(picker.props('initialHits')[0].recordId).toBe('inst-1');
    expect(wrapper.find('#problem-institutions').text()).toContain('Faculty council');

    picker.vm.$emit('confirm', [institutionHit('inst-2', 'Student council')]);
    await nextTick();

    expect((wrapper.vm as unknown as { form: { institutions: string[] } }).form.institutions).toEqual(['inst-2']);
    expect(wrapper.find('#problem-institutions').text()).toContain('Student council');
  });
});

describe('ProblemForm.vue — status selection', () => {
  it('submits the status key and keeps its error-summary target focusable', async () => {
    const wrapper = mountForm({ problem: emptyProblem() });
    const { form } = wrapper.vm as unknown as { form: { status: string } };
    const status = wrapper.get('#problem-status');

    expect(status.attributes('tabindex')).toBe('-1');
    await status.findAll('button')[2].trigger('click');
    expect(form.status).toBe('resolved');
    expect(status.findAll('button')[2].classes()).toContain('normal-case');
    await wrapper.get('form').trigger('submit');
    expect(wrapper.emitted('submit:form')?.[0]?.[0]).toMatchObject({ status: 'resolved' });
    wrapper.unmount();
  });
});

describe('ProblemForm.vue — responsible member picker', () => {
  it('uses scoped user search and saves or clears the selected user', async () => {
    const wrapper = mountForm();
    const picker = wrapper.getComponent(UserSearchField);

    expect(picker.props('permission')).toBe('problems.create.padalinys');
    expect(picker.props('scope')).toBeUndefined();
    expect(wrapper.get('input#problem-responsible-user').attributes('role')).toBe('combobox');

    picker.vm.$emit('update:modelValue', { id: 'user-1', name: 'Jonas Jonaitis' });
    await nextTick();

    expect((wrapper.vm as unknown as { form: { responsible_user_id: string | null } }).form.responsible_user_id).toBe('user-1');
    expect(wrapper.get('[data-testid="member-search-selected"]').text()).toContain('Jonas Jonaitis');

    await wrapper.get('[data-testid="member-search-selected"] button').trigger('click');
    expect((wrapper.vm as unknown as { form: { responsible_user_id: string | null } }).form.responsible_user_id).toBeNull();
    wrapper.unmount();
  });
});
