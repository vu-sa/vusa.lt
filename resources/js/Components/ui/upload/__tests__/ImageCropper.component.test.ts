import { mount } from '@vue/test-utils';
import { beforeAll, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

import ImageCropper from '../ImageCropper.vue';

vi.mock('cropperjs', () => ({}));

class TestCropperImage extends HTMLElement {
  scale = 0.5;

  async $ready() {
    return document.createElement('img');
  }

  $getTransform() {
    return [this.scale, 0, 0, this.scale, 0, 0];
  }

  $resetTransform() {
    this.scale = 1;
  }

  $center() {
    this.scale = 0.5;
  }

  $zoom(amount: number) {
    this.scale *= amount < 0 ? 1 / (1 - amount) : 1 + amount;
  }

  $rotate() {}
}

const toCanvas = vi.fn(async () => document.createElement('canvas'));
const resetSelection = vi.fn();

class TestCropperSelection extends HTMLElement {
  aspectRatio = NaN;
  width = 200;
  height = 100;
  $toCanvas = toCanvas;
  $reset = resetSelection;
}

beforeAll(() => {
  vi.stubGlobal('requestAnimationFrame', (callback: FrameRequestCallback) => setTimeout(callback, 0));
  if (!customElements.get('cropper-canvas')) customElements.define('cropper-canvas', class extends HTMLElement {});
  if (!customElements.get('cropper-image')) customElements.define('cropper-image', TestCropperImage);
  if (!customElements.get('cropper-selection')) customElements.define('cropper-selection', TestCropperSelection);
});

function mountCropper(aspectRatio = 0) {
  return mount(ImageCropper, {
    props: { src: 'data:image/png;base64,AA==', aspectRatio },
    global: {
      stubs: {
        DialogHeader: { template: '<div><slot /></div>' },
        DialogTitle: { template: '<h2><slot /></h2>' },
        DialogDescription: { template: '<p><slot /></p>' },
        DialogFooter: { template: '<div><slot /></div>' },
        Slider: { template: '<div />' },
      },
    },
  });
}

describe('ImageCropper', () => {
  it('applies ratio choices, frees the selection, and resets to the supplied ratio', async () => {
    const wrapper = mountCropper(4 / 3);
    await nextTick();
    await Promise.resolve();

    const selection = wrapper.find('cropper-selection').element as TestCropperSelection;
    expect(selection.aspectRatio).toBeCloseTo(4 / 3);
    expect(wrapper.find('button[aria-pressed="true"]').text()).toBe('4:3');

    await wrapper.findAll('button').find(button => button.text() === '16:9')!.trigger('click');
    expect(selection.aspectRatio).toBeCloseTo(16 / 9);

    await wrapper.findAll('button').find(button => button.text() === 'Laisvai')!.trigger('click');
    expect(selection.aspectRatio).toBe(0);

    await wrapper.findAll('button').find(button => button.text() === 'Atstatyti')!.trigger('click');
    expect(selection.aspectRatio).toBeCloseTo(4 / 3);
    expect(resetSelection).toHaveBeenCalled();
  });

  it('caps the crop by source pixels and emits the encoded blob', async () => {
    const blob = new Blob(['image'], { type: 'image/webp' });
    vi.spyOn(HTMLCanvasElement.prototype, 'toBlob').mockImplementation((callback, type, quality) => {
      expect(type).toBe('image/webp');
      expect(quality).toBe(0.9);
      callback(blob);
    });

    const wrapper = mount(ImageCropper, {
      props: { src: 'data:image/png;base64,AA==', maxOutputWidth: 300, maxOutputHeight: 300 },
      global: {
        stubs: {
          DialogHeader: { template: '<div><slot /></div>' },
          DialogTitle: { template: '<h2><slot /></h2>' },
          DialogDescription: { template: '<p><slot /></p>' },
          DialogFooter: { template: '<div><slot /></div>' },
          Slider: { template: '<div />' },
        },
      },
    });
    await nextTick();
    const saveButton = wrapper.findAll('button').find(button => button.text() === 'Išsaugoti kadrą')!;
    await vi.waitFor(() => expect(saveButton.attributes('disabled')).toBeUndefined());
    await saveButton.trigger('click');
    await vi.waitFor(() => expect(wrapper.emitted('crop')).toHaveLength(1));

    expect(toCanvas).toHaveBeenCalledWith({ width: 300 });
    expect(wrapper.emitted('crop')?.[0]?.[0]).toEqual(expect.objectContaining({ blob }));
    vi.restoreAllMocks();
  });
});
