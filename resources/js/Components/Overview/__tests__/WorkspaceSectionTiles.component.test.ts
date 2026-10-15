import { usePage } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import WorkspaceSectionTiles from '../WorkspaceSectionTiles.vue';

import { atstovavimas, pradzia, workspace } from '@/Components/Layouts/Shell/__tests__/fixtures';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

beforeEach(() => {
  vi.mocked(usePage).mockReturnValue(createMockPage({
    adminNavigation: { workspaces: [pradzia, atstovavimas, workspace('sistema', [])] },
  }) as ReturnType<typeof usePage>);
});

describe('WorkspaceSectionTiles', () => {
  it('tiles the workspace sections without its own overview', () => {
    const wrapper = mount(WorkspaceSectionTiles, { props: { workspaceKey: 'atstovavimas' } });

    expect(wrapper.findAll('a').map(link => link.attributes('href'))).toEqual(['/mocked-route/meetings.index']);
    expect(wrapper.find('[data-tile-description]').text()).toBe('shell.section_descriptions.posedziai');
  });

  it('renders nothing when the user can open none of its sections', () => {
    expect(mount(WorkspaceSectionTiles, { props: { workspaceKey: 'sistema' } }).find('[data-slot="navigation-tiles"]').exists()).toBe(false);
    expect(mount(WorkspaceSectionTiles, { props: { workspaceKey: 'unknown' } }).find('[data-slot="navigation-tiles"]').exists()).toBe(false);
  });
});
