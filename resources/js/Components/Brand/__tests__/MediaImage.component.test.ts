import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import MediaFrame from '../MediaFrame.vue';
import MediaImage from '../MediaImage.vue';

import type { ImageData } from '@/Types/media';

const image: ImageData = {
  id: 1,
  url: '/uploads/media/1/photo.webp',
  thumb: '/uploads/media/1/conversions/photo-thumb.webp',
  srcset: '/uploads/media/1/conversions/photo-thumb.webp 400w, /uploads/media/1/photo.webp 1200w',
  width: 1200,
  height: 800,
  focal_point: '40% 20%',
  alt: 'Studentai',
  author: null,
};

describe('MediaImage', () => {
  it('renders srcset, intrinsic size and the image\'s focal point', () => {
    const img = mount(MediaImage, { props: { image, sizes: '50vw' } }).find('img');

    expect(img.attributes()).toMatchObject({ src: image.url, srcset: image.srcset, sizes: '50vw', width: '1200', height: '800', alt: 'Studentai', loading: 'lazy' });
    expect(img.attributes('style')).toContain('object-position: 40% 20%');
  });

  it('falls back to a plain url without srcset', () => {
    const img = mount(MediaImage, { props: { src: '/uploads/news/old.jpg' } }).find('img');

    expect(img.attributes('src')).toBe('/uploads/news/old.jpg');
    expect(img.attributes('srcset')).toBeUndefined();
  });

  it('renders nothing without an image', () => {
    expect(mount(MediaImage).find('img').exists()).toBe(false);
  });

  it('inside MediaFrame, an explicit focal point wins over the image\'s own', () => {
    const img = mount(MediaFrame, { props: { image, focalPoint: '50% 50%' } }).find('img');

    expect(img.attributes('style')).toContain('object-position: 50% 50%');
  });
});
