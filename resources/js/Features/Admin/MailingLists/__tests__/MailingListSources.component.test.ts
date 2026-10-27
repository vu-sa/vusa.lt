import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import MailingListSources from '../MailingListSources.vue';
import type { MailingListSourceItem } from '../types';

import { MailingListAddressMode, MailingListSourceType, MailingListTenantScope } from '@/Types/enums';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const source: MailingListSourceItem = {
  id: 'source-1', type: MailingListSourceType.Institution, label: 'Taryba',
  address_mode: MailingListAddressMode.DutyMailbox, tenant_scope: MailingListTenantScope.All,
  tenants: [], include_subtypes: true, excluded_types: [], duty_types: ['Pirmininkas'],
  note: 'Visi tarybos nariai', expires_on: null, is_expired: false,
};

function mountSources(canUpdate: boolean) {
  return mount(MailingListSources, {
    props: { mailingListId: 'list-1', sources: [source], exclusions: [], canUpdate },
    global: { stubs: { ...commonStubs, MailingListSourceSheet: true, ConfirmDialog: true } },
  });
}

describe('MailingListSources', () => {
  it('summarises a source with its address, filters and note', () => {
    const row = mountSources(true).get('[data-testid="source-row"]');

    expect(row.text()).toContain('Taryba');
    expect(row.text()).toContain('address_modes.duty_mailbox');
    expect(row.text()).toContain('Pirmininkas');
    expect(row.text()).toContain('Visi tarybos nariai');
  });

  it('offers no changes to people who cannot edit the list', () => {
    const wrapper = mountSources(false);

    expect(wrapper.find('[data-testid="source-add"]').exists()).toBe(false);
    expect(wrapper.get('[data-testid="source-row"]').find('button').exists()).toBe(false);
    expect(wrapper.find('form').exists()).toBe(false);
  });
});
