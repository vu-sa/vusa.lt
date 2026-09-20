import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import ShowAdministration from '@/Pages/Admin/ShowAdministration.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';

let wrapper: ReturnType<typeof mount>;

function createWrapper() {
  return mount(ShowAdministration);
}

/** Shapes a workspace exactly like `AdminNavigationCatalog::for()`'s resolved payload. */
function workspace(overrides: Record<string, unknown>) {
  return {
    key: 'organizacija',
    label: 'shell.workspaces.organizacija.title',
    description: 'shell.workspaces.organizacija.description',
    sections: [],
    createActions: [],
    ...overrides,
  };
}

beforeEach(() => {
  vi.mocked(usePage).mockReturnValue(createMockPage());
});

afterEach(() => {
  wrapper.unmount();
  vi.mocked(usePage).mockReset();
});

describe('ShowAdministration', () => {
  it('says so when adminNavigation is absent', () => {
    wrapper = createWrapper();

    expect(wrapper.text()).toContain('shell.chrome.no_sections');
  });

  it('renders the Organizacija tools (duty_update, duty_periods) as hairline rows', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      adminNavigation: {
        workspaces: [
          workspace({
            createActions: [
              { key: 'duty_update', label: 'shell.actions.duty_update.title', description: null, entityType: 'duty', target: { kind: 'route', routeName: 'duties.updateUsersWizard' } },
              { key: 'duty_periods', label: 'shell.actions.duty_periods.title', description: null, entityType: 'dutiable', target: { kind: 'route', routeName: 'dutiables.timeline' } },
            ],
          }),
        ],
      },
    }));

    wrapper = createWrapper();

    const wizardLink = wrapper.find('a[href="/mocked-route/duties.updateUsersWizard"]');
    const periodsLink = wrapper.find('a[href="/mocked-route/dutiables.timeline"]');
    expect(wizardLink.exists()).toBe(true);
    expect(periodsLink.exists()).toBe(true);
    expect(wizardLink.classes()).toContain('border-b');
    expect(wrapper.html()).not.toContain('gradient');
    expect(wrapper.text()).toContain('shell.actions.duty_update.title');
  });

  it('renders one section per visible workspace, Pradžia included', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      adminNavigation: {
        workspaces: [
          workspace({
            key: 'pradzia',
            label: 'shell.workspaces.pradzia.title',
            sections: [{ key: 'apzvalga', label: 'shell.sections.apzvalga', routeName: 'dashboard', routeParams: {}, entityType: null }],
          }),
          workspace({
            key: 'svetaine',
            label: 'shell.workspaces.svetaine.title',
            sections: [{ key: 'naujienos', label: 'shell.sections.naujienos', routeName: 'news.index', routeParams: {}, entityType: 'news' }],
          }),
        ],
      },
    }));

    wrapper = createWrapper();

    expect(wrapper.text()).toContain('shell.workspaces.pradzia.title');
    expect(wrapper.find('a[href="/mocked-route/dashboard"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('shell.workspaces.svetaine.title');
    expect(wrapper.find('a[href="/mocked-route/news.index"]').exists()).toBe(true);
  });

  it('filters sections and tools by the search query', async () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      adminNavigation: {
        workspaces: [
          workspace({
            key: 'svetaine',
            label: 'shell.workspaces.svetaine.title',
            sections: [
              { key: 'naujienos', label: 'shell.sections.naujienos', routeName: 'news.index', routeParams: {}, entityType: 'news' },
              { key: 'puslapiai', label: 'shell.sections.puslapiai', routeName: 'pages.index', routeParams: {}, entityType: 'page' },
            ],
          }),
        ],
      },
    }));

    wrapper = createWrapper();

    // $t() is mocked to identity, so search against the raw label key.
    await wrapper.find('input').setValue('naujienos');

    expect(wrapper.find('a[href="/mocked-route/news.index"]').exists()).toBe(true);
    expect(wrapper.find('a[href="/mocked-route/pages.index"]').exists()).toBe(false);
  });

  it('shows the empty state when the query matches nothing', async () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      adminNavigation: {
        workspaces: [
          workspace({
            key: 'svetaine',
            label: 'shell.workspaces.svetaine.title',
            sections: [{ key: 'naujienos', label: 'shell.sections.naujienos', routeName: 'news.index', routeParams: {}, entityType: 'news' }],
          }),
        ],
      },
    }));

    wrapper = createWrapper();

    await wrapper.find('input').setValue('zzz-no-match');

    expect(wrapper.text()).toContain('shell.chrome.no_sections');
  });

  it('does not render the category filter dropdown or item-count badges', () => {
    wrapper = createWrapper();

    expect(wrapper.text()).not.toContain('Filtrai');
    expect(wrapper.findComponent({ name: 'DropdownMenu' }).exists()).toBe(false);
  });
});
