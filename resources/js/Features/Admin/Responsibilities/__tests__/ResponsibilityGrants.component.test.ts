import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { router, useForm } from '@inertiajs/vue3';
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
  users: [],
};

let form: MockForm;

function mountGrants(props: Record<string, unknown> = {}, extraStubs: Record<string, unknown> = {}) {
  return mount(ResponsibilityGrants, {
    props: {
      roleId: 'role-1',
      grants,
      ...props,
      scopes: ['tenant'],
      canUpdate: true,
      dutyTypeOptions: [{ id: 3, title: 'ŽI koordinatorius', parent_id: null }],
      options: { institutions: [], tenants: [{ id: '1', shortname: 'MIF' }], institutionTypes: [] },
    },
    global: { stubs: { ...stubs, ...extraStubs } },
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

  it('removes a grant as a partial reload so the deferred list stays in place', async () => {
    vi.mocked(router.delete).mockClear();
    const wrapper = mountGrants({}, {
      ConfirmDialog: { props: ['open'], emits: ['confirm'], template: '<button v-if="open" data-testid="confirm-yes" @click="$emit(\'confirm\')" />' },
    });
    await wrapper.get('[data-testid="grant-duty-type"] button').trigger('click');
    await wrapper.get('[data-testid="confirm-yes"]').trigger('click');

    expect(router.delete).toHaveBeenCalledWith('/mocked/responsibilities.grants.destroy', expect.objectContaining({
      preserveScroll: true,
      only: expect.arrayContaining(['grants', 'flash']),
    }));
  });

  describe('people holding it directly', () => {
    const withHolder = { ...grants, users: [{ id: 'u1', name: 'Justas', profile_photo_path: null, tenants: ['VU SA', 'VU SA FilF'] }] };
    const confirmingDialog = { ConfirmDialog: { props: ['open'], emits: ['confirm'], template: '<button v-if="open" data-testid="confirm-yes" @click="$emit(\'confirm\')" />' } };

    it('lists every padalinys the role reaches through them', () => {
      expect(mountGrants({ grants: withHolder }).get('[data-testid="grant-user"]').text()).toContain('VU SA, VU SA FilF');
    });

    it('lets only a super admin take it away, through its own route', async () => {
      expect(mountGrants({ grants: withHolder }).get('[data-testid="grant-user"]').find('button').exists()).toBe(false);

      vi.mocked(router.delete).mockClear();
      const wrapper = mountGrants({ grants: withHolder, canRemoveUsers: true }, confirmingDialog);
      await wrapper.get('[data-testid="grant-user"] button').trigger('click');
      await wrapper.get('[data-testid="confirm-yes"]').trigger('click');

      expect(router.delete).toHaveBeenCalledWith('/mocked/responsibilities.users.destroy', expect.objectContaining({ data: { acknowledge_access_change: false } }));
    });

    it('shows only the people on the super admin responsibility, which is switched on their pages', () => {
      const wrapper = mountGrants({ grants: withHolder, isSuperAdmin: true, canRemoveUsers: true });

      expect(wrapper.find('[data-testid="grant-duty-type"]').exists()).toBe(false);
      expect(wrapper.get('[data-testid="grant-user"]').find('button').exists()).toBe(false);
      expect(wrapper.get('[data-testid="grant-users"]').text()).toContain('responsibilities.grants.users_super_admin_hint');
    });
  });
});
