import { afterEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';
import { useFetch } from '@vueuse/core';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

import FilePropertiesDrawer from '../Components/FilePropertiesDrawer.vue';

vi.mock('@vueuse/core', async (importOriginal) => ({
  ...await importOriginal<typeof import('@vueuse/core')>(),
  useFetch: vi.fn(),
}));
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
  vi.mocked(useFetch).mockReset();
  vi.mocked(toast.error).mockClear();
  vi.mocked(toast.success).mockClear();
});

describe('FilePropertiesDrawer public links', () => {
  function mountSharepointDrawer(response: Record<string, unknown>, error: string | null = null) {
    const post = vi.fn();
    const request = { post, json: vi.fn().mockResolvedValue({ data: ref(response), error: ref(error) }) };
    post.mockReturnValue(request);
    vi.mocked(useFetch).mockReturnValue(request as unknown as ReturnType<typeof useFetch>);
    wrapper = mount(FilePropertiesDrawer, {
      props: {
        selectedFile: 'drive-item-1',
        files: [],
        source: 'sharepoint',
        sharepointFile: { id: 'drive-item-1', name: 'Protokolas.pdf', size: 100 },
      },
      global: { stubs: { Sheet: passthrough, SheetContent: passthrough, ConfirmDialog: ConfirmDialogStub } },
    });
    return { drawer: wrapper, post };
  }

  it('offers opening and copying the link returned by SharePoint', async () => {
    const { drawer, post } = mountSharepointDrawer({ success: true, url: 'https://sharepoint.test/public' });

    await drawer.findAll('button').find(button => button.text().includes('Sukurti viešą nuorodą'))!.trigger('click');
    await flushPromises();

    expect(useFetch).toHaveBeenCalledWith(expect.stringContaining('/mocked-route/sharepoint.createPublicPermission'), expect.objectContaining({ headers: expect.any(Object) }));
    expect(post).toHaveBeenCalledOnce();
    expect(drawer.get('a[href="https://sharepoint.test/public"]').text()).toContain('Atidaryti');
    expect(drawer.findAll('button').some(button => button.text().includes('Kopijuoti'))).toBe(true);
    expect(toast.success).toHaveBeenCalledOnce();
  });

  it('keeps the creation action available and reports an unsuccessful request', async () => {
    const { drawer } = mountSharepointDrawer({ success: false, error: 'SharePoint nepasiekiamas' }, 'HTTP 503');

    await drawer.findAll('button').find(button => button.text().includes('Sukurti viešą nuorodą'))!.trigger('click');
    await flushPromises();

    expect(toast.error).toHaveBeenCalledWith('SharePoint nepasiekiamas');
    expect(drawer.find('a[href^="https://sharepoint.test/"]').exists()).toBe(false);
    expect(drawer.findAll('button').some(button => button.text().includes('Sukurti viešą nuorodą'))).toBe(true);
  });
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
