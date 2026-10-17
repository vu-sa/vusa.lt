import { flushPromises, mount } from '@vue/test-utils';
import { defineComponent } from 'vue';
import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';

import ImageUpload from '@/Components/ui/upload/ImageUpload.vue';
import type { ImageData } from '@/Types/media';
import { commonStubs } from '@/tests/stubs';
import { route } from '@/mocks/route';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('vue-sonner', () => ({ toast: { error: vi.fn(), success: vi.fn() } }));
const compression = vi.hoisted(() => ({
  compressImage: vi.fn(async (file: File) => ({ file, originalSize: file.size, compressedSize: file.size, compressionRatio: 0, wasCompressed: false })),
}));
vi.mock('@/Composables/useImageCompression', () => ({
  useImageCompression: () => ({
    compressImage: compression.compressImage,
    formatFileSize: (size: number) => String(size),
  }),
}));

const staged: ImageData = {
  id: 42,
  url: '/uploads/media/42/photo.webp',
  thumb: '/uploads/media/42/photo.webp',
  srcset: null,
  width: 2400,
  height: 1600,
  focal_point: null,
  alt: null,
  author: null,
};

async function chooseFile(wrapper: ReturnType<typeof mount>) {
  const input = wrapper.find('input[type="file"]');
  Object.defineProperty(input.element, 'files', { configurable: true, value: [new File(['x'], 'photo.jpg', { type: 'image/jpeg' })] });
  await input.trigger('change');
}

beforeEach(() => {
  vi.stubGlobal('route', route);
});

afterEach(() => {
  vi.unstubAllGlobals();
});

