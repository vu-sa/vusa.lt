import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';
import { describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import { commonStubs } from '@/tests/stubs';

import InstitutionRelationsList from '../InstitutionRelationsList.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@/Composables/useFeatureSpotlight', () => ({
  useFeatureSpotlight: () => ({ isDismissed: ref(true), dismiss: vi.fn() }),
}));

const related = [
  { id: 'a', name: 'KAP Taryba', direction: 'outgoing' as const, via: 'direct' as const, kind_label: 'Tvirtina sudėtį', authorized: true },
  { id: 'b', name: 'Senatas', direction: 'incoming' as const, via: 'type' as const, kind_label: 'Susijusi', cross_tenant: true, authorized: false },
];
const links = [
  { id: 1, other: { id: 'a', name: 'KAP Taryba', is_active: true }, direction: 'outgoing' as const, kind: 'approves_composition', kind_label: 'Tvirtina sudėtį', mutual: false },
  // Left out of the relation summary, yet still a link someone may need to remove.
  { id: 2, other: { id: 'z', name: 'Archyvas', is_active: false }, direction: 'incoming' as const, kind: 'related', kind_label: 'Susijusi', mutual: true },
];

const stubs = {
  ...commonStubs,
  SpotlightPopover: { template: '<div><slot /></div>' },
  RelationshipLinkSheet: { name: 'RelationshipLinkSheet', props: ['open', 'link'], template: '<div />' },
  ConfirmDialog: { name: 'ConfirmDialog', props: ['open'], emits: ['confirm', 'update:open'], template: '<div />' },
};

const mountList = (canManage = true) => mount(InstitutionRelationsList, {
  props: { related, links, recordId: 'x', recordName: 'VU SA', canManage, kinds: [] },
  global: { stubs },
});

const rowFor = (wrapper: ReturnType<typeof mountList>, name: string) =>
  wrapper.findAll('[data-testid="relationship-row"]').find(row => row.text().includes(name))!;

describe('InstitutionRelationsList', () => {
  it('lists type and direct relations together, grouped by direction, with each link once', () => {
    const wrapper = mountList();

    expect(wrapper.findAll('[data-testid="relationship-row"]')).toHaveLength(3);
    expect(wrapper.findAll('section[data-group]').map(group => group.attributes('data-group'))).toEqual(['outgoing', 'incoming', 'mutual']);
    expect(rowFor(wrapper, 'KAP Taryba').text()).toContain('Tiesioginis ryšys');
    expect(rowFor(wrapper, 'Senatas').text()).toContain('Per tipą · Tarp padalinių');
  });

  it('offers editing only for direct links, and only to someone who may manage them', async () => {
    const wrapper = mountList();

    expect(rowFor(wrapper, 'Senatas').text()).not.toContain('Pašalinti ryšį');
    await rowFor(wrapper, 'KAP Taryba').findAll('button').find(button => button.text().includes('Redaguoti ryšį'))!.trigger('click');
    expect(wrapper.findComponent({ name: 'RelationshipLinkSheet' }).props()).toMatchObject({ open: true, link: links[0] });

    const readOnly = mountList(false);
    expect(readOnly.text()).not.toContain('Pridėti ryšį');
    expect(readOnly.text()).not.toContain('Redaguoti ryšį');
  });

  it('removes a link the relation summary left out', async () => {
    const wrapper = mountList();

    await rowFor(wrapper, 'Archyvas').findAll('button').find(button => button.text().includes('Pašalinti ryšį'))!.trigger('click');
    wrapper.findComponent({ name: 'ConfirmDialog' }).vm.$emit('confirm');

    expect(router.delete).toHaveBeenCalledWith('/mocked-route/institutionLinks.destroy', expect.objectContaining({ preserveScroll: true }));
  });
});
