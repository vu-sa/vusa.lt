import { afterEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import InstitutionForm from '@/Components/AdminForms/InstitutionForm.vue';

vi.mock('@inertiajs/vue3', async () => {
  const actual = await vi.importActual('@inertiajs/vue3');

  return {
    ...actual,
    usePage: () => ({
      props: {
        app: { locale: 'lt' },
        tenants: [{ id: 2, alias: 'ff' }],
      },
    }),
  };
});

describe('InstitutionForm.vue — status links', () => {
  let wrapper: ReturnType<typeof mount>;

  afterEach(() => {
    wrapper?.unmount();
  });

  it('builds the public link from the institution tenant, not the editable tenant list', () => {
    wrapper = mount(InstitutionForm, {
      shallow: true,
      props: {
        institution: {
          id: 'institution-1',
          name: { lt: 'VU SA FF', en: 'VU SA FF' },
          short_name: { lt: '', en: '' },
          description: { lt: '', en: '' },
          address: { lt: '', en: '' },
          working_hours: { lt: '', en: '' },
          website: '',
          alias: 'ff',
          image_url: null,
          image_focal_point: null,
          logo_url: null,
          is_active: true,
          tenant_id: 2,
          types: [],
          duties: [],
        },
        institutionTypes: [],
        assignableTenants: {} as never,
      },
    });

    const vm = wrapper.vm as unknown as { statusLinks: { label: string }[] };
    expect(vm.statusLinks.map(link => link.label)).toEqual(['Admin', 'Public']);
  });
});
