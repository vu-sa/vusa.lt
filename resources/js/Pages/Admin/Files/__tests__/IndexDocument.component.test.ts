import { mount } from '@vue/test-utils';
import { ref } from 'vue';
import { describe, expect, it, vi } from 'vitest';

import IndexDocument from '../IndexDocument.vue';

import type { AdminRecordAction } from '@/Features/Admin/RecordActions/types';
import type { DocumentSearchResult } from '@/Shared/Search/types';

const abilityState = vi.hoisted(() => ({ allowed: true }));
vi.mock('@/Composables/useRowAbilities', () => ({ useRowAbilities: () => ({ can: () => abilityState.allowed, invalidate: vi.fn() }) }));

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@/Composables/useAdminNavigation', () => ({ useAdminNavigation: () => ({ activeWorkspace: ref(null) }) }));
vi.mock('@/Composables/useCollectionSource', () => ({
  useTypesenseCollectionSource: () => ({ filters: ref({}) }),
}));

function actionsFor(anonymousUrl: string | null, allowed = true): AdminRecordAction[] {
  abilityState.allowed = allowed;
  const wrapper = mount(IndexDocument, {
    props: {
      importantContentTypes: [], defaultTenantShortnames: [], discovery: null,
      abilities: { create: false, update: allowed, updateTenantShortnames: ['MIF'], updateInstitutionIds: [] },
    },
    global: { stubs: {
      CollectionPage: { name: 'CollectionPage', props: ['rowActions'], template: '<div />' },
      ConfirmDialog: true,
    } },
  });
  const rowActions = wrapper.findComponent({ name: 'CollectionPage' }).props('rowActions') as (item: DocumentSearchResult) => AdminRecordAction[];
  const actions = rowActions({ id: 'd1', title: 'Dokumentas', tenant_shortname: 'MIF', anonymous_url: anonymousUrl } as DocumentSearchResult);
  wrapper.unmount();
  return actions;
}

describe('document collection actions', () => {
  it('labels editing when a permitted document has no public URL', () => {
    expect(actionsFor(null)).toEqual([
      expect.objectContaining({ key: 'edit', role: 'edit', primary: true, href: route('documents.edit', 'd1') }),
      expect.objectContaining({ key: 'refresh' }),
      expect.objectContaining({ key: 'hide' }),
    ]);
  });

  it('keeps the file in the menu beside the SharePoint editor', () => {
    const actions = actionsFor('https://example.com/file');

    expect(actions[0]).toMatchObject({ key: 'edit', role: 'edit', primary: true });
    expect(actions[1]).toMatchObject({ key: 'open', menuOnly: true, external: true, href: 'https://example.com/file' });
    expect(actions[2]).toMatchObject({ key: 'refresh' });
  });

  it('offers only the external file to read-only users', () => {
    expect(actionsFor('https://example.com/file', false)).toEqual([
      expect.objectContaining({ key: 'open', role: 'open', primary: true, external: true }),
    ]);
    expect(actionsFor(null, false)).toEqual([]);
  });
});
