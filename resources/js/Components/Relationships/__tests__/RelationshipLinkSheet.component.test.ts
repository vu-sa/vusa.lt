import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { useForm } from '@inertiajs/vue3';

import RelationshipLinkSheet from '../RelationshipLinkSheet.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const stubs = {
  SheetForm: { name: 'SheetForm', props: ['open'], emits: ['submit', 'update:open', 'cancel'], template: '<div><slot /></div>' },
  InlineCollectionSelect: true,
  SingleSelect: true,
};

const mountSheet = (props: Record<string, unknown> = {}) => {
  const wrapper = mount(RelationshipLinkSheet, {
    props: { open: true, subject: 'institution', recordId: 'inst-1', recordName: 'Taryba', link: null, kinds: [{ value: 'related', label: 'Susijusi' }], ...props },
    global: { stubs },
  });
  const form = vi.mocked(useForm).mock.results.at(-1)!.value;

  return { wrapper, form };
};

describe('RelationshipLinkSheet', () => {
  it('creates an institution link from the record, naming the picked institution', () => {
    const { wrapper, form } = mountSheet();

    wrapper.findComponent({ name: 'SheetForm' }).vm.$emit('submit');

    const transform = vi.mocked(form.transform).mock.calls[0][0];
    expect(transform({ other_id: 'inst-2', direction: 'incoming', kind: 'oversees', mutual: true, cross_tenant: false }))
      .toEqual({ other_institution_id: 'inst-2', direction: 'incoming', kind: 'oversees', mutual: true });
    expect(form.post).toHaveBeenCalledWith(expect.stringContaining('institutions.links.store'), expect.anything());
  });

  it('edits only the kind and mutuality of an existing link', () => {
    const link = { id: 5, other: { id: '9', name: 'Senatas' }, direction: 'outgoing', kind: 'related', kind_label: 'Susijusi', mutual: false, cross_tenant: true };
    const { wrapper, form } = mountSheet({ subject: 'type', recordId: '3', link });

    expect(wrapper.text()).toContain('Senatas');
    expect(wrapper.find('#relationship-cross-tenant').exists()).toBe(false);

    wrapper.findComponent({ name: 'SheetForm' }).vm.$emit('submit');

    const transform = vi.mocked(form.transform).mock.calls[0][0];
    expect(transform({ other_id: '9', direction: 'outgoing', kind: 'advisory', mutual: true, cross_tenant: true })).toEqual({ kind: 'advisory', mutual: true });
    expect(form.patch).toHaveBeenCalledWith(expect.stringContaining('institutionTypeLinks.update'), expect.anything());
  });
});
