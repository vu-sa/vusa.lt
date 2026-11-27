import { afterEach, describe, expect, it, vi } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';

import Upload from '@/Components/ui/upload/Upload.vue';
import { scopedGlobals } from '@/tests/helpers/scopedGlobals';

const { stubProperty, restoreGlobals } = scopedGlobals();

enableAutoUnmount(afterEach);
afterEach(restoreGlobals);

describe('Upload.vue', () => {
  it('replaces an existing single image after selecting a new file', async () => {
    stubProperty(URL, 'createObjectURL', vi.fn(() => 'blob:new-image'));
    stubProperty(URL, 'revokeObjectURL', vi.fn());

    const wrapper = mount(Upload, { props: { max: 1, deferred: true } });
    wrapper.vm.setFiles([{
      id: 'existing-1',
      name: 'existing.jpg',
      size: 0,
      type: 'image/jpeg',
      url: 'https://example.com/existing.jpg',
      status: 'success',
      progress: 100,
    }]);

    const replacement = new File(['replacement'], 'replacement.jpg', { type: 'image/jpeg' });
    const input = wrapper.find('input[type="file"]');
    Object.defineProperty(input.element, 'files', {
      configurable: true,
      value: [replacement],
    });
    await input.trigger('change');

    expect(wrapper.emitted('update:files')?.at(-1)).toEqual([
      [expect.objectContaining({ file: replacement, name: 'replacement.jpg' })],
    ]);
  });
});
