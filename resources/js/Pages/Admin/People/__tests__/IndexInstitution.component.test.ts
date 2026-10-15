import { describe, expect, it, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref, toValue, type MaybeRefOrGetter } from 'vue';

import IndexInstitution from '../IndexInstitution.vue';

import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const typesense = vi.hoisted(() => ({
  baseFilterBy: undefined as unknown,
  rows: [] as Record<string, unknown>[],
  scope: { isSuperAdmin: false, tenantIds: [1], institutionIds: [] as string[] },
}));
const subscription = vi.hoisted(() => ({ setFollowedMany: vi.fn(), toggleFollow: vi.fn() }));

vi.mock('@/Composables/useCollectionSource', () => ({
  isTrashView: () => false,
  useTrashCollectionSource: vi.fn(),
  useTypesenseCollectionSource: (options: { baseFilterBy?: unknown }) => {
    typesense.baseFilterBy = options.baseFilterBy;

    return { items: ref(typesense.rows), accessScope: ref(typesense.scope) };
  },
}));

vi.mock('@/Composables/useInstitutionSubscription', () => ({
  useInstitutionSubscription: () => ({ ...subscription, bulkLoading: ref(false) }),
}));

/** The collection shell is covered by its own suite; here it only hands over its slots and events. */
const CollectionPageStub = {
  name: 'CollectionPage',
  props: ['source', 'quickFilters', 'selectable'],
  emits: ['quickFilter'],
  template: `
    <div>
      <div v-for="item in source.items.value" :key="item.id" data-testid="row"><slot name="row" :item="item" view="rows" /></div>
      <div data-testid="bulk"><slot name="bulk-actions" :selected="source.items.value" :clear="() => {}" /></div>
    </div>
  `,
};

const mountPage = (followedInstitutionIds: string[] = ['i1']) => mount(IndexInstitution, {
  props: { deletedCount: 0, followedInstitutionIds, defaultTenantShortnames: [], publicMeetingTypeIds: [7] },
  global: { stubs: { ...commonStubs, CollectionPage: CollectionPageStub, CollectionConfirmAction: true } },
});

const baseFilter = () => toValue(typesense.baseFilterBy as MaybeRefOrGetter<string | undefined>);

describe('IndexInstitution following', () => {
  beforeEach(() => {
    typesense.rows = [{ id: 'i1', name_lt: 'Senatas', tenant_id: 1 }, { id: 'i2', name_lt: 'Taryba', tenant_id: 1 }];
    subscription.setFollowedMany.mockReset().mockResolvedValue(true);
    window.history.replaceState({}, '', '/mano/institutions');
  });

  it('marks followed rows and counts them on the quick filter', () => {
    const wrapper = mountPage(['i1']);

    expect(wrapper.findAll('[data-slot="institution-followed"]')).toHaveLength(1);
    expect(wrapper.findComponent(CollectionPageStub).props('quickFilters')).toEqual([{ id: 'followed', label: 'Sekamos (1)', active: false }]);
  });

  it('narrows Typesense to the followed ids while "Sekamos" is on, and remembers it in the URL', async () => {
    const wrapper = mountPage(['i1', 'i3']);
    expect(baseFilter()).toBeUndefined();

    wrapper.findComponent(CollectionPageStub).vm.$emit('quickFilter', 'followed');
    await wrapper.vm.$nextTick();

    expect(baseFilter()).toBe('id:=[i1,i3]');
    expect(new URLSearchParams(window.location.search).get('followed')).toBe('1');
  });

  it('matches nothing, rather than everything, when "Sekamos" is on with no follows', () => {
    window.history.replaceState({}, '', '/mano/institutions?followed=1');
    mountPage([]);

    expect(baseFilter()).toBe('id:=[__none__]');
  });

  it('follows only the selected rows not followed yet, in one request', async () => {
    const wrapper = mountPage(['i1']);

    await wrapper.find('[data-testid="bulk"] button').trigger('click');

    expect(subscription.setFollowedMany).toHaveBeenCalledWith(['i2'], true);
    await vi.waitFor(() => expect(wrapper.findAll('[data-slot="institution-followed"]')).toHaveLength(2));
  });

  it('offers following only where the meetings are public or the user reaches the institution', async () => {
    typesense.rows = [
      { id: 'mine', name_lt: 'Mano', tenant_id: 1 },
      { id: 'public', name_lt: 'Vieša', tenant_id: 2, type_ids: [7] },
      { id: 'closed', name_lt: 'Uždara', tenant_id: 2 },
    ];
    const wrapper = mountPage([]);

    const offersFollow = wrapper.findAllComponents({ name: 'CollectionRowActions' })
      .map(actions => (actions.props('actions') as { key: string }[]).some(action => action.key === 'follow'));
    expect(offersFollow).toEqual([true, true, false]);

    await wrapper.find('[data-testid="bulk"] button').trigger('click');
    expect(subscription.setFollowedMany).toHaveBeenCalledWith(['mine', 'public'], true);
  });
});
