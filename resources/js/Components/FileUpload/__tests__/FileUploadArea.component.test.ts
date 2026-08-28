import { afterEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { useHttp } from '@inertiajs/vue3';

import FileUploadArea from '../FileUploadArea.vue';
import { commonStubs } from '@/tests/stubs';

const wrappers: ReturnType<typeof mount>[] = [];

afterEach(() => {
  wrappers.splice(0).forEach(wrapper => wrapper.unmount());
  vi.clearAllMocks();
});

function mountArea(props: Record<string, unknown> = {}) {
  const wrapper = mount(FileUploadArea, {
    props,
    global: { stubs: { ...commonStubs } },
  });
  wrappers.push(wrapper);
  return wrapper;
}

/** The mock's `get` records the call; the component's callbacks are driven from here. */
function respondWith(payload: unknown) {
  const http = vi.mocked(useHttp).mock.results.at(-1)?.value;
  const options = vi.mocked(http.get).mock.calls.at(-1)?.[1];
  options.onSuccess(payload);
}

const ALLOWED = { extensions: ['jpg', 'pdf'], accept: '.jpg,.pdf', maxSizeMB: 50 };

describe('FileUploadArea allowed types', () => {
  it('reads the {success, data} envelope every API controller returns', async () => {
    const wrapper = mountArea();

    // Regression: the component used to assign the envelope itself, so `.extensions` was
    // undefined and `.join()` threw out of the render, freezing the panel.
    expect(() => respondWith({ success: true, data: ALLOWED })).not.toThrow();
    await wrapper.vm.$nextTick();

    expect(wrapper.text()).toContain('JPG, PDF');
    expect(wrapper.find('input[type="file"]').attributes('accept')).toBe('.jpg,.pdf');
  });

  it('still accepts a bare, unwrapped payload', async () => {
    const wrapper = mountArea();

    respondWith(ALLOWED);
    await wrapper.vm.$nextTick();

    expect(wrapper.text()).toContain('JPG, PDF');
  });

  it('falls back rather than throwing when the payload has no extensions', async () => {
    const wrapper = mountArea();

    expect(() => respondWith({ success: true, data: { accept: '*' } })).not.toThrow();
    await wrapper.vm.$nextTick();

    expect(wrapper.text()).toContain('Visi formatai');
  });

  it('skips the request entirely when the caller forces its own extensions', () => {
    const wrapper = mountArea({ forceAccept: true, extensions: ['png'], accept: '.png' });

    const http = vi.mocked(useHttp).mock.results.at(-1)?.value;
    expect(http.get).not.toHaveBeenCalled();
    expect(wrapper.text()).toContain('PNG');
  });
});

describe('FileUploadArea selection', () => {
  it('emits the selected files for the parent to upload', async () => {
    const wrapper = mountArea({ forceAccept: true, extensions: ['pdf'], accept: '.pdf' });

    const file = new File(['x'], 'report.pdf', { type: 'application/pdf' });
    Object.defineProperty(wrapper.find('input[type="file"]').element, 'files', { value: [file] });
    await wrapper.find('input[type="file"]').trigger('change');

    expect(wrapper.emitted('files-selected')?.at(-1)?.[0]).toEqual([file]);

    const uploadButton = wrapper.findAll('button').find(b => b.text().includes('Įkelti'));
    await uploadButton!.trigger('click');

    expect(wrapper.emitted('upload')?.[0]?.[0]).toEqual([file]);
  });

  it('rejects a file whose extension is not allowed', async () => {
    const wrapper = mountArea({ forceAccept: true, extensions: ['pdf'], accept: '.pdf' });

    const file = new File(['x'], 'payload.exe', { type: 'application/octet-stream' });
    Object.defineProperty(wrapper.find('input[type="file"]').element, 'files', { value: [file] });
    await wrapper.find('input[type="file"]').trigger('change');

    expect(wrapper.emitted('files-selected')?.at(-1)?.[0]).toEqual([]);
  });
});
