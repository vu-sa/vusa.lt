import { describe, expect, it } from 'vitest';
import { mount, type VueWrapper } from '@vue/test-utils';

import CardStackEditor from '../Types/CardStackEditor.vue';
import CarouselSlideDeckEditor from '../Types/CarouselSlideDeckEditor.vue';
import PhotoGalleryGridEditor from '../Types/PhotoGalleryGridEditor.vue';
import CardStackDisplay from '../RCCardStack/CardStackDisplay.vue';
import CarouselSlideDeckDisplay from '../RCCarouselSlideDeck/CarouselSlideDeckDisplay.vue';
import PhotoGalleryGridDisplay from '../RCPhotoGalleryGrid/PhotoGalleryGridDisplay.vue';
import { createContentItem, getContentType } from '../Types';

/**
 * Regression coverage for the three content types that shipped broken:
 * their editors bound v-model to refs that were never declared, and
 * CardStackDisplay read a nested `.cards` property that doesn't exist
 * on the array-shaped json_content.
 */

/** $t is mocked to return the key verbatim (see tests/setup.ts), so buttons render their i18n key as text. */
function findButtonByText(wrapper: VueWrapper, key: string) {
  const match = wrapper.findAll('button').find(b => b.text().includes(key));
  if (!match) throw new Error(`No button found containing text "${key}"`);
  return match;
}

describe('card-stack', () => {
  it('editor adds and removes a card via v-model', async () => {
    const item = createContentItem('card-stack');
    const wrapper = mount(CardStackEditor, {
      props: { modelValue: item.json_content, options: item.options },
    });

    await findButtonByText(wrapper, 'add_first_card').trigger('click');
    expect((wrapper.emitted('update:modelValue')?.at(-1)?.[0] as unknown[]).length).toBe(1);
  });

  it('display renders an empty stack without throwing', () => {
    const wrapper = mount(CardStackDisplay, {
      props: { element: { type: 'card-stack', json_content: [], options: getContentType('card-stack').defaultOptions!() } },
    });
    expect(wrapper.exists()).toBe(true);
  });

  it('display renders populated cards and cycles the active index', async () => {
    const element = {
      type: 'card-stack',
      json_content: [
        { icon: 'users', title: 'A', description: 'a' },
        { icon: 'star', title: 'B', description: 'b' },
      ],
      options: { autoplay: false, autoplayDelay: 5000, hintText: '' },
    };
    const wrapper = mount(CardStackDisplay, { props: { element } });
    expect(wrapper.findAll('h3')).toHaveLength(2);
    await wrapper.find('button').trigger('click'); // an indicator dot
    expect(wrapper.exists()).toBe(true);
  });

  it('renders an icon badge above the title when a card has one, and centers the text when it does not', () => {
    const element = {
      type: 'card-stack',
      json_content: [
        { icon: 'book-open', title: 'With icon', description: 'a' },
        { title: 'Without icon', description: 'b' },
      ],
      options: { autoplay: false, autoplayDelay: 5000, hintText: '' },
    };
    const wrapper = mount(CardStackDisplay, { props: { element } });
    const cards = wrapper.findAll('.absolute.inset-0');

    // Card with an icon: a badge renders, and the text group isn't given the
    // vertical-centering classes (it flows naturally below the badge).
    expect(cards[0]!.find('svg').exists()).toBe(true);
    expect(cards[0]!.find('h3').element.parentElement?.className).not.toContain('justify-center');

    // Card with no icon: no badge, and the text group centers vertically instead of
    // sitting bunched at the top of the card.
    expect(cards[1]!.find('svg').exists()).toBe(false);
    expect(cards[1]!.find('h3').element.parentElement?.className).toContain('justify-center');
  });
});

