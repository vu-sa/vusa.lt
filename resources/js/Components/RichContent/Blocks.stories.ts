import type { Meta, StoryObj } from '@storybook/vue3-vite';

import BlockPreviewRenderer from './Editor/BlockPreviewRenderer.vue';
import { contentSamples } from './Types/samples';

import { usePage } from '@/mocks/inertia.storybook';

/**
 * The RichContent block displays, on the public surface.
 *
 * These are the ~20 components that render a page's actual content, and they were migrated to
 * the design system in one pass — so this is where that pass can be checked: every block, both
 * themes, one scroll. Blocks are what the site is made of; the primitives in `Public/Base` are
 * only what the blocks are made of.
 *
 * Fixtures come from `Types/samples.ts`, the same data the admin block picker previews with, so
 * a block that looks right here looks right to an author choosing it. Rendering goes through
 * `BlockPreviewRenderer` for the same reason — it is the shared preview root, and it stamps
 * `data-surface="public"` and the block's canvas column exactly as the editor does.
 *
 * a11y stays at the inherited `'todo'` rather than `'error'`: these render CMS-authored content,
 * and the placeholder text and images in the fixtures are not the real thing to assert against.
 * The primitives under `Public/Base` are where `'error'` belongs.
 */
const pageProps = {
  app: { locale: 'lt', subdomain: 'www', name: 'VU SA', url: 'http://www.vusa.test', path: 'lt' },
  auth: { user: null, can: {} },
  tenant: { alias: 'vusa', shortname: 'VU SA' },
  flash: {},
  errors: {},
};

/** The canvas is what gives `rc-full` / `rc-wide` blocks their column — without it they clamp. */
function renderBlock(type: string, overrides: Record<string, unknown> = {}) {
  const sample = { ...contentSamples[type]!(), ...overrides };

  return {
    components: { BlockPreviewRenderer },
    setup: () => ({
      element: { id: 1, type, json_content: sample.json_content, options: sample.options ?? null },
      resolved: sample.resolved,
    }),
    template: `
      <div class="rc-canvas" style="--rc-measure: 44rem">
        <BlockPreviewRenderer :element="element" :resolved="resolved" />
      </div>
    `,
  };
}

const meta: Meta = {
  title: 'Public/Blocks',
  tags: ['autodocs'],
  /**
   * A decorator, not `beforeEach`: the latter does not run when Storybook renders a Docs page,
   * and several displays read `usePage()` during setup. See Chrome.stories.ts for the full story.
   */
  decorators: [
    (story) => {
      usePage.mockImplementation(() => ({ props: pageProps }));

      return story();
    },
  ],
};

export default meta;
type Story = StoryObj;

/** Full-bleed band, grayscale photo on the fixed-dark ink ground, copy hung off a brand rule. */
export const HeroCarousel: Story = { render: () => renderBlock('hero-carousel') };

/** The `split` variant — the two-column text + image hero. */
export const Hero: Story = { render: () => renderBlock('hero') };

/**
 * `style: 'list'` — the design's scannable event row: a ruled date block, the title, and its
 * when/where on one line. The `cards` style below is the same data in the grouped card shape.
 */
export const EventListRows: Story = {
  render: () => renderBlock('event-list', (() => {
    const sample = contentSamples['event-list']!();
    const resolved = sample.resolved as { groups: { items: unknown[] }[]; items: unknown[]; meta: Record<string, unknown> };

    return {
      options: { ...sample.options, style: 'list' },
      // The flat list reads `items`, the card layout reads `groups` — the fixture only fills the
      // latter, so flatten it here rather than duplicating the fixture.
      resolved: { ...resolved, groups: [], items: resolved.groups.flatMap(g => g.items), meta: { ...resolved.meta, style: 'list' } },
    };
  })()),
};

export const EventListCards: Story = { render: () => renderBlock('event-list') };

export const LinkList: Story = { render: () => renderBlock('link-list') };

export const CardStack: Story = { render: () => renderBlock('card-stack') };

export const ContentGrid: Story = { render: () => renderBlock('content-grid') };

export const NumberStats: Story = { render: () => renderBlock('number-stat-section') };

export const Timetable: Story = { render: () => renderBlock('timetable') };

export const PersonQuote: Story = { render: () => renderBlock('person-quote') };

export const Accordion: Story = { render: () => renderBlock('shadcn-accordion') };

/** The `outline` card variant with its brand accent rail. */
export const Card: Story = { render: () => renderBlock('shadcn-card') };

export const ImageGrid: Story = { render: () => renderBlock('image-grid') };

export const PhotoGallery: Story = { render: () => renderBlock('photo-gallery') };

export const CarouselSlideDeck: Story = { render: () => renderBlock('carousel-slide-deck') };

export const TextBox: Story = { render: () => renderBlock('text-box') };

export const Section: Story = { render: () => renderBlock('section') };

/**
 * Every block in one scroll. This is the acceptance view for the migration: any block still
 * carrying a white card, a soft shadow or a rounded corner shows up against its neighbours here
 * in a way it never would on its own story.
 */
export const AllBlocks: Story = {
  render: () => ({
    components: { BlockPreviewRenderer },
    setup: () => ({
      blocks: Object.keys(contentSamples).map((type, index) => {
        const sample = contentSamples[type]!();

        return {
          key: `${type}-${index}`,
          label: type,
          element: { id: index + 1, type, json_content: sample.json_content, options: sample.options ?? null },
          resolved: sample.resolved,
        };
      }),
    }),
    template: `
      <div class="flex flex-col gap-16">
        <section v-for="block in blocks" :key="block.key">
          <p class="u-eyebrow mb-3">{{ block.label }}</p>
          <div class="rc-canvas border-t border-border pt-6" style="--rc-measure: 44rem">
            <BlockPreviewRenderer :element="block.element" :resolved="block.resolved" />
          </div>
        </section>
      </div>
    `,
  }),
};
