import { beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { useCommandActions } from '@/Components/CommandPalette/useCommandActions';
import { useActionWindow } from '@/Composables/useActionWindow';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@/Composables/useActionWindow', () => ({ useActionWindow: vi.fn() }));

const section = (key: string, routeName: string, entityType: string | null = null) => ({
  key,
  label: `shell.sections.${key}`,
  routeName,
  routeParams: {},
  entityType,
  collectionActions: [],
  matches: [routeName.endsWith('.index') ? `${routeName.slice(0, -5)}*` : routeName],
});

const workspace = (key: string, sections: ReturnType<typeof section>[], createActions: unknown[] = []) => ({
  key,
  label: `shell.workspaces.${key}.title`,
  description: '',
  sections,
  createActions,
});

const resolve = () => {
  let resolved!: ReturnType<typeof useCommandActions>;
  mount(defineComponent({
    setup() {
      resolved = useCommandActions();

      return () => h('div');
    },
  }));

  return resolved;
};

const open = vi.fn();

beforeEach(() => {
  open.mockClear();
  vi.mocked(useActionWindow).mockReturnValue({ open } as unknown as ReturnType<typeof useActionWindow>);
});

describe('useCommandActions', () => {
  it('uses navigation and create entries supplied by the catalog', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ adminNavigation: {
      workspaces: [workspace('organizacija', [section('nariai', 'users.index', 'user')], [
        { key: 'new_news', label: 'shell.actions.new_news.title', description: null, entityType: 'news', target: { kind: 'route', routeName: 'news.create' } },
      ])],
    } }));

    expect(resolve().actions.value.map(action => action.id))
      .toEqual(expect.arrayContaining(['nav-organizacija-nariai', 'create-new_news', 'nav-search', 'nav-profile']));
  });

  it('keeps only global exclusions when the catalog is empty', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ adminNavigation: { workspaces: [] } }));

    expect(resolve().actions.value.map(action => action.id)).toEqual(['nav-search', 'nav-profile']);
  });

  it('gives sections that share a label distinct ids and their workspace as a secondary label', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ adminNavigation: {
      workspaces: [
        workspace('pradzia', [section('apzvalga', 'dashboard')]),
        workspace('svetaine', [section('apzvalga', 'dashboard.svetaine')]),
      ],
    } }));

    const overviews = resolve().actions.value.filter(action => action.id.endsWith('-apzvalga'));

    expect(overviews.map(action => action.id)).toEqual(['nav-pradzia-apzvalga', 'nav-svetaine-apzvalga']);
    expect(overviews.map(action => action.workspaceKey)).toEqual(['pradzia', 'svetaine']);
  });

  it('opens the ActionWindow for a screen-target create action instead of doing nothing', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ adminNavigation: {
      workspaces: [workspace('atstovavimas', [section('posedziai', 'meetings.index', 'meeting')], [
        { key: 'new_meeting', label: 'shell.actions.new_meeting.title', description: null, entityType: 'meeting', target: { kind: 'screen', screen: 'meeting.institution' } },
      ])],
    } }));

    resolve().actions.value.find(action => action.id === 'create-new_meeting')?.action();

    expect(open).toHaveBeenCalledWith({ flow: 'meeting.create' });
  });

  it('marks catalog sections as pinnable by their relative path', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ adminNavigation: {
      workspaces: [workspace('atstovavimas', [section('posedziai', 'meetings.index', 'meeting')])],
    } }));

    const action = resolve().actions.value.find(candidate => candidate.id === 'nav-atstovavimas-posedziai');

    expect(action?.page).toEqual({ routeName: 'meetings.index', href: '/mocked-route/meetings.index', title: 'shell.sections.posedziai' });
    expect(resolve().actions.value.find(candidate => candidate.id === 'nav-search')?.page).toBeUndefined();
  });

  it('ranks entries from the workspace the user is standing in first', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ adminNavigation: {
      workspaces: [
        workspace('pradzia', [section('uzduotys', 'userTasks')]),
        workspace('svetaine', [section('naujienos', 'news.index', 'news')]),
      ],
    } }));

    // `route().current()` is not part of the shared Ziggy mock, so the active workspace falls
    // back to the first one; ranking is exercised through rankByWorkspace directly.
    const { rankByWorkspace, activeWorkspace } = resolve();
    const items = [{ workspaceKey: 'svetaine' }, { workspaceKey: 'pradzia' }, { workspaceKey: undefined }];

    expect(activeWorkspace.value?.key).toBe('pradzia');
    expect(rankByWorkspace(items).map(item => item.workspaceKey)).toEqual(['pradzia', 'svetaine', undefined]);
  });

  it('resolves the workspace that owns an entity type', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ adminNavigation: {
      workspaces: [workspace('atstovavimas', [section('posedziai', 'meetings.index', 'meeting')])],
    } }));

    const { workspaceKeyForEntity } = resolve();

    expect(workspaceKeyForEntity('meeting')).toBe('atstovavimas');
    expect(workspaceKeyForEntity('news')).toBeUndefined();
  });

  it('filters by label, keyword and workspace title', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ adminNavigation: {
      workspaces: [workspace('svetaine', [section('naujienos', 'news.index', 'news')])],
    } }));

    expect(resolve().filterActions('naujienos').map(action => action.id)).toEqual(['nav-svetaine-naujienos']);
    expect(resolve().filterActions('zzz')).toEqual([]);
  });
});
