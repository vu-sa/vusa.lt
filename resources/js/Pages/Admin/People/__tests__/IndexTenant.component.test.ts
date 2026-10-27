import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { trans as $t } from 'laravel-vue-i18n';

import IndexTenant from '../IndexTenant.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const tenants = [
  { id: 7, fullname: 'VU SA MIF', shortname: 'MIF', alias: 'mif', type: 'padalinys',
    can: { update: true, delete: false }, primary_institution_id: '01INSTITUTION',
    primary_institution: { id: '01INSTITUTION', name: 'Padalinio institucija', is_active: true, tenant: { id: 7, shortname: 'MIF' } } },
  { id: 8, fullname: 'VU SA FF', shortname: 'FF', alias: 'ff', type: 'padalinys',
    can: { update: true, delete: false }, primary_institution_id: null, primary_institution: null },
];

describe('IndexTenant', () => {
  it.each(['row', 'cell'])('shows the primary institution and empty label in the %s view', (view) => {
    const wrapper = mount(IndexTenant, {
      props: { tenants },
      global: { stubs: {
        CollectionPage: {
          props: ['columns'],
          setup: () => ({ tenants, view }),
          template: `<main><div v-for="item in tenants" :key="item.id"><slot v-if="view === 'row'" name="row" :item="item" /><slot v-else name="cell" :item="item" :column="{ key: 'primary_institution' }" /></div></main>`,
        },
        TenantSheetForm: true, ConfirmDialog: true, CollectionRowActions: true,
      } },
    });
    expect(wrapper.text()).toContain('Padalinio institucija');
    expect(wrapper.text()).toContain($t('forms.tenants.not_assigned'));
  });
});
