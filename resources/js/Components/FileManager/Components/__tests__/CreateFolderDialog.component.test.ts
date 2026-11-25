import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import CreateFolderDialog from '../CreateFolderDialog.vue';

const mountInline = () => mount(CreateFolderDialog, {
  props: { 'open': true, 'inline': true, 'path': 'public/files/vusa', 'folderLabel': 'vusa', 'onUpdate:open': () => {} },
});

describe('CreateFolderDialog', () => {
  beforeEach(() => vi.mocked(router.post).mockClear());

  it('creates the trimmed folder in the open path and reports it', async () => {
    vi.mocked(router.post).mockImplementation((_url, _data, options) => {
      (options as { onSuccess?: () => void; onFinish?: () => void })?.onSuccess?.();
      (options as { onFinish?: () => void })?.onFinish?.();
    });
    const wrapper = mountInline();

    await wrapper.get('input').setValue('  Ataskaitos ');
    await wrapper.get('form').trigger('submit');

    expect(router.post).toHaveBeenCalledWith(expect.stringContaining('files.createDirectory'), { path: 'public/files/vusa', name: 'Ataskaitos' }, expect.any(Object));
    expect(wrapper.emitted('created')).toHaveLength(1);
    expect(wrapper.emitted('update:open')?.at(-1)).toEqual([false]);
  });

  it('does not submit a blank name', async () => {
    const wrapper = mountInline();

    await wrapper.get('input').setValue('   ');
    await wrapper.get('form').trigger('submit');

    expect(router.post).not.toHaveBeenCalled();
  });
});