describe('upload feedback', () => {
  beforeEach(() => {
    vi.stubGlobal('URL', { createObjectURL: vi.fn(() => 'blob:photo'), revokeObjectURL: vi.fn() });
  });

  it('distinguishes compression from uploading until the request starts', async () => {
    const optimizing = Promise.withResolvers<Awaited<ReturnType<typeof compression.compressImage>>>();
    const uploading = Promise.withResolvers<Response>();
    compression.compressImage.mockImplementationOnce(() => optimizing.promise);
    const fetchMock = vi.fn(() => uploading.promise);
    vi.stubGlobal('fetch', fetchMock);
    const wrapper = mount(ImageUpload, { props: { mode: 'media', compress: true }, global: { stubs: commonStubs } });
    await chooseFile(wrapper);

    expect(wrapper.get('[role="status"]').text()).toContain('forms.image_upload.optimizing');
    expect(fetchMock).not.toHaveBeenCalled();
    const file = new File(['optimized'], 'photo.webp', { type: 'image/webp' });
    optimizing.resolve({ file, originalSize: 1, compressedSize: 1, compressionRatio: 0, wasCompressed: true });
    await flushPromises();
    expect(wrapper.get('[role="status"]').text()).toContain('forms.image_upload.uploading');
    uploading.resolve(new Response(JSON.stringify({ success: true, data: staged }), { status: 201 }));
    await flushPromises();
    wrapper.unmount();
  });

  it.each(['media', 'immediate'] as const)('shows the preview and busy status immediately in %s mode', async (mode) => {
    const pending = Promise.withResolvers<Response>();
    const fetchMock = vi.fn(() => pending.promise);
    vi.stubGlobal('fetch', fetchMock);
    const wrapper = mount(ImageUpload, { props: { mode, compress: false, cropper: true, focalPoint: true }, global: { stubs: commonStubs } });

    await chooseFile(wrapper);

    expect(wrapper.get('img').attributes('src')).toBe('blob:photo');
    expect(wrapper.get('[data-slot="image-upload-status"]').text()).toContain('forms.image_upload.uploading');
    expect(wrapper.get('[data-slot="upload"]').attributes('aria-busy')).toBe('true');
    expect(wrapper.find('[data-slot="image-upload-success"]').exists()).toBe(false);
    expect(wrapper.findAll('button').every(button => button.attributes('disabled') !== undefined)).toBe(true);
    await chooseFile(wrapper);
    expect(fetchMock).toHaveBeenCalledTimes(1);

    pending.resolve(new Response(JSON.stringify({ success: true, data: staged, url: staged.url }), { status: 201 }));
    await flushPromises();

    expect(wrapper.find('[role="status"]').exists()).toBe(false);
    expect(wrapper.get('[data-slot="image-upload-success"]').exists()).toBe(true);
    expect(wrapper.emitted(mode === 'media' ? 'update:image' : 'update:url')?.at(-1)?.[0]).toEqual(mode === 'media' ? staged : staged.url);
    wrapper.unmount();
  });

  it.each(['server', 'network'])('keeps the accepted image after a %s failure and supports retry', async (failure) => {
    const pending = Promise.withResolvers<Response>();
    const fetchMock = vi.fn().mockImplementationOnce(() => pending.promise)
      .mockResolvedValueOnce(new Response(JSON.stringify({ success: true, data: staged }), { status: 201 }));
    vi.stubGlobal('fetch', fetchMock);
    const previous = { ...staged, id: 7, url: '/previous.webp' };
    const wrapper = mount(ImageUpload, { props: { mode: 'media', image: previous }, global: { stubs: commonStubs } });

    await chooseFile(wrapper);
    if (failure === 'server') pending.resolve(new Response(JSON.stringify({ message: 'Too large' }), { status: 422 }));
    else pending.reject(new TypeError('Failed to fetch'));
    await flushPromises();

    expect(wrapper.get('[role="alert"]').text()).toContain(failure === 'server' ? 'Too large' : 'forms.image_upload.failed');
    expect(wrapper.emitted('update:image')).toBeUndefined();
    expect(wrapper.find('[data-slot="image-upload-success"]').exists()).toBe(false);
    await wrapper.findAll('button').find(button => button.text() === 'forms.image_upload.retry')!.trigger('click');
    await flushPromises();

    expect(wrapper.find('[role="alert"]').exists()).toBe(false);
    expect(wrapper.emitted('update:image')?.at(-1)?.[0]).toEqual(staged);
    wrapper.unmount();
  });

  it('aborts on unmount and ignores a late response', async () => {
    const pending = Promise.withResolvers<Response>();
    const fetchMock = vi.fn(() => pending.promise);
    vi.stubGlobal('fetch', fetchMock);
    const wrapper = mount(ImageUpload, { props: { mode: 'media' }, global: { stubs: commonStubs } });
    await chooseFile(wrapper);
    const options = (fetchMock.mock.calls[0] as unknown as [string, RequestInit])[1];

    wrapper.unmount();
    expect(options.signal?.aborted).toBe(true);
    pending.resolve(new Response(JSON.stringify({ success: true, data: staged }), { status: 201 }));
    await flushPromises();
    expect(wrapper.emitted('update:image')).toBeUndefined();
  });

  it('shows the busy state again while a cropped image is uploaded', async () => {
    const Cropper = defineComponent({ emits: ['crop'], template: '<div />' });
    const pending = Promise.withResolvers<Response>();
    vi.stubGlobal('fetch', vi.fn().mockResolvedValueOnce(new Response(JSON.stringify({ success: true, data: staged }), { status: 201 }))
      .mockImplementationOnce(() => pending.promise));
    const wrapper = mount(ImageUpload, { props: { mode: 'media', cropper: true }, global: { stubs: { ...commonStubs, ImageCropper: Cropper } } });
    await chooseFile(wrapper);
    await flushPromises();
    await wrapper.findAll('button').find(button => button.text() === 'Apkirpti')!.trigger('click');
    await flushPromises();
    wrapper.findComponent(Cropper).vm.$emit('crop', { dataUrl: 'data:image/webp;base64,Y3JvcA==', blob: new Blob(['crop'], { type: 'image/webp' }) });
    await flushPromises();

    expect(wrapper.get('[role="status"]').text()).toContain('forms.image_upload.uploading');
    expect(wrapper.find('[data-slot="image-upload-success"]').exists()).toBe(false);
    pending.resolve(new Response(JSON.stringify({ success: true, data: { ...staged, id: 43 } }), { status: 201 }));
    await flushPromises();
    expect(wrapper.emitted('update:image')?.at(-1)?.[0]).toMatchObject({ id: 43 });
    wrapper.unmount();
  });

  it('keeps deferred files local without an upload request', async () => {
    const fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);
    const wrapper = mount(ImageUpload, { props: { mode: 'deferred', compress: false }, global: { stubs: commonStubs } });
    await chooseFile(wrapper);
    await flushPromises();

    expect(fetchMock).not.toHaveBeenCalled();
    expect(wrapper.emitted('update:file')?.at(-1)?.[0]).toBeInstanceOf(File);
    expect(wrapper.find('[role="status"]').exists()).toBe(false);
    wrapper.unmount();
  });
});

describe('ImageUpload media mode', () => {
  it('stages the chosen file on the server and emits what it returned', async () => {
    const fetchMock = vi.fn(async () => new Response(JSON.stringify({ success: true, data: staged }), { status: 201 }));
    vi.stubGlobal('fetch', fetchMock);
    vi.stubGlobal('URL', { createObjectURL: vi.fn(() => 'blob:photo'), revokeObjectURL: vi.fn() });

    const wrapper = mount(ImageUpload, { props: { mode: 'media', image: null }, global: { stubs: commonStubs } });
    await chooseFile(wrapper);

    await vi.waitFor(() => expect(wrapper.emitted('update:image')?.at(-1)?.[0]).toEqual(staged));
    expect(fetchMock).toHaveBeenCalledWith(expect.stringContaining('api.v1.admin.pendingUploads.store'), expect.anything());
    const body = (fetchMock.mock.calls[0] as unknown as [string, RequestInit])[1].body as FormData;
    expect(body.has('path')).toBe(false);
  });

  it('emits null when the shown image is removed', async () => {
    const wrapper = mount(ImageUpload, { props: { mode: 'media', image: staged }, global: { stubs: commonStubs } });
    await flushPromises();
    expect(wrapper.find('img').attributes('src')).toBe(staged.url);

    await wrapper.findAll('button').find(button => button.classes('bg-destructive'))!.trigger('click');

    expect(wrapper.emitted('update:image')?.at(-1)?.[0]).toBeNull();
  });
});
