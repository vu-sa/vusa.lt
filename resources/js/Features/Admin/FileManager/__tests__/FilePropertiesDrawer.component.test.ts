import { afterEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';

import FilePropertiesDrawer from '../Components/FilePropertiesDrawer.vue';

vi.mock('vue-sonner', () => ({ toast: { error: vi.fn(), success: vi.fn() } }));

const passthrough = { template: '<div><slot /></div>' };
const ConfirmDialogStub = {
  name: 'ConfirmDialog',
  props: ['open', 'title', 'description', 'confirmLabel'],
  emits: ['confirm', 'update:open'],
  template: '<div v-if="open" data-testid="compression-dialog"><button type="button" data-testid="confirm-compression" @click="$emit(\'confirm\')">Confirm</button><button type="button" data-testid="cancel-compression" @click="$emit(\'update:open\', false)">Cancel</button></div>',
};

let wrapper: ReturnType<typeof mount> | undefined;

afterEach(() => {
  wrapper?.unmount();
  vi.mocked(router.post).mockClear();
});

function mountDrawer() {
  wrapper = mount(FilePropertiesDrawer, {
    props: {
      selectedFile: 'public/files/large.png',
      files: [{ path: 'public/files/large.png', name: 'large.png', type: 'file', size: 600_000, modified: 1_700_000_000 }],
    },
    global: {
      stubs: { Sheet: passthrough, SheetContent: passthrough, ConfirmDialog: ConfirmDialogStub },
    },
  });
  return wrapper;
}

describe('FilePropertiesDrawer image optimization', () => {
  it('keeps the image unchanged on cancel and submits the captured path on confirm', async () => {
    const drawer = mountDrawer();
    const optimize = drawer.findAll('button').find(button => button.text().includes('Optimizuoti'));
    expect(optimize).toBeDefined();

    await optimize!.trigger('click');
    expect(drawer.find('[data-testid="compression-dialog"]').exists()).toBe(true);
    expect(router.post).not.toHaveBeenCalled();

    await drawer.find('[data-testid="cancel-compression"]').trigger('click');
    expect(drawer.find('[data-testid="compression-dialog"]').exists()).toBe(false);
    expect(router.post).not.toHaveBeenCalled();

    await optimize!.trigger('click');
    await drawer.find('[data-testid="confirm-compression"]').trigger('click');

    expect(router.post).toHaveBeenCalledOnce();
    expect(router.post).toHaveBeenCalledWith(
      '/mocked-route/files.compress',
      { path: 'public/files/large.png' },
      expect.any(Object),
    );
  });
});

describe('FilePropertiesDrawer usage check', () => {
  function respondToScan(data: Record<string, unknown>) {
    vi.mocked(router.post).mockImplementationOnce((_url, _data, options) => {
      (options as { onSuccess: (page: unknown) => void }).onSuccess({ props: { flash: { data } } });
    });
  }

  async function runScan(drawer: ReturnType<typeof mount>) {
    const check = drawer.findAll('button').find(button => button.text().includes('Tikrinti'));
    await check!.trigger('click');
  }

  it('lists every record that uses the file, linking to it', async () => {
    respondToScan({
      is_safe_to_delete: false,
      total_usages: 2,
      scanned_models: ['contentParts', 'banners'],
      usage_details: [
        { model_type: 'news', model_class: 'App\\Models\\News', id: 7, title: 'Rudens šventė', url: '/mano/news/7/edit', matched_parts_count: 2 },
        { model_type: 'contentEditorDrafts', model_class: 'App\\Models\\ContentEditorDraft', id: 3, title: 'ContentEditorDraft #3', url: null },
      ],
    });
    const drawer = mountDrawer();

    await runScan(drawer);

    const rows = drawer.findAll('[data-testid="file-usage"] li');
    expect(rows).toHaveLength(2);
    expect(rows[0].text()).toContain('Rudens šventė');
    expect(rows[0].text()).toContain('files.usage.models.news');
    expect(rows[0].text()).toContain('files.usage.matched_blocks');
    expect(rows[0].find('a').attributes('href')).toBe('/mano/news/7/edit');
    expect(rows[1].text()).toContain('files.usage.models.contentEditorDrafts');
    expect(rows[1].text()).not.toContain('files.usage.matched_blocks');
    expect(rows[1].find('a').exists()).toBe(false);
  });

  it('reports a safe file without a usage list', async () => {
    respondToScan({ is_safe_to_delete: true, total_usages: 0, scanned_models: ['contentParts', 'news', 'banners'], usage_details: [] });
    const drawer = mountDrawer();

    await runScan(drawer);

    const usage = drawer.find('[data-testid="file-usage"]');
    expect(usage.text()).toContain('Saugu trinti');
    expect(usage.text()).toContain('files.messages.usage_safe');
    expect(usage.findAll('li')).toHaveLength(0);
  });
});
