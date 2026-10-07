import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

import RelationshipLinkList from '../RelationshipLinkList.vue';
import type { RelationshipLinkRow } from '../types';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@/Composables/useFeatureSpotlight', () => ({
  useFeatureSpotlight: () => ({ isDismissed: ref(true), dismiss: vi.fn() }),
}));

const links: RelationshipLinkRow[] = [
  { id: 1, other: { id: '7', name: 'KAP Taryba' }, direction: 'outgoing', kind: 'approves_composition', kind_label: 'Tvirtina sudėtį', mutual: false },
  { id: 2, other: { id: '3', name: 'Senatas' }, direction: 'outgoing', kind: 'related', kind_label: 'Susijusi', mutual: true, cross_tenant: true },
];

const stubs = {
  Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
  SpotlightPopover: { template: '<div><slot /></div>' },
  RelationshipLinkSheet: true,
  ConfirmDialog: { name: 'ConfirmDialog', props: ['open'], emits: ['confirm', 'update:open'], template: '<div />' },
};

const mountList = (props: Record<string, unknown> = {}) => mount(RelationshipLinkList, {
  props: { subject: 'type', recordId: '3', recordName: 'Senatas', links, canManage: true, kinds: [], ...props },
  global: { stubs },
});

describe('RelationshipLinkList', () => {
  it('names a self-link by what it relates, not by the type itself', () => {
    const rows = mountList().findAll('[data-testid="relationship-link-row"]');

    expect(rows[0].text()).toContain('KAP Taryba');
    expect(rows[1].text()).toContain('To paties tipo institucijos');
    expect(rows[1].text()).toContain('Centrinis → padaliniai');
  });

  it('hides every action from someone who cannot manage links', () => {
    const wrapper = mountList({ canManage: false });

    expect(wrapper.find('[data-testid="relationship-link-add"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="relationship-link-edit"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="relationship-link-remove"]').exists()).toBe(false);
    expect(wrapper.findComponent({ name: 'RelationshipLinkSheet' }).exists()).toBe(false);
  });

  it('removes a link only after confirming', async () => {
    const wrapper = mountList({ subject: 'institution', recordId: 'inst-1' });

    await wrapper.findAll('[data-testid="relationship-link-remove"]')[0].trigger('click');
    expect(router.delete).not.toHaveBeenCalled();

    wrapper.findComponent({ name: 'ConfirmDialog' }).vm.$emit('confirm');

    expect(router.delete).toHaveBeenCalledWith(expect.stringContaining('institutionLinks.destroy'), expect.anything());
  });
});
