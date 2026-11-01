import { usePage } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { ref, type Component } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import IndexBanner from '../IndexBanner.vue';
import IndexNews from '../IndexNews.vue';
import IndexPages from '../IndexPages.vue';
import IndexCalendarEvents from '../../Calendar/IndexCalendarEvents.vue';
import IndexEventType from '../../Calendar/IndexEventType.vue';
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
    if (testCase.domainType) {
      expect(actions[0]).toMatchObject({ role: 'open', primary: true, label: 'Atidaryti', href: route(`${testCase.prefix}.show`, '1') });
      expect(actions[1]).toMatchObject({ role: 'edit', menuOnly: true, label: 'Redaguoti', href: route(`${testCase.prefix}.edit`, '1') });
    }
    else {
      expect(actions[0]).toMatchObject({ role: 'edit', label: 'Redaguoti', href: route(`${testCase.prefix}.edit`, '1') });
    }
    expect(kinds(actions)).toEqual([
      ...(testCase.domainType ? ['open'] : []),
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
      expect(kinds(actionsFor(testCase, false, false, true))).toEqual(['open', 'edit']);
      expect(kinds(actionsFor(testCase, true, false, false))).toEqual(['open', 'edit', 'delete']);
    });
  }
});

const publishingCases = cases.filter(testCase => ['news', 'pages', 'calendar', 'banners'].includes(testCase.prefix));
const publishingItem = {
  id: '1', title: 'Įrašas', lang: 'lt', draft: true, is_draft: true, is_active: false,
};

function mountPublishingPage(testCase: CollectionCase, view = 'rows') {
  window.history.replaceState({}, '', `/mano/${testCase.prefix}`);
  vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { can: { create: { [testCase.model]: true } } } }));
  const preview = { template: '<div><slot name="status" /><slot name="actions" /></div>' };

  return mount(testCase.component, {
    props: testCase.props,
    global: { stubs: {
      CollectionPage: {
        name: 'CollectionPage',
        props: ['rowActions'],
        setup: () => ({ item: publishingItem, view, statusColumn: { key: testCase.banner ? 'is_active' : 'status' } }),
        template: `<div>
          <section data-testid="row"><slot name="row" :item="item" :view="view" :actions="rowActions(item)" /></section>
          <section data-testid="cell"><slot name="cell" :item="item" :column="statusColumn" /></section>
          <section data-testid="preview"><slot name="preview" :item="item" :actions="rowActions(item)" /></section>
        </div>`,
      },
      CollectionRow: { name: 'CollectionRow', props: ['actions'], template: '<article><slot name="lead" /><slot /></article>' },
      CollectionStatusIcon: true,
      CollectionStatusMenu: true,
      CollectionRowActions: true,
      StatusBadge: true,
      NewsPreview: preview,
      PagePreview: preview,
      CalendarEventPreview: preview,
      CollectionConfirmAction: true,
      ConfirmDialog: true,
    } },
  });
}

describe.each(publishingCases)('$prefix publishing placement', (testCase) => {
  it('shows status icons in rows and editable statuses in table cells and detail cards', () => {
    const wrapper = mountPublishingPage(testCase);
    const row = wrapper.get('[data-testid="row"]');
    expect(row.findComponent({ name: 'CollectionStatusMenu' }).exists()).toBe(false);
    expect(row.findComponent({ name: 'CollectionStatusIcon' }).exists()).toBe(true);
    const cell = wrapper.get('[data-testid="cell"]');
    expect(cell.findComponent({ name: 'CollectionStatusMenu' }).props('editable')).toBe(true);
    const preview = wrapper.get('[data-testid="preview"]');
    expect(preview.findComponent({ name: 'CollectionStatusMenu' }).exists()).toBe(!testCase.banner);
    if (!testCase.banner) {
      expect(preview.findComponent({ name: 'CollectionStatusMenu' }).props('editable')).toBe(true);
      expect(preview.findComponent({ name: 'CollectionRowActions' }).props()).toMatchObject({ emphasis: true, fullLabels: true });
    }
    wrapper.unmount();
  });

  if (!testCase.banner) {
    it('leaves actions and the status badge to the detail card in preview view', () => {
      const wrapper = mountPublishingPage(testCase, 'preview');
      const row = wrapper.get('[data-testid="row"]');
      expect(row.findComponent({ name: 'CollectionRow' }).props('actions')).toEqual([]);
      expect(row.findComponent({ name: 'StatusBadge' }).exists()).toBe(false);
      expect(row.findComponent({ name: 'CollectionStatusIcon' }).exists()).toBe(false);
      wrapper.unmount();
    });
  }
});

it('marks stopped event types before the name and mutes them without a duplicate status column', () => {
  const inactive = { id: 1, name: { lt: 'Tipas', en: 'Type' }, slug: 'type', is_active: false };
  const wrapper = mount(IndexEventType, {
    props: { abilities: { update: true, delete: false, restore: false }, eventTypes: emptyPage },
    global: { stubs: {
      CollectionPage: {
        name: 'CollectionPage', props: ['columns', 'rowMuted'],
        setup: () => ({ item: inactive }),
        template: '<div><slot name="row" :item="item" :actions="[]" /></div>',
      },
      CollectionRow: { template: '<article><slot /></article>' },
      EventTypeSheetForm: true, ConfirmDialog: true,
    } },
  });
  const collection = wrapper.findComponent({ name: 'CollectionPage' });
  expect(collection.props('rowMuted')(inactive)).toBe(true);
  expect(collection.props('rowMuted')({ ...inactive, is_active: true })).toBe(false);
  expect(collection.props('columns').some((column: { key: string }) => column.key === 'is_active')).toBe(false);
  expect(wrapper.find('.sr-only').text()).toBe('Neaktyvus:');
  wrapper.unmount();
});
