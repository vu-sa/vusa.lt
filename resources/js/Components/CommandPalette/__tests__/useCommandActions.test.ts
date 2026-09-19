import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { useCommandActions } from '@/Components/CommandPalette/useCommandActions';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const actionIds = () => {
  let ids!: string[];
  mount(defineComponent({ setup: () => { ids = useCommandActions().actions.value.map(action => action.id); return () => h('div'); } }));
  return ids;
};

describe('useCommandActions', () => {
  it('uses navigation and create entries supplied by the catalog', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ adminNavigation: {
      workspaces: [{
        key: 'organization', label: '', description: '',
        sections: [{ key: 'users', label: 'shell.sections.nariai', routeName: 'users.index', routeParams: {}, entityType: 'user', collectionActions: [] }],
        createActions: [{ key: 'new_news', label: 'shell.actions.new_news.title', description: null, entityType: 'news', target: { kind: 'route', routeName: 'news.create' } }],
      }],
    } }));

    expect(actionIds()).toEqual(expect.arrayContaining(['nav-users', 'create-new_news', 'nav-search', 'nav-profile']));
  });

  it('keeps only global exclusions when the catalog is empty', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ adminNavigation: { workspaces: [] } }));
    expect(actionIds()).toEqual(['nav-search', 'nav-profile']);
  });
});
