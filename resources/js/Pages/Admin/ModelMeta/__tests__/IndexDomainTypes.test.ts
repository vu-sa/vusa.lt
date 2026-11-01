import { usePage } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import IndexDomainTypes from '../IndexDomainTypes.vue';

import type { CollectionColumn } from '@/Components/Collection/types';
import type { CollectionSource } from '@/Composables/useCollectionSource';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const dismiss = vi.fn();
vi.mock('@/Composables/useFeatureSpotlight', () => ({
  useFeatureSpotlight: () => ({ isDismissed: ref(false), dismiss }),
}));

function render(typeKind: 'institutionType' | 'dutyType', canCreate: boolean) {
  const otherKind = typeKind === 'institutionType' ? 'dutyType' : 'institutionType';
  vi.mocked(usePage).mockReturnValue(createMockPage({
    auth: { can: { create: { [typeKind]: canCreate, [otherKind]: true } } },
  }));

  return mount(IndexDomainTypes, {
    props: { typeKind, types: [], deletedCount: 0 },
    global: {
      stubs: {
        CollectionPage: { name: 'CollectionPage', props: ['columns', 'source'], template: '<main><slot name="actions" /></main>' },
        CollectionConfirmAction: true,
        SpotlightPopover: { template: '<div><slot /></div>' },
      },
    },
  });
}

describe.each([
  ['institutionType', 'institutionTypes'],
  ['dutyType', 'dutyTypes'],
] as const)('%s collection creation', (typeKind, resource) => {
  beforeEach(() => {
    window.history.replaceState({}, '', '/');
    dismiss.mockClear();
  });

  it('links to its create form and dismisses discovery when opened', async () => {
    const wrapper = render(typeKind, true);
    const link = wrapper.get('a');

    expect(link.attributes('href')).toBe(route(`${resource}.create`));
    await link.trigger('click');
    expect(dismiss).toHaveBeenCalledOnce();
  });

  it('offers created and updated dates as hidden sortable columns', () => {
    const wrapper = render(typeKind, true);
    const collection = wrapper.findComponent({ name: 'CollectionPage' });
    const columns = collection.props('columns') as CollectionColumn[];
    const source = collection.props('source') as CollectionSource<unknown>;

    for (const field of ['created_at', 'updated_at']) {
      expect(columns.find(column => column.key === field)).toMatchObject({
        class: 'w-36', sortField: field, defaultHidden: true,
      });
      expect(source.sortOptions.value.map(option => option.value)).toEqual(expect.arrayContaining([
        `${field}:asc`, `${field}:desc`,
      ]));
    }
    wrapper.unmount();
  });

  it('hides creation without permission for this type', () => {
    expect(render(typeKind, false).find('a').exists()).toBe(false);
  });

  it('hides creation in the trash view', () => {
    window.history.replaceState({}, '', '/?showDeleted=true');

    expect(render(typeKind, true).find('a').exists()).toBe(false);
  });
});
