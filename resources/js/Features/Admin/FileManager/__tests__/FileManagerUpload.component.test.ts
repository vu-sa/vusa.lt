import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';

import FileManager from '../FileManager.vue';
import { commonStubs } from '@/tests/stubs';

const wrappers: ReturnType<typeof mount>[] = [];
const mockFetch = vi.fn();

beforeEach(() => {
  localStorage.clear();
  mockFetch.mockReset();
  vi.stubGlobal('fetch', mockFetch);
});

afterEach(() => {
  wrappers.splice(0).forEach(wrapper => wrapper.unmount());
});

function mountManager(props: Record<string, unknown> = {}) {
  const wrapper = mount(FileManager, {
    props: {
      files: [{ path: 'public/files/a.pdf', name: 'a.pdf', size: 10, modified: 1 }],
      directories: [],
      path: 'public/files',
      ...props,
    },
    global: { stubs: { ...commonStubs } },
  });
  wrappers.push(wrapper);
  return wrapper;
}

function mountSelector(props: Record<string, unknown> = {}) {
  return mountManager({
    selectionMode: true,
    allowUploadInSelection: true,
    uploadExtensions: ['jpg', 'png', 'webp'],
    ...props,
  });
}

function uploadedResponse(uploaded: Array<{ name: string; path: string }>) {
  return jsonResponse(true, {
    success: true,
    data: {
      uploaded: uploaded.map(file => ({ ...file, url: `/uploads/${file.name}`, renamed: false })),
      failed: [],
      path: 'public/files',
    },
    message: 'ok',
  });
}

function jsonResponse(ok: boolean, body: unknown, status = ok ? 200 : 422) {
  return { ok, status, json: () => Promise.resolve(body) };
}

/** Switch to the upload panel and hand FileUploadArea a file to emit upward. */
async function startUpload(wrapper: ReturnType<typeof mount>) {
  const uploadToggle = wrapper.findAll('button').find(b => b.text().includes('files.ui.upload'));
  await uploadToggle!.trigger('click');

  const area = wrapper.findComponent({ name: 'FileUploadArea' });
  area.vm.$emit('upload', [new File(['x'], 'report.pdf', { type: 'application/pdf' })]);
  await flushPromises();

  return area;
}

function uploadButtonIsSpinning(wrapper: ReturnType<typeof mount>) {
  return wrapper.findComponent({ name: 'FileUploadArea' }).props('loading') === true;
}

describe('FileManager upload', () => {
  it('sends the whole batch as one request', async () => {
    mockFetch.mockResolvedValue(jsonResponse(true, {
      success: true,
      data: { uploaded: [{ name: 'report.pdf', path: 'p', url: '/u', renamed: false }], failed: [], path: 'public/files' },
      message: 'ok',
    }));

    const wrapper = mountManager();
    await startUpload(wrapper);

    // One request, not one visit per file group — the two-visit split is what let Inertia
    // cancel the first upload and strand the spinner.
    expect(mockFetch).toHaveBeenCalledTimes(1);
    const updates = wrapper.emitted('update') ?? [];
    expect(updates[updates.length - 1]?.[0]).toBe('public/files');
  });

  it('clears the spinner when the server rejects the upload', async () => {
    mockFetch.mockResolvedValue(jsonResponse(false, { success: false, message: 'Nope' }));

    const wrapper = mountManager();
    await startUpload(wrapper);

    expect(uploadButtonIsSpinning(wrapper)).toBe(false);
  });

  it('clears the spinner when the request never completes cleanly', async () => {
    // The reported bug: the upload reached the server, but nothing ever settled the caller,
    // so the button span forever over a file that had in fact been stored.
    mockFetch.mockRejectedValue(new Error('Network error'));

    const wrapper = mountManager();
    await startUpload(wrapper);

    expect(uploadButtonIsSpinning(wrapper)).toBe(false);
    expect(wrapper.findComponent({ name: 'FileUploadArea' }).exists()).toBe(true);
  });

  it('clears the spinner when the response is not JSON at all', async () => {
    mockFetch.mockResolvedValue({ ok: false, status: 500, json: () => Promise.reject(new Error('not json')) });

    const wrapper = mountManager();
    await startUpload(wrapper);

    expect(uploadButtonIsSpinning(wrapper)).toBe(false);
  });

  it('selects the uploaded file so the picker does not make the author find it again', async () => {
    mockFetch.mockResolvedValue(uploadedResponse([{ name: 'plakatas.webp', path: 'public/files/plakatas.webp' }]));

    const wrapper = mountSelector();
    await startUpload(wrapper);

    expect(wrapper.emitted('fileSelected')).toEqual([['public/files/plakatas.webp', 'upload']]);
  });

  it('does not auto-select an upload the picker would refuse on click', async () => {
    // The server can hand back an extension outside the allowlist (a rejected optimisation,
    // an .svg saved as-is); auto-selecting it would smuggle past the click-time guard.
    mockFetch.mockResolvedValue(uploadedResponse([{ name: 'sutartis.pdf', path: 'public/files/sutartis.pdf' }]));

    const wrapper = mountSelector();
    await startUpload(wrapper);

    expect(wrapper.emitted('fileSelected')).toBeUndefined();
  });

  it('leaves the selection alone outside selection mode', async () => {
    mockFetch.mockResolvedValue(uploadedResponse([{ name: 'plakatas.webp', path: 'public/files/plakatas.webp' }]));

    const wrapper = mountManager();
    await startUpload(wrapper);

    expect(wrapper.emitted('fileSelected')).toBeUndefined();
  });

  it('keeps the panel open when every file in the batch failed', async () => {
    mockFetch.mockResolvedValue(jsonResponse(true, {
      success: true,
      data: { uploaded: [], failed: [{ name: 'report.pdf', reason: 'unreadable' }], path: 'public/files' },
    }));

    const wrapper = mountManager();
    await startUpload(wrapper);

    expect(uploadButtonIsSpinning(wrapper)).toBe(false);
    expect(wrapper.emitted('update')).toBeUndefined();
  });
});
