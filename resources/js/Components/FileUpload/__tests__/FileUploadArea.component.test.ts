import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';

import { commonStubs } from '@/tests/stubs';

import FileUploadArea from '../FileUploadArea.vue';

const wrappers: ReturnType<typeof mount>[] = [];
const mockFetch = vi.fn();

beforeEach(() => {
  mockFetch.mockReset();
  vi.stubGlobal('fetch', mockFetch);
});

function lastEmitted(wrapper: ReturnType<typeof mount>, event: string) {
  const emitted = wrapper.emitted(event) ?? [];
  return emitted[emitted.length - 1]?.[0];
}

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

async function mountWithAllowedTypes(payload: unknown) {
  mockFetch.mockResolvedValueOnce({ ok: true, status: 200, json: () => Promise.resolve(payload) });
  const wrapper = mountArea();
  await flushPromises();
  return wrapper;
}

const ALLOWED = { extensions: ['jpg', 'pdf'], accept: '.jpg,.pdf', maxSizeMB: 50 };

describe('FileUploadArea allowed types', () => {
  it('reads the {success, data} envelope every API controller returns', async () => {
    // Regression: the component used to assign the envelope itself, so `.extensions` was
    // undefined and `.join()` threw out of the render, freezing the panel.
    const wrapper = await mountWithAllowedTypes({ success: true, data: ALLOWED });

    expect(wrapper.text()).toContain('JPG, PDF');
    expect(wrapper.find('input[type="file"]').attributes('accept')).toBe('.jpg,.pdf');
  });

  it('still accepts a bare, unwrapped payload', async () => {
    const wrapper = await mountWithAllowedTypes(ALLOWED);

    expect(wrapper.text()).toContain('JPG, PDF');
  });

  it('falls back rather than throwing when the payload has no extensions', async () => {
    const wrapper = await mountWithAllowedTypes({ success: true, data: { accept: '*' } });

    expect(wrapper.text()).toContain('Visi formatai');
  });

  it('skips the request entirely when the caller forces its own extensions', () => {
    const wrapper = mountArea({ forceAccept: true, extensions: ['png'], accept: '.png' });

    expect(mockFetch).not.toHaveBeenCalled();
    expect(wrapper.text()).toContain('PNG');
  });
});

describe('FileUploadArea selection', () => {
  it('emits the selected files for the parent to upload', async () => {
    const wrapper = mountArea({ forceAccept: true, extensions: ['pdf'], accept: '.pdf' });

    const file = new File(['x'], 'report.pdf', { type: 'application/pdf' });
    Object.defineProperty(wrapper.find('input[type="file"]').element, 'files', { value: [file] });
    await wrapper.find('input[type="file"]').trigger('change');

    expect(lastEmitted(wrapper, 'files-selected')).toEqual([file]);

    const uploadButton = wrapper.findAll('button').find(b => b.text().includes('Įkelti'));
    await uploadButton!.trigger('click');

    expect(wrapper.emitted('upload')?.[0]?.[0]).toEqual([file]);
  });

  it('rejects a file whose extension is not allowed', async () => {
    const wrapper = mountArea({ forceAccept: true, extensions: ['pdf'], accept: '.pdf' });

    const file = new File(['x'], 'payload.exe', { type: 'application/octet-stream' });
    Object.defineProperty(wrapper.find('input[type="file"]').element, 'files', { value: [file] });
    await wrapper.find('input[type="file"]').trigger('change');

    expect(lastEmitted(wrapper, 'files-selected')).toEqual([]);
  });
});