describe('carousel-slide-deck', () => {
  it('editor adds a slide via v-model', async () => {
    const item = createContentItem('carousel-slide-deck');
    const wrapper = mount(CarouselSlideDeckEditor, {
      props: { modelValue: item.json_content, options: item.options },
      global: { stubs: { TiptapEditor: true } },
    });

    await findButtonByText(wrapper, 'add_first_slide').trigger('click');
    expect((wrapper.emitted('update:modelValue')?.at(-1)?.[0] as unknown[]).length).toBe(1);
  });

  it('editor renders pre-existing slides that omit the optional decorations field (seeded data)', () => {
    // RichContentDemoPagesSeeder stores carousel slides without a `decorations` key
    // (the field is optional on the type). The decorations DynamicListInput must
    // tolerate that undefined value rather than throwing on `items.length`.
    const wrapper = mount(CarouselSlideDeckEditor, {
      props: {
        modelValue: [
          {
            icon: 'users',
            badge: 'Badge',
            title: 'Title',
            description: '',
            imageSrc: '/images/x.webp',
            imageAlt: '',
            imageLeft: true,
            // deliberately no `decorations`
          },
        ],
        options: { autoplay: false, autoplayDelay: 8000, showNavigation: true, showThumbnails: true },
      },
      global: { stubs: { TiptapEditor: true } },
    });

    // Mounting itself is the regression — previously the decorations
    // DynamicListInput threw on `items.length` when `decorations` was absent.
    // The empty-state copy confirms that sub-list rendered safely.
    expect(wrapper.text()).toContain('no_decorations');
  });

  it('display renders a slide description from Tiptap JSON without throwing', () => {
    const element = {
      type: 'carousel-slide-deck',
      json_content: [
        {
          icon: 'info',
          badge: 'Badge',
          title: 'Title',
          description: { type: 'doc', content: [{ type: 'paragraph', content: [{ type: 'text', text: 'Hi' }] }] },
          imageSrc: '/images/x.webp',
          imageAlt: '',
          imageLeft: false,
          decorations: [],
        },
      ],
      options: { autoplay: false, autoplayDelay: 8000, showNavigation: true, showThumbnails: true },
    };
    const wrapper = mount(CarouselSlideDeckDisplay, {
      props: { element },
      global: { stubs: { Carousel: true, CarouselContent: true, CarouselItem: true, CarouselNext: true, CarouselPrevious: true } },
    });
    expect(wrapper.exists()).toBe(true);
  });
});

describe('photo-gallery', () => {
  it('editor adds an image via v-model', async () => {
    // photo-gallery now shares RCImageTileGrid with image-grid: adding a tile goes
    // through the TiptapImageButton picker (stubbed here to emit immediately) instead
    // of DynamicListInput's old "append a blank row" button.
    const stubs = {
      TiptapImageButton: {
        emits: ['submit:object'],
        template: '<button class="image-btn-stub" @click="$emit(\'submit:object\', { src: \'/new.jpg\', alt: \'New alt\', title: \'\' })"><slot /></button>',
      },
    };
    const item = createContentItem('photo-gallery');
    const wrapper = mount(PhotoGalleryGridEditor, {
      props: { modelValue: item.json_content, options: item.options },
      global: { stubs },
    });

    await findButtonByText(wrapper, 'add_first_image').trigger('click');
    expect((wrapper.emitted('update:modelValue')?.at(-1)?.[0] as unknown[]).length).toBe(1);
  });

  it('display distributes images round-robin and opens the lightbox at the clicked index', async () => {
    const element = {
      type: 'photo-gallery',
      json_content: Array.from({ length: 6 }, (_, i) => ({ src: `/img-${i}.webp`, alt: `img ${i}`, heightClass: 'h-52', decorations: [] })),
      options: { columns: '3' as const, gap: 'medium' as const, showLightbox: true },
    };
    const wrapper = mount(PhotoGalleryGridDisplay, {
      props: { element },
      global: {
        stubs: {
          VueEasyLightbox: { props: ['index', 'visible'], template: '<div data-testid="lightbox" :data-index="index" :data-visible="visible" />' },
        },
      },
    });

    // Round-robin distribution over 3 columns puts images in DOM order
    // [0, 3, 1, 4, 2, 5] (column-major). The 4th tile in the DOM is therefore
    // original image #4 — clicking it must open the lightbox at index 4, not
    // at the column-length-sum the buggy version computed.
    const tiles = wrapper.findAll('.group.cursor-pointer');
    expect(tiles).toHaveLength(6);
    await tiles[3]!.trigger('click');
    expect(wrapper.find('[data-testid="lightbox"]').attributes('data-index')).toBe('4');
  });
});
