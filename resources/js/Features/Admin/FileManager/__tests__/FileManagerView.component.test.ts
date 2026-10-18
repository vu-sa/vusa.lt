import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import FileManager from '../FileManager.vue';
import type { DirectoryEntry, FileEntry } from '../types';

import CollectionSelectionBar from '@/Components/Collection/CollectionSelectionBar.vue';
import { commonStubs } from '@/tests/stubs';

const directories: DirectoryEntry[] = [
  { path: 'public/files/images', name: 'images', type: 'directory', items: 3 },
  { path: 'public/files/old', name: 'old', type: 'directory', items: 0 },
];

const files: FileEntry[] = [
  { path: 'public/files/photo.jpg', name: 'photo.jpg', type: 'file', size: 1024 * 500, modified: 1700000000 },
  { path: 'public/files/report.pdf', name: 'report.pdf', type: 'file', size: 1024 * 200, modified: 1690000000 },
  { path: 'public/files/notes.docx', name: 'notes.docx', type: 'file', size: 1024, modified: 1680000000 },
];

const wrappers: ReturnType<typeof mount>[] = [];

beforeEach(() => {
  localStorage.clear();
});

afterEach(() => {
  wrappers.splice(0).forEach(wrapper => wrapper.unmount());
  vi.mocked(router.delete).mockReset();
  vi.mocked(router.post).mockReset();
});

function mountManager(props: Record<string, unknown> = {}) {
  const wrapper = mount(FileManager, {
    props: { directories, files, path: 'public/files', ...props },
    attachTo: document.body,
    global: { stubs: { ...commonStubs } },
  });
  wrappers.push(wrapper);
  return wrapper;
}

function openConfirm(wrapper: ReturnType<typeof mount>) {
  return wrapper.findAllComponents({ name: 'ConfirmDialog' }).find(dialog => dialog.props('open'))!;
}

function respondToUsageScan(files: Record<string, unknown>[]) {
  vi.mocked(router.post).mockImplementationOnce((_url, _data, options) => {
    (options as { onSuccess: (page: unknown) => void }).onSuccess({ props: { flash: { data: { files } } } });
    (options as { onFinish: () => void }).onFinish();
  });
}

function usageOf(path: string, used = false) {
  return {
    path,
    is_safe_to_delete: !used,
    total_usages: used ? 1 : 0,
    scanned_models: ['news'],
    usage_details: used ? [{ model_type: 'news', model_class: 'App\\Models\\News', id: 7, title: 'Rudens šventė', url: '/mano/news/7/edit' }] : [],
  };
}

async function checkUsageAndDelete(wrapper: ReturnType<typeof mount>) {
  const dialog = wrapper.find('[data-testid="delete-usage-check"]');
  await dialog.findAll('button').find(button => button.text().includes('files.ui.check_usage'))!.trigger('click');
  await wrapper.find('[data-testid="delete-files-confirm"]').trigger('click');
}

function fileNames(wrapper: ReturnType<typeof mount>) {
  return wrapper.findAll('[data-slot="file-item"]').map(item => item.text());
}

