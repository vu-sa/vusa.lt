import { mount } from '@vue/test-utils';
import { ref, type Component } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import IndexAgendaItem from '../../Representation/IndexAgendaItem.vue';
import IndexResource from '../../Reservations/IndexResource.vue';
import IndexResponsibility from '../../Permissions/IndexResponsibility.vue';
import IndexMailingList from '../../MailingLists/IndexMailingList.vue';
import IndexGoal from '../../Goals/IndexGoal.vue';
import IndexForm from '../../Forms/IndexForm.vue';
import IndexDuty from '../../People/IndexDuty.vue';
import IndexProblem from '../../Problems/IndexProblem.vue';

import type { AdminRecordAction } from '@/Features/Admin/RecordActions/types';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
const permissions = vi.hoisted(() => ({ update: false, delete: false }));
vi.mock('@/Composables/useRowAbilities', () => ({
  useRowAbilities: () => ({ can: (_id: string, ability: 'update' | 'delete') => permissions[ability], invalidate: vi.fn() }),
}));
vi.mock('@/Composables/useAdminNavigation', () => ({
  useAdminNavigation: () => ({ hasCollectionAction: () => false }),
}));
vi.mock('@/Composables/useCollectionSource', () => {
  const source = () => ({ items: ref([]), query: ref(''), filters: ref({}), activeFilterCount: ref(0), refresh: vi.fn() });
  return { useTypesenseCollectionSource: source, useLocalCollectionSource: source, useDatabaseCollectionSource: source };
});
vi.mock('@/Components/Reservations/useReservationCart', () => ({
  useReservationCart: () => ({ period: ref(null), hasPeriod: ref(false), openSheet: vi.fn() }),
}));
vi.mock('@/Features/Admin/AdminSearch/Composables/useResourceAvailability', () => ({
  useResourceAvailability: () => ({ availability: ref(new Map()), ensure: vi.fn() }),
}));

const emptyPage = { data: [], meta: { total: 0, current_page: 1, per_page: 50, last_page: 1, from: 0, to: 0 }, total: 0, per_page: 50, current_page: 1, last_page: 1 };
const cases: { name: string; component: Component; props: Record<string, unknown>; noDelete?: boolean }[] = [
  { name: 'agenda items', component: IndexAgendaItem, props: { defaultTenantShortnames: [] } },
  { name: 'resources', component: IndexResource, props: { reservationCart: null, mainTenantId: 1, managedTenantIds: [] } },
  { name: 'responsibilities', component: IndexResponsibility, props: { responsibilities: [] } },
  { name: 'mailing lists', component: IndexMailingList, props: { mailingLists: [], tenants: [], domain: '', openCreate: false, canCreate: false, canImport: false, mailmanConfigured: false }, noDelete: true },
  { name: 'goals', component: IndexGoal, props: { goals: [], canCreate: false } },
  { name: 'forms', component: IndexForm, props: { forms: emptyPage, can: { create: false } } },
  { name: 'duties', component: IndexDuty, props: { duties: emptyPage, dutyTypes: [], tenants: [] } },
  { name: 'problems', component: IndexProblem, props: { data: [], meta: emptyPage.meta, categories: [], institutions: [] } },
];

function actionsFor(testCase: typeof cases[number], allowed: boolean, extra: Record<string, unknown> = {}): AdminRecordAction[] {
  permissions.update = allowed;
  permissions.delete = allowed;
  const wrapper = mount(testCase.component, {
    props: testCase.props,
    global: { stubs: {
      CollectionPage: { name: 'CollectionPage', props: ['rowActions'], template: '<div />' },
      CollectionConfirmAction: true, ConfirmDialog: true, MergeRecordsDialog: true,
      MailingListSheetForm: true, ReservationCartBar: true, ReservationCartSheet: true,
    } },
  });
  const actions = wrapper.findComponent({ name: 'CollectionPage' }).props('rowActions')({
    id: 'record-1', title: 'Įrašas', title_lt: 'Įrašas', name: 'Įrašas', name_lt: 'Įrašas',
    can: { update: allowed, delete: allowed }, can_update: allowed, can_delete: allowed, ...extra,
  });
  wrapper.unmount();
  return actions;
}

beforeEach(() => window.history.replaceState({}, '', '/mano'));

describe.each(cases)('$name row abilities', (testCase) => {
  it.each([true, false])('shows edit and delete only when allowed (%s)', (allowed) => {
    const actions = actionsFor(testCase, allowed);
    expect(actions.some(action => action.role === 'open')).toBe(true);
    expect(actions.some(action => action.role === 'edit')).toBe(allowed);
    expect(actions.some(action => action.destructive)).toBe(allowed && !testCase.noDelete);
    expect(actions.some(action => action.disabled)).toBe(false);
    if (['forms', 'mailing lists', 'responsibilities'].includes(testCase.name)) {
      expect(actions[0]).toMatchObject({ role: 'open', primary: true, label: 'Atidaryti' });
      if (allowed) expect(actions.find(action => action.role === 'edit')).toMatchObject({ menuOnly: true });
    }
  });
});

it('keeps protected responsibilities and deletion blockers hidden', () => {
  const responsibility = cases.find(testCase => testCase.name === 'responsibilities')!;
  const actions = actionsFor(responsibility, true, { is_super_admin: true, delete_blocked: true });
  expect(actions.map(action => action.role)).toEqual(['open']);
});
