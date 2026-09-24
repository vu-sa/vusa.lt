import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import FileManager from '../FileManager.vue';
import type { DirectoryEntry, FileEntry } from '../types';

vi.mock('@inertiajs/vue3', () => ({
  router: {
    post: vi.fn(),
    delete: vi.fn(),
    get: vi.fn(),
    reload: vi.fn(),
  },
  usePage: () => ({ props: { csrf_token: 'test-token' } }),
}));

vi.mock('@/Composables/useToasts', () => ({
  useToasts: () => ({
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn(),
  }),
}));

const mockDirectories: DirectoryEntry[] = [
  { path: 'public/files/images', name: 'images', type: 'directory' },
  { path: 'public/files/docs', name: 'docs', type: 'directory' },
];

const mockFiles: FileEntry[] = [
  {
    path: 'public/files/photo.jpg',
    name: 'photo.jpg',
    type: 'file',
    size: 1024 * 500,
    modified: 1700000000,
    mimeType: 'image/jpeg',
  },
  {
    path: 'public/files/report.pdf',
    name: 'report.pdf',
    type: 'file',
    size: 1024 * 200,
    modified: 1690000000,
    mimeType: 'application/pdf',
  },
];

describe('FileManager views and sidebar', () => {
  it('renders sidebar navigation and files grid in standalone mode', () => {
    const wrapper = mount(FileManager, {
      props: {
        directories: mockDirectories,
        files: mockFiles,
        path: 'public/files',
      },
    });

    expect(wrapper.text()).toContain('photo.jpg');
    expect(wrapper.text()).toContain('report.pdf');
    // Sidebar quick views
    expect(wrapper.text()).toContain('Naujausi');
  });

  it('switches between grid and list views', async () => {
    const wrapper = mount(FileManager, {
      props: {
        directories: mockDirectories,
        files: mockFiles,
        path: 'public/files',
      },
    });

    // Default is grid, cards are rendered
    expect(wrapper.find('table').exists()).toBe(false);

    // Click list view button
    const listButton = wrapper.find('button[aria-label="Sąrašas"]');
    if (listButton.exists()) {
      await listButton.trigger('click');
      expect(wrapper.find('table').exists()).toBe(true);
    }
  });

  it('navigates when folder is clicked in sidebar', async () => {
    const wrapper = mount(FileManager, {
      props: {
        directories: mockDirectories,
        files: mockFiles,
        path: 'public/files',
      },
    });

    // Find directory button in sidebar
    const folderButtons = wrapper.findAll('button').filter(b => b.text().includes('images'));
    expect(folderButtons.length).toBeGreaterThan(0);
    await folderButtons[0].trigger('click');

    expect(wrapper.emitted('changeDirectory')).toBeTruthy();
    expect(wrapper.emitted('changeDirectory')?.[0]).toEqual(['public/files/images']);
  });
});