describe('FileManager', () => {
  it('lists the folder\'s files with the sidebar views', () => {
    const wrapper = mountManager();

    expect(wrapper.text()).toContain('photo.jpg');
    expect(wrapper.text()).toContain('report.pdf');
    expect(wrapper.text()).toContain('files.ui.view_recent');
  });

  it('renders thumbnails in the table view, not only type icons', async () => {
    const wrapper = mountManager();

    await wrapper.find('[role="radio"][aria-label="Lentelė"]').trigger('click');

    expect(wrapper.find('table').exists()).toBe(true);
    const image = wrapper.find('table img');
    expect(image.exists()).toBe(true);
    expect(image.attributes('src')).toContain('files.thumbnail');
  });

  it('opens a folder from the sidebar tree', async () => {
    const wrapper = mountManager();

    const folder = wrapper.find('[data-slot="file-manager-sidebar"]').findAll('button').find(b => b.text().includes('images'));
    await folder!.trigger('click');

    expect(wrapper.emitted('changeDirectory')?.[0]).toEqual(['public/files/images']);
  });

  it('offers delete only on an empty folder and posts it to the folder route', async () => {
    const wrapper = mountManager();
    const vm = wrapper.vm as unknown as {
      folderActionsFor: (d: DirectoryEntry) => { actions: { key: string }[]; note?: string };
    };

    expect(vm.folderActionsFor(directories[0]).actions.map(a => a.key)).not.toContain('delete');
    expect(vm.folderActionsFor(directories[0]).note).toBe('files.ui.delete_folder_not_empty');
    expect(vm.folderActionsFor(directories[1]).actions.map(a => a.key)).toContain('delete');

    wrapper.findComponent({ name: 'FolderStrip' }).vm.$emit('action', 'delete', directories[1]);
    await wrapper.vm.$nextTick();
    openConfirm(wrapper).vm.$emit('confirm');

    expect(router.delete).toHaveBeenCalledWith(expect.stringContaining('files.deleteDirectory'), expect.any(Object));
  });

  it('hides folder delete from someone who may not delete folders here', () => {
    const wrapper = mountManager({
      abilities: { upload: true, createDirectory: true, deleteDirectory: false, update: false, delete: false },
    });
    const vm = wrapper.vm as unknown as { folderActionsFor: (d: DirectoryEntry) => { actions: unknown[]; note?: string } };

    expect(vm.folderActionsFor(directories[1])).toEqual({ actions: [], note: undefined });
  });

  it('closes the details sheet after deleting its file, without blanking the list', async () => {
    vi.mocked(router.delete).mockImplementation((_url, options) => {
      (options as { onSuccess: (page: unknown) => void }).onSuccess({ props: { flash: {} } });
    });
    const wrapper = mountManager();

    await wrapper.findAll('[data-slot="file-item"] button[aria-label="report.pdf"]')[0].trigger('click');
    expect(wrapper.findComponent({ name: 'FilePropertiesDrawer' }).props('file')).toMatchObject({ name: 'report.pdf' });

    respondToUsageScan([usageOf('public/files/report.pdf')]);
    wrapper.findComponent({ name: 'FilePropertiesDrawer' }).vm.$emit('delete');
    await wrapper.vm.$nextTick();
    await checkUsageAndDelete(wrapper);

    expect(router.delete).toHaveBeenCalledWith(expect.stringContaining('files.delete?'), expect.any(Object));
    expect(wrapper.findComponent({ name: 'FilePropertiesDrawer' }).props('file')).toBeNull();
    expect(wrapper.find('[data-testid="file-grid-skeleton"]').exists()).toBe(false);
    expect(wrapper.emitted('update')).toBeUndefined();
  });

  it('keeps file delete locked until the usage check has run', async () => {
    const wrapper = mountManager();

    await wrapper.findAll('[data-slot="file-item"] button[aria-label="report.pdf"]')[0].trigger('click');
    wrapper.findComponent({ name: 'FilePropertiesDrawer' }).vm.$emit('delete');
    await wrapper.vm.$nextTick();

    expect(wrapper.find('[data-testid="delete-files-confirm"]').attributes('disabled')).toBeDefined();
    expect(wrapper.text()).toContain('files.ui.delete_check_first');
    expect(router.delete).not.toHaveBeenCalled();
  });

  it('checks every ticked file before a bulk delete and names the ones in use', async () => {
    const wrapper = mountManager();
    const items = wrapper.findAll('[data-slot="file-item"] > span');
    await items[0].trigger('click');
    await items[2].trigger('click', { shiftKey: true });
    const ticked = files.map(file => file.path);

    await wrapper.findComponent(CollectionSelectionBar).findAll('button').find(button => button.text().trim() === 'files.ui.delete')!.trigger('click');
    respondToUsageScan([usageOf(ticked[0], true), usageOf(ticked[1]), usageOf(ticked[2])]);
    const check = wrapper.find('[data-testid="delete-usage-check"]').findAll('button').find(button => button.text().includes('files.ui.check_usage'));
    await check!.trigger('click');

    expect(router.post).toHaveBeenCalledWith(expect.stringContaining('files.scanUsageBulk'), { paths: expect.arrayContaining(ticked) }, expect.any(Object));
    const usage = wrapper.find('[data-testid="delete-usage-check"]');
    expect(usage.text()).toContain('files.ui.delete_some_used');
    expect(usage.findAll('[data-testid="file-usage"]')).toHaveLength(1);
    expect(usage.text()).toContain('photo.jpg');

    await wrapper.find('[data-testid="delete-files-confirm"]').trigger('click');

    expect(router.delete).toHaveBeenCalledWith(expect.stringContaining('files.bulkDelete'), expect.objectContaining({ data: { paths: expect.arrayContaining(ticked) } }));
  });

  it('narrows to a kind from the type filter', async () => {
    const wrapper = mountManager();

    await wrapper.findComponent({ name: 'TypeFilterMenu' }).vm.$emit('update:modelValue', 'pdf');

    expect(fileNames(wrapper)).toHaveLength(1);
    expect(fileNames(wrapper)[0]).toContain('report.pdf');
  });

  it('ticks a range with shift-click', async () => {
    const wrapper = mountManager();
    const items = wrapper.findAll('[data-slot="file-item"] > span');

    await items[0].trigger('click');
    await items[2].trigger('click', { shiftKey: true });

    expect(wrapper.text()).toContain('files.ui.checked');
    expect(wrapper.findAll('[data-slot="file-item"] [data-slot="checkbox"][data-state="checked"]')).toHaveLength(3);
  });

  it('reports filters and the open file for the URL', async () => {
    const wrapper = mountManager({ initialState: { sort: 'size:desc' } });

    await wrapper.findComponent({ name: 'TypeFilterMenu' }).vm.$emit('update:modelValue', 'image');

    const states = wrapper.emitted('stateChange') ?? [];
    expect(states[states.length - 1]?.[0]).toMatchObject({ type: 'image', sort: 'size:desc', file: null });
  });
});
