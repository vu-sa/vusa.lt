import { usePage } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { ref, type Component } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import IndexBanner from '../IndexBanner.vue';
import IndexNews from '../IndexNews.vue';
import IndexPages from '../IndexPages.vue';
import IndexCalendarEvents from '../../Calendar/IndexCalendarEvents.vue';
import IndexDomainTypes from '../../ModelMeta/IndexDomainTypes.vue';
import IndexStudyProgram from '../../People/IndexStudyProgram.vue';
import IndexStudySet from '../../StudySets/IndexStudySet.vue';

import type { AdminRecordAction } from '@/Features/Admin/RecordActions/types';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@/Composables/useAdminNavigation', () => ({
  useAdminNavigation: () => ({ hasCollectionAction: () => false }),
}));
vi.mock('@/Composables/useFeatureSpotlight', () => ({
  useFeatureSpotlight: () => ({ isDismissed: ref(true), dismiss: vi.fn() }),
}));
vi.mock('@/Composables/useCollectionSource', () => {
  const source = () => ({
    items: ref([]),
    query: ref(''),
    filters: ref({}),
    activeFilterCount: ref(0),
    refresh: vi.fn(),
  });
  const isTrashView = () => new URLSearchParams(window.location.search).get('showDeleted') === 'true';
  return {
    isTrashView,
    useDatabaseCollectionSource: source,
    useLocalCollectionSource: source,
    useTypesenseCollectionSource: source,
    useTrashCollectionSource: source,
    useTrashAwareSource: (live: () => unknown, trash: () => unknown) => isTrashView() ? trash() : live(),
  };
});

interface CollectionCase {
  model: string;
  prefix: string;
  component: Component;
  props: Record<string, unknown>;
  duplicate?: boolean;
  banner?: boolean;
  domainType?: boolean;
}

const emptyPage = { data: [], meta: { total: 0, current_page: 1, per_page: 50, last_page: 1, from: 0, to: 0 } };
const cases: CollectionCase[] = [
  { model: 'news', prefix: 'news', component: IndexNews, props: { deletedCount: 0 }, duplicate: true },
  { model: 'page', prefix: 'pages', component: IndexPages, props: { deletedCount: 0 } },
  { model: 'banner', prefix: 'banners', component: IndexBanner, props: { banners: emptyPage }, banner: true },
  { model: 'calendar', prefix: 'calendar', component: IndexCalendarEvents, props: { calendar: emptyPage, eventTypes: [], deletedCount: 0 }, duplicate: true },
  { model: 'studySet', prefix: 'studySets', component: IndexStudySet, props: { studySets: [], deletedCount: 0 } },
  { model: 'studyProgram', prefix: 'studyPrograms', component: IndexStudyProgram, props: { studyPrograms: [], degreeOptions: [], deletedCount: 0 } },
  { model: 'institutionType', prefix: 'institutionTypes', component: IndexDomainTypes, props: { typeKind: 'institutionType', types: [], deletedCount: 0 }, domainType: true },
  { model: 'dutyType', prefix: 'dutyTypes', component: IndexDomainTypes, props: { typeKind: 'dutyType', types: [], deletedCount: 0 }, domainType: true },
];
const item = { id: '1', title: 'Įrašas', name: 'Įrašas' };

function actionsFor(testCase: CollectionCase, allowed: boolean, trash = false, canCreate = allowed): AdminRecordAction[] {
  window.history.replaceState({}, '', `/mano/${testCase.prefix}${trash ? '?showDeleted=true' : ''}`);
  vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { can: {
    create: { [testCase.model]: canCreate },
    delete: { [testCase.model]: allowed },
    restore: { [testCase.model]: allowed },
    forceDelete: { [testCase.model]: allowed },
  } } }));
  const wrapper = mount(testCase.component, {
    props: { ...testCase.props, ...(testCase.banner ? { showDeleted: trash } : {}) },
    global: { stubs: {
      CollectionPage: { name: 'CollectionPage', props: ['rowActions'], template: '<div />' },
      CollectionConfirmAction: true,
      ConfirmDialog: true,
      MergeRecordsDialog: true,
    } },
  });
  const rowActions = wrapper.findComponent({ name: 'CollectionPage' }).props('rowActions') as (record: typeof item) => AdminRecordAction[];
  const actions = rowActions(item);
  wrapper.unmount();
  return actions;
}

const kinds = (actions: AdminRecordAction[]) => actions.map(action => action.key.split(':')[0]);

describe.each(cases)('$prefix row actions', (testCase) => {
  beforeEach(() => vi.clearAllMocks());

  it.each([false, true])('retains labelled editor access and the existing delete/duplicate gates (%s)', (allowed) => {
    const actions = actionsFor(testCase, allowed);
    expect(actions[0]).toMatchObject({ role: 'edit', label: 'Redaguoti', href: route(`${testCase.prefix}.edit`, '1') });
    expect(kinds(actions)).toEqual([
      'edit',
      ...(testCase.duplicate && allowed ? ['duplicate'] : []),
      ...(!testCase.banner && allowed ? ['delete'] : []),
    ]);
  });

  it.each([false, true])('preserves the original restore and force-delete gates (%s)', (allowed) => {
    const actions = actionsFor(testCase, allowed, true);
    expect(kinds(actions)).toEqual([
      ...(testCase.banner || allowed ? ['restore'] : []),
      ...(allowed ? ['forceDelete'] : []),
    ]);
    if (testCase.banner && !allowed) {
      expect(actions[0].primary).toBe(true);
    }
  });

  if (testCase.domainType) {
    it('uses the domain delete gate independently of creation permission', () => {
      expect(kinds(actionsFor(testCase, false, false, true))).toEqual(['edit']);
      expect(kinds(actionsFor(testCase, true, false, false))).toEqual(['edit', 'delete']);
    });
  }
});
