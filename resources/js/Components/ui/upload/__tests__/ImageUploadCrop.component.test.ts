import { mount } from '@vue/test-utils';
import { defineComponent, nextTick } from 'vue';
import { describe, expect, it, vi } from 'vitest';

import ImageUpload from '@/Components/ui/upload/ImageUpload.vue';
import { commonStubs } from '@/tests/stubs';

const compression = vi.hoisted(() => ({
  compressImage: vi.fn(async (file: File) => ({
    file,
    originalSize: file.size,
    compressedSize: file.size,
    compressionRatio: 0,
    wasCompressed: false,
  })),
}));

vi.mock('@/Composables/useImageCompression', () => ({
  useImageCompression: () => ({
    compressImage: compression.compressImage,
    formatFileSize: (size: number) => String(size),
  }),
}));

const CropperStub = defineComponent({
  name: 'ImageCropperStub',
  template: '<div data-testid="cropper-stub" />',
});

describe('ImageUpload crop flow', () => {
  it('uses higher quality before cropping and saves the crop without default recompression', async () => {
    vi.stubGlobal('URL', {
      createObjectURL: vi.fn(() => 'blob:original'),
      revokeObjectURL: vi.fn(),
    });

    const wrapper = mount(ImageUpload, {
      props: { cropper: true, mode: 'deferred' },
      global: {
        stubs: {
          ...commonStubs,
          ImageCropper: CropperStub,
        },
      },
    });
    const file = new File(['original'], 'photo.jpg', { type: 'image/jpeg' });
    const input = wrapper.find('input[type="file"]');
    Object.defineProperty(input.element, 'files', { configurable: true, value: [file] });
    await input.trigger('change');
    await vi.waitFor(() => expect(compression.compressImage).toHaveBeenCalledTimes(1));
    expect(compression.compressImage).toHaveBeenCalledWith(file, expect.objectContaining({
      maxSizeMB: 3,
      maxWidthOrHeight: 2048,
      quality: 0.9,
    }));

    await wrapper.findAll('button').find(button => button.text() === 'Apkirpti')!.trigger('click');
    await nextTick();
    wrapper.findComponent(CropperStub).vm.$emit('crop', {
      dataUrl: 'data:image/webp;base64,Y3JvcA==',
      blob: new Blob(['crop'], { type: 'image/webp' }),
    });
    await nextTick();

    expect(compression.compressImage).toHaveBeenCalledTimes(1);
    const croppedFile = wrapper.emitted('update:file')?.at(-1)?.[0] as File;
    expect(croppedFile.type).toBe('image/webp');
    expect(croppedFile.name).toBe('photo.webp');
    expect(croppedFile.size).toBe(4);
    wrapper.unmount();
    vi.unstubAllGlobals();
  });
});
