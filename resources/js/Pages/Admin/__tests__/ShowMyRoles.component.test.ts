import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import ShowMyRoles from '@/Pages/Admin/ShowMyRoles.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const term = (overrides: Record<string, unknown> = {}) => ({
  id: 't1',
  dutyName: 'Studentų atstovas',
  dutyHref: null,
  institutionName: 'VU MIF studentų atstovybė',
  institutionHref: null,
  tenant: 'VU SA MIF',
  representsTenant: null,
  startDate: '2026-02-01',
  endDate: null,
  isExOfficio: false,
  roles: ['Studentų atstovas'],
  ...overrides,
});

const emptyAccess = { isSuperAdmin: false, directRoles: [], current: [], upcoming: [], ended: [], history: [] };

function mountPage(access: Record<string, unknown>, workspaces: unknown[] = []) {
  vi.mocked(usePage).mockReturnValue(createMockPage({ adminNavigation: { workspaces } }) as ReturnType<typeof usePage>);

  return mount(ShowMyRoles, { props: { access: { ...emptyAccess, ...access } as never } });
}

beforeEach(() => {
  vi.clearAllMocks();
});

describe('ShowMyRoles', () => {
  it('lists the current duty with its institution, term and roles', () => {
    const wrapper = mountPage({ current: [term()] });

    const row = wrapper.get('[data-testid="current-duties"] [data-slot="my-duty-term"]');
    expect(row.text()).toContain('Studentų atstovas');
    expect(row.text()).toContain('VU MIF studentų atstovybė');
    expect(row.text()).toContain('2026-02-01');
  });

  it('marks ex-officio seats and cross-tenant representation', () => {
    const wrapper = mountPage({ current: [term({ isExOfficio: true, representsTenant: 'VU SA ChGF' })] });

    expect(wrapper.text()).toContain('access.term.ex_officio');
    expect(wrapper.text()).toContain('access.term.represents');
  });

  it('links a duty only when the server said the user may open it', () => {
    const linked = mountPage({ current: [term({ dutyHref: '/mano/duties/1' })] });
    const plain = mountPage({ current: [term()] });

    expect(linked.find('a[href="/mano/duties/1"]').exists()).toBe(true);
    expect(plain.find('[data-testid="current-duties"] a').exists()).toBe(false);
  });

  it('collapses empty sections to one line instead of drawing empty lists', () => {
    const wrapper = mountPage({});

    expect(wrapper.find('[data-testid="current-duties"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="upcoming-duties"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="ended-duties"]').exists()).toBe(false);
    expect(wrapper.text()).toContain('access.current.empty');
  });

  it('shows what the user can open, straight from the navigation catalog', () => {
    const wrapper = mountPage({}, [{
      key: 'atstovavimas',
      label: 'shell.workspaces.atstovavimas.title',
      sections: [{ key: 'posedziai', label: 'shell.sections.posedziai', routeName: 'meetings.index', routeParams: {} }],
    }]);

    const links = wrapper.findAll('[data-testid="capabilities"] a');
    expect(links).toHaveLength(1);
    expect(links[0].attributes('href')).toContain('meetings.index');
  });

  it('says so for a super admin instead of listing every section', () => {
    const wrapper = mountPage({ isSuperAdmin: true });

    expect(wrapper.find('[data-testid="super-admin"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="capabilities"]').exists()).toBe(false);
  });

  it('lists the dated changes, each in its own words, and collapses when there are none', () => {
    const history = [
      { kind: 'started', dutyName: 'Studentų atstovas', institutionName: 'VU MIF', date: '2026-09-01', effectiveOn: '2026-09-01', isExOfficio: false },
      { kind: 'ended', dutyName: 'Kita pareigybė', institutionName: null, date: '2026-03-31', effectiveOn: '2026-04-01', isExOfficio: false },
    ];

    const rows = mountPage({ history }).findAll('[data-testid="access-history"] [data-slot="access-change"]');

    expect(rows).toHaveLength(2);
    expect(rows[0].text()).toContain('2026-09-01');
    expect(rows[0].text()).toContain('access.history.started');
    expect(rows[0].text()).toContain('VU MIF');
    expect(rows[1].text()).toContain('access.history.ended');

    expect(mountPage({}).find('[data-testid="access-history"]').exists()).toBe(false);
  });
});
