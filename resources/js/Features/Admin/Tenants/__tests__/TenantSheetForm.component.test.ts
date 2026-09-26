import { mount } from '@vue/test-utils';
import { useForm } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import TenantSheetForm from '@/Features/Admin/Tenants/TenantSheetForm.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name: string, id?: string | number) => `/mocked-route/${name}${id ? `/${id}` : ''}`);

const SheetFormStub = {
  props: ['open', 'title'],
  emits: ['submit', 'cancel', 'update:open'],
  template: '<section v-if="open"><h1>{{ title }}</h1><slot /><button type="button" data-testid="submit" @click="$emit(\'submit\')">save</button></section>',
};

const mountForm = (tenant: any = null) => mount(TenantSheetForm, {
  props: { open: true, tenant, assignableInstitutions: [{ id: 1, name: 'Taryba' }] },
  global: {
    stubs: {
      SheetForm: SheetFormStub,
      Select: { template: '<div><slot /></div>' },
      SelectContent: { template: '<div><slot /></div>' },
      SelectItem: { template: '<div><slot /></div>' },
      SelectTrigger: { template: '<div><slot /></div>' },
      SelectValue: { template: '<div />' },
    },
  },
});

describe('TenantSheetForm.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('posts a new tenant from the collection sheet', async () => {
    const wrapper = mountForm();

    expect(wrapper.text()).toContain('Naujas padalinys');

    await wrapper.find('[data-testid="submit"]').trigger('click');

    expect(vi.mocked(useForm).mock.results[0]?.value.post).toHaveBeenCalledWith('/mocked-route/tenants.store', expect.any(Object));
  });

  it('patches the selected tenant on edit', async () => {
    const wrapper = mountForm({
      id: 7,
      fullname: 'VU SA MIF',
      shortname: 'MIF',
      type: 'padalinys',
      alias: 'mif',
      shortname_vu: 'MIF',
      primary_institution_id: 1,
    });

    expect(wrapper.text()).toContain('Redaguoti padalinį');

    await wrapper.find('[data-testid="submit"]').trigger('click');

    expect(vi.mocked(useForm).mock.results[0]?.value.patch).toHaveBeenCalledWith('/mocked-route/tenants.update/7', expect.any(Object));
  });
});
