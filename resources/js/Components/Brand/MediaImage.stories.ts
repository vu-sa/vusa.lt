import type { Meta, StoryObj } from '@storybook/vue3-vite';

import MediaFrame from './MediaFrame.vue';
import MediaImage from './MediaImage.vue';

import type { ImageData } from '@/Types/media';

const image: ImageData = {
  id: 1,
  url: '/images/placeholders/foto1.jpg',
  thumb: '/images/placeholders/foto1.jpg',
  srcset: '/images/placeholders/foto2.jpg 400w, /images/placeholders/foto1.jpg 1600w',
  width: 1600,
  height: 1000,
  focal_point: '50% 30%',
  alt: 'Studentai renginyje',
  author: null,
};

/**
 * A media library image: srcset from the shared conversions, the focal point as object-position.
 * Inside MediaFrame it fills the frame's ratio.
 */
const meta: Meta<typeof MediaImage> = {
  title: 'Brand/MediaImage',
  component: MediaImage,
  tags: ['autodocs'],
  parameters: { a11y: { test: 'error' } },
  args: { image, sizes: '(min-width: 768px) 50vw, 100vw' },
};

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const InFrame: Story = {
  render: args => ({
    components: { MediaFrame },
    setup: () => ({ args }),
    template: '<div class="max-w-md"><MediaFrame :image="args.image" :sizes="args.sizes" ratio="4/3" :grayscale="false" /></div>',
  }),
};

export const FocalPointAtTop: Story = {
  render: args => ({
    components: { MediaFrame },
    setup: () => ({ args: { ...args, image: { ...image, focal_point: '50% 0%' } } }),
    template: '<div class="max-w-md"><MediaFrame :image="args.image" ratio="16/9" :grayscale="false" /></div>',
  }),
};
