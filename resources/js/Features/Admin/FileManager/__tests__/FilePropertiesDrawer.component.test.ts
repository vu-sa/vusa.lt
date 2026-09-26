import { afterEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';

import FilePropertiesDrawer from '../Components/FilePropertiesDrawer.vue';

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
