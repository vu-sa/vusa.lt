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
    expect(transform({ other_id: 'inst-2', direction: 'incoming', kind: 'oversees', mutual: true, scope: 'same_tenant' }))
      .toEqual({ other_institution_id: 'inst-2', direction: 'incoming', kind: 'oversees', mutual: true });
    expect(form.post).toHaveBeenCalledWith(expect.stringContaining('institutions.links.store'), expect.anything());
  });

  it('creates a type link with the chosen scope', async () => {
    const { wrapper, form } = mountSheet({ subject: 'type', recordId: '3' });

    await wrapper.find('[data-testid="relationship-scope-all_tenants"]').trigger('click');
    expect(form.scope).toBe('all_tenants');

    wrapper.findComponent({ name: 'SheetForm' }).vm.$emit('submit');

    const transform = vi.mocked(form.transform).mock.calls[0][0];
    expect(transform({ other_id: '3', direction: 'outgoing', kind: 'related', mutual: true, scope: 'all_tenants' }))
      .toEqual({ other_type_id: '3', direction: 'outgoing', kind: 'related', mutual: true, scope: 'all_tenants' });
    expect(form.post).toHaveBeenCalledWith(expect.stringContaining('institutionTypes.links.store'), expect.anything());
  });

  it('edits only the kind and mutuality of an existing link', () => {
    const link = { id: 5, other: { id: '9', name: 'Senatas' }, direction: 'outgoing', kind: 'related', kind_label: 'Susijusi', mutual: false, scope: 'main_to_units' };
    const { wrapper, form } = mountSheet({ subject: 'type', recordId: '3', link });

    expect(wrapper.text()).toContain('Senatas');
    expect(wrapper.find('[data-testid="relationship-scope-main_to_units"]').exists()).toBe(false);

    wrapper.findComponent({ name: 'SheetForm' }).vm.$emit('submit');

    const transform = vi.mocked(form.transform).mock.calls[0][0];
    expect(transform({ other_id: '9', direction: 'outgoing', kind: 'advisory', mutual: true, scope: 'main_to_units' })).toEqual({ kind: 'advisory', mutual: true });
    expect(form.patch).toHaveBeenCalledWith(expect.stringContaining('institutionTypeLinks.update'), expect.anything());
  });
});
