import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import type { InertiaForm } from '@inertiajs/vue3';
import { ComboboxRoot } from 'reka-ui';
import { defineComponent, markRaw, nextTick } from 'vue';

import ProblemForm from '@/Components/AdminForms/ProblemForm.vue';
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

describe('ProblemForm.vue — mobile field spacing', () => {
  it('gives the paired tenant/status and date fields a row gap, not just a column gap', () => {
    // Regression: `gap-x-4` alone leaves zero space between the two FormFieldWrappers
    // once `lg:grid-cols-2` collapses to a single column below the lg breakpoint —
    // their labels/inputs touched on mobile. `gap-4` keeps the same visual gap at
    // lg+ (only one row exists there, so gap-y is never applied) while fixing mobile.
    const wrapper = mountForm();

    const pairedGrids = wrapper.findAll('.grid.lg\\:grid-cols-2');
    expect(pairedGrids.length).toBe(2);

    for (const grid of pairedGrids) {
      expect(grid.classes()).toContain('gap-4');
      expect(grid.classes()).not.toContain('gap-x-4');
    }
  });
});

describe('ProblemForm.vue — field requirements', () => {
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

    expect(wrapper.get('[data-testid="problem-required-note"]').text()).toBe('problems.form.instructions');
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

describe('ProblemForm.vue — responsible member picker', () => {
  it('shows server matches without a second local filter and saves the selected user', async () => {
    const wrapper = mountForm();
    const picker = wrapper.findComponent(ComboboxRoot);

    expect(picker.props('ignoreFilter')).toBe(true);
    expect(picker.props('openOnFocus')).toBe(true);
    expect(picker.props('openOnClick')).toBe(true);

    picker.vm.$emit('update:modelValue', { id: 'user-1', name: 'Jonas Jonaitis' });
    await nextTick();

    expect((wrapper.vm as unknown as { form: { responsible_user_id: string | null } }).form.responsible_user_id).toBe('user-1');
  });
});
