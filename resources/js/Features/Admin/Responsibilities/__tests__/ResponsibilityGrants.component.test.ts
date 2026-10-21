import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { useForm } from '@inertiajs/vue3';
import { nextTick } from 'vue';

import { createMockForm, type MockForm } from '@/tests/helpers/createMockForm';
import ResponsibilityGrants from '@/Features/Admin/Responsibilities/ResponsibilityGrants.vue';
import type { ResponsibilityGrantsPayload } from '@/Features/Admin/Responsibilities/types';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const stubs = {
  Button: { template: '<button><slot /></button>' },
  OverviewSection: { template: '<section><slot name="actions" /><slot /></section>' },
  SheetForm: { props: ['open'], template: '<div v-if="open" data-testid="grant-sheet"><slot /></div>' },
  ConfirmDialog: true,
  AccessChangeWarningDialog: true,
  FormFieldWrapper: { template: '<div><slot /></div>' },
  FormSegmentedControl: {
    props: ['modelValue', 'options', 'testIdPrefix'],
    emits: ['update:modelValue'],
    template: '<button v-for="option in options" :key="option.value" :data-testid="`${testIdPrefix}-${option.value}`" @click="$emit(\'update:modelValue\', option.value)">{{ option.label }}</button>',
  },
  TypeTreeSelect: true,
  SingleSelect: { template: '<div data-testid="scope-picker" />' },
  DutySearchField: true,
  InflectedDutyName: true,
  Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
};

const grants: ResponsibilityGrantsPayload = {
  duties: [],
  dutyTypes: [{ id: 'grant-1', type: { id: 3, title: 'ŽI koordinatorius' }, scope_type: 'own_tenant', scope_name: null, duties_count: 2 }],
  institutions: [],
};

let form: MockForm;

function mountGrants() {
  return mount(ResponsibilityGrants, {
    props: {
      roleId: 'role-1',
      grants,
      scopes: ['tenant'],
      canUpdate: true,
      dutyTypeOptions: [{ id: 3, title: 'ŽI koordinatorius', parent_id: null }],
      options: { institutions: [], tenants: [{ id: '1', shortname: 'MIF' }], institutionTypes: [] },
    },
    global: { stubs },
  });
}

beforeEach(() => {
  vi.stubGlobal('route', (name: string) => `/mocked/${name}`);
  form = createMockForm({ grantee_type: 'duty', grantee_id: '', scope_type: 'tenant', scope_id: '' });
  vi.mocked(useForm).mockReturnValue(form as unknown as ReturnType<typeof useForm>);
});

describe('ResponsibilityGrants', () => {
  it('shows a type grant that covers each duty\'s own padalinys', () => {
    expect(mountGrants().get('[data-testid="grant-duty-type"]').text()).toContain('responsibilities.scopes.own_tenant');
  });

  it('defaults a type grant to the own padalinys and hides the padalinys picker', async () => {
    const wrapper = mountGrants();

    await wrapper.findAll('button').find(button => button.text().includes('responsibilities.grants.add_type'))!.trigger('click');
    await nextTick();

    expect(form.scope_type).toBe('own_tenant');
    expect(wrapper.find('[data-testid="grant-own-tenant-hint"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="scope-picker"]').exists()).toBe(false);
  });

  it('gives an institution grant its own padalinys without asking', async () => {
    const wrapper = mountGrants();

    await wrapper.findAll('button').find(button => button.text().includes('responsibilities.grants.add_institution'))!.trigger('click');
    await nextTick();

    expect(form.scope_type).toBe('own_tenant');
    expect(wrapper.find('[data-testid^="grant-scope-"]').exists()).toBe(false);
    expect(wrapper.get('[data-testid="grant-own-tenant-hint"]').text()).toBe('responsibilities.grants.institution_tenant_hint');
  });

  it('never offers the own padalinys for a single duty', async () => {
    const wrapper = mountGrants();

    await wrapper.get('[data-testid="grant-add"]').trigger('click');
    await nextTick();

    expect(wrapper.find('[data-testid="grant-scope-own_tenant"]').exists()).toBe(false);
    expect(form.scope_type).toBe('tenant');
  });
});
