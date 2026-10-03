import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import StudyProgramForm from '@/Components/AdminForms/StudyProgramForm.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name?: string, id?: string | number) => (name === undefined ? { current: () => false } : `/mocked/${name}${id ? `/${id}` : ''}`));

const stubs = {
  ...commonStubs,
  FormPage: {
    props: ['title', 'mode'],
    emits: ['submit'],
    template: '<form data-testid="form-page" :data-mode="mode" @submit.prevent="$emit(\'submit\')"><h1>{{ title }}</h1><slot /><slot name="aside" /><slot name="danger-zone" /></form>',
  },
  FormPanel: { props: ['title'], template: '<div data-testid="form-panel" :data-title="title"><slot /></div>' },
  FormSection: { props: ['title'], template: '<section :data-section="title"><slot /></section>' },
  FormFieldWrapper: { props: ['id'], template: '<div :data-field="id"><slot /></div>' },
  MultiLocaleInput: { template: '<div />' },
  Select: { template: '<div><slot /></div>' },
  SelectContent: { template: '<div><slot /></div>' },
  SelectItem: { template: '<div><slot /></div>' },
  SelectTrigger: { template: '<div><slot /></div>' },
  SelectValue: { template: '<div />' },
  ConfirmDialog: true,
};

const mountForm = (props: Record<string, unknown> = {}) => mount(StudyProgramForm, {
  props: {
    studyProgram: { name: { lt: 'Informatika', en: 'Informatics' }, degree: 'ba', tenant_id: 1 } as unknown as App.Entities.StudyProgram,
    tenants: [],
    ...props,
  },
  global: { stubs },
});

describe('StudyProgramForm.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders in edit mode and shows danger zone when enabled', () => {
    const wrapper = mountForm({ enableDelete: true, studyProgram: { id: 3, name: { lt: 'Informatika', en: 'Informatics' }, degree: 'ba', tenant_id: 1 } });

    expect(wrapper.find('[data-testid="form-page"]').attributes('data-mode')).toBe('edit');
    expect(wrapper.text()).toContain('Ištrinti studijų programą');
  });

  it('renders in create mode when rememberKey is provided', () => {
    const wrapper = mountForm({ rememberKey: 'CreateStudyProgram' });

    expect(wrapper.find('[data-testid="form-page"]').attributes('data-mode')).toBe('create');
    expect(wrapper.text()).toContain('Nauja studijų programa');
  });

  it('places degree and tenant fields in the aside panel', () => {
    const wrapper = mountForm();

    const asidePanel = wrapper.find('[data-testid="form-panel"]');
    expect(asidePanel.exists()).toBe(true);
    expect(asidePanel.find('[data-field="degree"]').exists()).toBe(true);
    expect(asidePanel.find('[data-field="tenant_id"]').exists()).toBe(true);
  });
});
