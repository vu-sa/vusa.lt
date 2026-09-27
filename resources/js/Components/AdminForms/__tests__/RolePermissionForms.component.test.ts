import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import RolePermissionForms from '@/Components/AdminForms/RolePermissionForms.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const mountForms = (baselineAccess?: Record<string, string>) => mount(RolePermissionForms, {
  props: { role: { id: 1, name: 'Studentų atstovas', permissions: [] } as unknown as App.Entities.Role, allAvailablePermissions: {}, baselineAccess },
  global: { stubs: { ...commonStubs, PermissionTable: true } },
});

describe('RolePermissionForms', () => {
  it('says what every member already has next to the entity it concerns', () => {
    const wrapper = mountForms({ problems: 'Mato visų padalinių problemas.' });
    const lines = wrapper.findAll('[data-testid="baseline-access"]');

    expect(lines).toHaveLength(1);
    expect(lines[0]!.text()).toContain('Mato visų padalinių problemas.');
  });

  it('shows no baseline line without the payload', () => {
    expect(mountForms().find('[data-testid="baseline-access"]').exists()).toBe(false);
  });
});
