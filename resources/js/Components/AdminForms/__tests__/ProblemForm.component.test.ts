import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import type { InertiaForm } from '@inertiajs/vue3';

import ProblemForm from '@/Components/AdminForms/ProblemForm.vue';
import { createMockForm } from '@/tests/helpers/createMockForm';
import { commonStubs } from '@/tests/stubs';

const stubs = {
  ...commonStubs,
  AdminForm: { template: '<form @submit.prevent><slot /></form>' },
  FormElement: { template: '<section><slot name="title" /><slot name="description" /><slot /></section>' },
  MultiLocaleInput: { template: '<div />' },
  MultiLocaleTiptapFormItem: { template: '<div />' },
  MultiSelect: { template: '<div />' },
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

const mountForm = () => mount(ProblemForm, {
  props: {
    form: createMockForm(emptyProblem()) as unknown as InertiaForm<ReturnType<typeof emptyProblem>>,
    tenants: [],
    categories: [],
    institutions: [],
  },
  global: { stubs },
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
