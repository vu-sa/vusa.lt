import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it, vi, afterEach } from 'vitest';

import ImageUpload from '@/Components/ui/upload/ImageUpload.vue';
import type { ImageData } from '@/Types/media';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('vue-sonner', () => ({ toast: { error: vi.fn(), success: vi.fn() } }));
vi.mock('@/Composables/useImageCompression', () => ({
  useImageCompression: () => ({
    compressImage: vi.fn(async (file: File) => ({ file, originalSize: file.size, compressedSize: file.size, compressionRatio: 0, wasCompressed: false })),
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

afterEach(() => {
  vi.unstubAllGlobals();
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
