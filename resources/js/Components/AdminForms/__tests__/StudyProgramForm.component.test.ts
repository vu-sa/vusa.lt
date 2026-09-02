import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import StudyProgramForm from '@/Components/AdminForms/StudyProgramForm.vue';
import { commonStubs } from '@/tests/stubs';

const stubs = {
  ...commonStubs,
  AdminForm: { template: '<form @submit.prevent><slot /></form>' },
  FormElement: { template: '<section><slot name="title" /><slot name="description" /><slot /></section>' },
  FormFieldWrapper: { template: '<div><slot /></div>' },
  MultiLocaleInput: { template: '<div />' },
  Select: { template: '<div><slot /></div>' },
  SelectContent: { template: '<div><slot /></div>' },
  SelectItem: { template: '<div><slot /></div>' },
  SelectTrigger: { template: '<div><slot /></div>' },
  SelectValue: { template: '<div />' },
};

const mountForm = () => mount(StudyProgramForm, {
  props: {
    studyProgram: { name: { lt: '', en: '' }, degree: null, tenant_id: null },
    tenants: [],
  },
  global: { stubs },
});

describe('StudyProgramForm.vue — mobile field spacing', () => {
  it('gives the paired degree/tenant fields a row gap, not just a column gap', () => {
    // Regression: `gap-x-4` alone leaves zero space between the two FormFieldWrappers
    // once `lg:grid-cols-2` collapses to a single column below the lg breakpoint —
    // their labels/inputs touched on mobile. `gap-4` keeps the same visual gap at
    // lg+ (only one row exists there, so gap-y is never applied) while fixing mobile.
    const wrapper = mountForm();

    const grid = wrapper.find('.grid.lg\\:grid-cols-2');
    expect(grid.exists()).toBe(true);
    expect(grid.classes()).toContain('gap-4');
    expect(grid.classes()).not.toContain('gap-x-4');
  });
});
