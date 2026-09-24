import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import TenantForm from '@/Components/AdminForms/TenantForm.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name?: string, id?: string | number) => (name === undefined ? { current: () => false } : `/mocked/${name}${id ? `/${id}` : ''}`));

const stubs = {
  ...commonStubs,
  FormPage: {
    props: ['title', 'mode'],
    emits: ['submit'],
    template: '<form data-testid="form-page" :data-mode="mode" @submit.prevent="$emit(\'submit\')"><h1>{{ title }}</h1><slot /><slot name="aside" /><slot name="header-actions" /><slot name="danger-zone" /></form>',
  },
  FormPanel: { props: ['title'], template: '<div data-testid="form-panel" :data-title="title"><slot /></div>' },
  FormSection: { props: ['title'], template: '<section :data-section="title"><slot /></section>' },
  FormFieldWrapper: { props: ['id', 'label', 'error'], template: '<div :data-field="id"><label>{{ label }}</label><slot /><span v-if="error" class="error">{{ error }}</span></div>' },
  Input: {
    props: ['modelValue', 'disabled'],
    template: '<input data-testid="input" :disabled="disabled" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
  },
  Select: { template: '<div><slot /></div>' },
  SelectContent: { template: '<div><slot /></div>' },
  SelectItem: { template: '<div><slot /></div>' },
  SelectTrigger: { template: '<div><slot /></div>' },
  SelectValue: { template: '<div />' },
  ConfirmDialog: true,
};

const defaultTenant = {
  id: 1,
  fullname: 'VU SA MIF',
  shortname: 'MIF',
  type: 'padalinys',
  alias: 'mif',
  shortname_vu: 'MIF',
  primary_institution_id: null,
};

const mountForm = (props: Record<string, unknown> = {}) =>
  mount(TenantForm, {
    props: { tenant: defaultTenant as unknown as App.Entities.Tenant, ...props },
    global: { stubs },
  });

describe('TenantForm.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders in edit mode with header actions and danger zone when enabled', () => {
    const wrapper = mountForm({ enableDelete: true });

    expect(wrapper.find('[data-testid="form-page"]').attributes('data-mode')).toBe('edit');
    expect(wrapper.text()).toContain('Redaguoti padalinio pagr. puslapį');
    expect(wrapper.text()).toContain('Ištrinti padalinį');
  });

  it('renders in create mode when rememberKey is provided', () => {
    const wrapper = mountForm({ rememberKey: 'CreateTenant', tenant: { fullname: '', shortname: '', type: 'padalinys', alias: '', shortname_vu: '', primary_institution_id: null } });

    expect(wrapper.find('[data-testid="form-page"]').attributes('data-mode')).toBe('create');
    expect(wrapper.text()).toContain('Naujas padalinys');
  });

  it('renders type and primary institution in the aside panel', () => {
    const wrapper = mountForm({
      assignableInstitutions: [{ id: 10, name: 'MIF Studentų atstovybė' }],
    });

    const asidePanel = wrapper.find('[data-testid="form-panel"]');
    expect(asidePanel.exists()).toBe(true);
    expect(asidePanel.find('[data-field="type"]').exists()).toBe(true);
    expect(asidePanel.find('[data-field="primary_institution_id"]').exists()).toBe(true);
  });
});
