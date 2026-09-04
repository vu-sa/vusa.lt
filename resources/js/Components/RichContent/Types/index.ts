import { defineAsyncComponent, type Component } from 'vue';

import TiptapDisplay from './TiptapDisplay.vue';

// Re-export all type definitions
export * from './types';
import TextCaseUppercase20Filled from '~icons/fluent/text-case-uppercase20-filled';
import AppsListDetail24Regular from '~icons/fluent/apps-list-detail24-regular';
import CalendarDay24Regular from '~icons/fluent/calendar-day24-regular';
import ImageMultiple24Regular from '~icons/fluent/image-multiple24-regular';
import SpotifyIcon from '~icons/simple-icons/spotify';
import NewsIcon from '~icons/fluent/news24-regular';
import CalendarIcon from '~icons/fluent/calendar-ltr24-regular';
import FlowIcon from '~icons/fluent/flow24-regular';
import NumberIcon from '~icons/fluent/number-symbol24-regular';
import HeroIcon from '~icons/fluent/slide-play24-regular';
import GridIcon from '~icons/fluent/table-simple24-regular';
import SocialIcon from '~icons/fluent/share-24-regular';
import TextBoxIcon from '~icons/fluent/text-field24-regular';
import CarouselIcon from '~icons/fluent/swipe-right24-regular';
import HeroCarouselIcon from '~icons/fluent/filmstrip-image24-regular';
import StackIcon from '~icons/fluent/stack24-regular';
import GalleryIcon from '~icons/fluent/collections24-regular';
import LinkListIcon from '~icons/fluent/link-multiple24-regular';
import EventListIcon from '~icons/fluent/calendar-multiple24-regular';
import PersonQuoteIcon from '~icons/fluent/text-quote24-regular';
import SectionIcon from '~icons/fluent/text-header-1-24-regular';
import SpacerIcon from '~icons/fluent/align-space-evenly-vertical-24-regular';
import TimetableIcon from '~icons/fluent/calendar-clock20-regular';
import ProcessStepsIcon from '~icons/fluent/text-number-list-ltr-24-regular';
import CtaBandIcon from '~icons/fluent/megaphone-loud-24-regular';

/**
 * Canvas column a block resolves to (see `.rc-canvas` in app.css). `prose` is the
 * default/unclassed column — narrow reading measure. `full` reaches the canvas edge.
 */
export type BlockWidth = 'prose' | 'content' | 'wide' | 'full';

/** Picker grouping — `special` is for rare, very-specific-rendering blocks (news/calendar/flow-graph). */
export type BlockCategory = 'text' | 'media' | 'section' | 'embed' | 'special';

export interface ContentTypeSkeleton {
  /** Reserved min-height class, applied only to the Suspense fallback (not permanently). */
  height: string;
  /** Inline template string for the fallback component. */
  template: string;
}

export interface ContentType {
  value: string;
  label: string;
  icon: Component;
  description?: string;
  isNew?: boolean;

  /** Picker grouping (Phase 5). */
  category: BlockCategory;
  /** Canvas column used when the block has no `options.width` of its own. */
  defaultWidth: BlockWidth;
  /** Widths offered in the block's width picker; omit to lock it to `defaultWidth`. */
  allowedWidths?: BlockWidth[];
  /**
   * The display renders its own section chrome (background + vertical padding), so the
   * canvas should not add its own top-margin flow on top of it (`.rc-flush`).
   */
  selfSpaced?: boolean;

  /**
   * The display renders through RCSection.vue and exposes the shared section-chrome
   * options (title/eyebrow/presentation/…) via RCSectionOptions. RCBlockCard uses this
   * to decide whether to show the "this block is a section" indicator chip. Orthogonal
   * to `bandRole` — a type can render a `SectionHeader` without ever being a band (none
   * currently do), or vice versa (`hero-carousel`, `cta-band`).
   */
  usesSectionChrome?: boolean;

  /**
   * Whether this type can render as a full-bleed, auto-alternating "band" (see
   * `bandLayout.ts`) or always sits in the flow with no ground of its own. A function
   * form covers types whose answer depends on a variant (`hero`, `spotify-embed`).
   * Omitted entirely means `'flow'` (`resolveBandRole` treats a missing field the same
   * as `'flow'`).
   */
  bandRole?: 'flow' | 'band' | ((options?: Record<string, unknown> | null) => 'flow' | 'band');

  defaultContent: () => any;
  defaultOptions?: () => Record<string, any>;

  /** Async-loaded editor component (`ContentEditorFactory`'s edit mode). */
  editor: Component;
  /** Display component. Only `tiptap` is loaded synchronously (most common type). */
  display: Component;

  /**
   * This type's display honours an `editable` prop and renders its text fields through
   * `RCInlineText`/a mounted `TiptapEditor` instead of static text. Gates whether
   * `BlockPreviewRenderer` passes `editable`/`band-slot` down at all — same gate pattern
   * as `serverResolved`/`resolved`, so an undeclared prop never falls through and
   * stringifies into the DOM on a display that doesn't ask for it.
   */
  inlineEditable?: true;

  /** Suspense fallback shown while `display` loads. Falls back to a generic skeleton. */
  skeleton?: ContentTypeSkeleton;

  /**
   * The server resolves this block's data (see `App\Services\ContentResolution`) and
   * ships it in the page's `resolvedParts` prop. RichContentParser only forwards its
   * `resolved` prop to types that declare this — an undeclared object prop on other
   * displays would otherwise fall through and stringify into the DOM as
   * `resolved="[object Object]"`. Keep in sync with
   * `ContentPartResolver::resolvableTypes()` (see registry.component.test.ts).
   */
  serverResolved?: true;
}

const DEFAULT_SKELETON: ContentTypeSkeleton = {
  height: 'min-h-[100px]',
  template: `
    <div class="w-full py-4 space-y-4">
      <Skeleton class="h-6 w-3/4" />
      <Skeleton class="h-4 w-full" />
      <Skeleton class="h-4 w-5/6" />
    </div>
  `,
};

export const contentTypeRegistry: Record<string, ContentType> = {
  'tiptap': {
    value: 'tiptap',
    label: 'Tekstas',
    icon: TextCaseUppercase20Filled,
    description: 'Redaguojamas teksto blokas su formatavimo galimybėmis',
    category: 'text',
    defaultWidth: 'prose',
    allowedWidths: ['prose', 'content'],
    defaultContent: () => ({}),
    editor: defineAsyncComponent(() => import('./TiptapEditor.vue')),
    display: TiptapDisplay,
  },
  'shadcn-accordion': {
    value: 'shadcn-accordion',
    label: 'Išsiskleidžiantis sąrašas',
    icon: AppsListDetail24Regular,
    description: 'Išsiskleidžiantis turinio blokas, kur rodomas tik pavadinimas',
    category: 'text',
    defaultWidth: 'full',
    // Full-bleed is the default, not a lock — these render their own section chrome
    // (background/padding) regardless of width, so authors can still narrow them.
    // `prose` is offered so an accordion can line up with a `prose` text block.
    allowedWidths: ['prose', 'content', 'wide', 'full'],
    selfSpaced: true,
    defaultContent: () => ([]),
    usesSectionChrome: true,
    bandRole: 'band',
    editor: defineAsyncComponent(() => import('./AccordionEditor.vue')),
    display: defineAsyncComponent(() => import('../RCAccordion.vue')),
    skeleton: {
      height: 'min-h-[200px]',
      template: `
        <div class="w-full py-16 px-4 bg-secondary/40">
          <div class="space-y-4">
            <div v-for="i in 3" :key="i" class="border border-border rounded-lg p-4 bg-card">
              <Skeleton class="h-5 w-3/4" />
            </div>
          </div>
        </div>
      `,
    },
  },
  'shadcn-card': {
    value: 'shadcn-card',
    label: 'Kortelė',
    icon: CalendarDay24Regular,
    description: 'Specialiai apipavidalintas tekstas su antrašte',
    category: 'text',
    defaultWidth: 'prose',
    allowedWidths: ['prose', 'content'],
    defaultContent: () => ({}),
    defaultOptions: () => ({
      title: '',
    }),
    inlineEditable: true,
    editor: defineAsyncComponent(() => import('./CardEditor.vue')),
    display: defineAsyncComponent(() => import('../RichContentCard.vue')),
  },
  'image-grid': {
    value: 'image-grid',
    label: 'Nuotraukų tinklelis',
    icon: ImageMultiple24Regular,
    description: 'Kelių nuotraukų išdėstymas tinkleliu',
    category: 'media',
    defaultWidth: 'wide',
    allowedWidths: ['content', 'wide', 'full'],
    defaultContent: () => ([]),
    editor: defineAsyncComponent(() => import('./ImageGridEditor.vue')),
    display: defineAsyncComponent(() => import('./ImageGridDisplay.vue')),
    skeleton: {
      height: 'min-h-[300px]',
      template: `
        <div class="w-full py-4">
          <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <Skeleton v-for="i in 6" :key="i" class="aspect-square rounded-md" />
          </div>
        </div>
      `,
    },
  },
  'hero': {
    value: 'hero',
    label: 'Hero',
    icon: HeroIcon,
    isNew: true,
    description: 'Didelis turinio blokas su paveiksliuku',
    category: 'section',
    defaultWidth: 'full',
    // Full-bleed is the default, not a lock — these render their own section chrome
    // (background/padding) regardless of width, so authors can still narrow them.
    allowedWidths: ['content', 'wide', 'full'],
    selfSpaced: true,
    defaultContent: () => ({
      title: '',
      description: '',
      eyebrow: '',
      imageSrc: '',
      imageAlt: '',
      objectPosition: '40% 65%',
      overlayContent: {
        title: '',
        subtitle: '',
      },
      buttons: [],
    }),
    defaultOptions: () => ({
      variant: 'split',
      textLeft: true,
      imageDecorations: [
        { type: 'line', position: 'top-right', size: 'md' },
        { type: 'square', position: 'top-left', size: 'md' },
      ],
    }),
    // `panel` keeps its own fixed gradient-panel chrome and ignores presentation/alternation.
    bandRole: options => (options?.variant === 'panel' ? 'flow' : 'band'),
    inlineEditable: true,
    editor: defineAsyncComponent(() => import('../RCHeroSection/HeroForm.vue')),
    display: defineAsyncComponent(() => import('../RCHeroSection/HeroElement.vue')),
    skeleton: {
      height: 'min-h-[45rem]',
      template: `
        <div class="w-full min-h-[45rem] bg-secondary/40 animate-pulse flex items-end justify-center pb-16">
          <div class="flex flex-col items-center gap-4 max-w-2xl px-8">
            <Skeleton class="h-12 w-96 max-w-full" />
            <Skeleton class="h-6 w-64 max-w-full" />
            <Skeleton class="h-12 w-40 rounded-full mt-4" />
          </div>
        </div>
      `,
    },
  },
  'news': {
    value: 'news',
    label: 'Naujienos',
    icon: NewsIcon,
    description: 'Naujienų blokas',
    category: 'special',
    defaultWidth: 'full',
    // Full-bleed is the default, not a lock — these render their own section chrome
    // (background/padding) regardless of width, so authors can still narrow them.
    allowedWidths: ['content', 'wide', 'full'],
    selfSpaced: true,
    serverResolved: true,
    defaultContent: () => ({ title: '' }),
    editor: defineAsyncComponent(() => import('./NewsEditor.vue')),
    display: defineAsyncComponent(() => import('@/Components/Public/NewsElement.vue')),
    skeleton: {
      height: 'min-h-[400px]',
      template: `
        <div class="w-full py-4">
          <div class="grid gap-4 sm:gap-6 grid-cols-1 lg:grid-cols-[2fr_1fr] px-4 md:px-8">
            <div class="space-y-4">
              <Skeleton class="aspect-video w-full rounded-md" />
              <Skeleton class="h-4 w-32" />
              <Skeleton class="h-8 w-3/4" />
              <Skeleton class="h-20 w-full" />
            </div>
            <div class="space-y-3">
              <Skeleton class="h-6 w-24" />
              <div v-for="i in 4" :key="i" class="flex items-center gap-3 py-2">
                <Skeleton class="w-16 h-12 rounded flex-shrink-0" />
                <div class="flex-1 space-y-2">
                  <Skeleton class="h-3 w-full" />
                  <Skeleton class="h-2 w-20" />
                </div>
              </div>
            </div>
          </div>
        </div>
      `,
    },
  },
  'calendar': {
    value: 'calendar',
    label: 'Kalendorius',
    icon: CalendarIcon,
    description: 'Kalendoriaus blokas',
    category: 'special',
    defaultWidth: 'full',
    // Full-bleed is the default, not a lock — these render their own section chrome
    // (background/padding) regardless of width, so authors can still narrow them.
    allowedWidths: ['content', 'wide', 'full'],
    selfSpaced: true,
    serverResolved: true,
    defaultContent: () => ({ title: '' }),
    defaultOptions: () => ({ allTenants: false }),
    // EventCalendarElement.vue is not registered as `bandRole: 'band'` — it always
    // renders its own hardcoded band, deliberately outside the alternation. See its docblock.
    editor: defineAsyncComponent(() => import('./CalendarEditor.vue')),
    display: defineAsyncComponent(() => import('@/Components/Public/FullWidth/EventCalendarElement.vue')),
    skeleton: {
      height: 'min-h-[500px]',
      template: `
        <div class="w-full py-8 px-4 md:px-8">
          <Skeleton class="h-8 w-48 mb-6" />
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="i in 6" :key="i" class="space-y-3 p-4 border border-border rounded-lg">
              <Skeleton class="h-4 w-24" />
              <Skeleton class="h-6 w-full" />
              <Skeleton class="h-3 w-3/4" />
            </div>
          </div>
        </div>
      `,
    },
  },
  'spotify-embed': {
    value: 'spotify-embed',
    label: 'Spotify / Mixcloud',
    icon: SpotifyIcon,
    description: 'Spotify grojaraščio ar Mixcloud įrašo įterpimas, arba pilna reklaminė sekcija su grotuvu',
    category: 'embed',
    // `prose` stays the default — the common case is still a link dropped mid-article, and
    // every embed already saved has no `options.width` of its own so must keep resolving here.
    // `promo` (see SpotifyEmbedEditor/RCSpotifyEmbed) additionally offers wide/full so the
    // two-column section isn't stuck at the reading measure.
    defaultWidth: 'prose',
    allowedWidths: ['prose', 'content', 'wide', 'full'],
    // Self-spaced for both variants: `promo` paints its own vertical rhythm, and `inline`
    // already carries its own `my-8` on the embed frame, so the canvas's flow margin on top of
    // that was only ever double-spacing it.
    selfSpaced: true,
    defaultContent: () => ({ url: '' }),
    defaultOptions: () => ({ variant: 'inline' }),
    // `inline` is a plain bordered embed dropped into prose — flow. `promo` reads as its
    // own section beside the page's other bands.
    bandRole: options => (options?.variant === 'promo' ? 'band' : 'flow'),
    editor: defineAsyncComponent(() => import('./SpotifyEmbedEditor.vue')),
    display: defineAsyncComponent(() => import('../RCSpotifyEmbed.vue')),
  },
  'social-embed': {
    value: 'social-embed',
    label: 'Facebook / Instagram',
    icon: SocialIcon,
    description: 'Facebook arba Instagram įrašo įterpimas',
    category: 'embed',
    defaultWidth: 'prose',
    allowedWidths: ['prose', 'content'],
    defaultContent: () => ({ url: '', platform: null, postId: '' }),
    defaultOptions: () => ({ showCaption: true }),
    editor: defineAsyncComponent(() => import('./SocialEmbedEditor.vue')),
    display: defineAsyncComponent(() => import('../RCSocialEmbed.vue')),
  },
  'flow-graph': {
    value: 'flow-graph',
    label: 'Flow Graph',
    icon: FlowIcon,
    description: 'Proceso eigos schema',
    category: 'special',
    defaultWidth: 'wide',
    allowedWidths: ['content', 'wide', 'full'],
    defaultContent: () => ({ preset: 'VusaStructure' }),
    editor: defineAsyncComponent(() => import('./FlowGraphEditor.vue')),
    display: defineAsyncComponent(() => import('../RCFlowGraph.vue')),
  },
  'number-stat-section': {
    value: 'number-stat-section',
    label: 'Skaitinės statistikos',
    icon: NumberIcon,
    description: 'Skaičių statistikos sekcija',
    category: 'section',
    defaultWidth: 'full',
    // Full-bleed is the default, not a lock — these render their own section chrome
    // (background/padding) regardless of width, so authors can still narrow them.
    // `prose` lets a number row align with a `prose` text block.
    allowedWidths: ['prose', 'content', 'wide', 'full'],
    selfSpaced: true,
    defaultContent: () => ([]),
    defaultOptions: () => ({ title: '' }),
    usesSectionChrome: true,
    bandRole: 'band',
    editor: defineAsyncComponent(() => import('./NumberStatEditor.vue')),
    display: defineAsyncComponent(() => import('../RCNumberStatSection/RCNumberSection.vue')),
    skeleton: {
      height: 'min-h-[200px]',
      template: `
        <div class="w-full py-12 px-4 md:px-8">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            <div v-for="i in 4" :key="i" class="flex flex-col items-center gap-2">
              <Skeleton class="h-16 w-24" />
              <Skeleton class="h-4 w-32" />
            </div>
          </div>
        </div>
      `,
    },
  },
  'text-box': {
    value: 'text-box',
    label: 'Teksto laukas',
    icon: TextBoxIcon,
    description: 'Teksto įvedimo laukas su pateikimo mygtuku',
    isNew: false,
    category: 'embed',
    defaultWidth: 'prose',
    allowedWidths: ['prose', 'content'],
    defaultContent: () => ({}),
    defaultOptions: () => ({
      title: { lt: '', en: '' },
      placeholder: { lt: '', en: '' },
      isClosed: false,
      closedMessage: { lt: '', en: '' },
    }),
    editor: defineAsyncComponent(() => import('./TextBoxEditor.vue')),
    display: defineAsyncComponent(() => import('./TextBoxDisplay.vue')),
    skeleton: {
      height: 'min-h-[200px]',
      template: `
        <div class="w-full rounded-lg border border-border p-6">
          <Skeleton class="h-6 w-48 mb-4" />
          <Skeleton class="h-28 w-full mb-3" />
          <Skeleton class="h-10 w-28 rounded-md" />
        </div>
      `,
    },
  },
  'content-grid': {
    value: 'content-grid',
    label: 'Tinklelis',
    icon: GridIcon,
    description: 'Lankstus turinys stulpeliais ir eilutėmis',
    category: 'section',
    defaultWidth: 'wide',
    allowedWidths: ['content', 'wide', 'full'],
    defaultContent: () => ([
      {
        columns: [
          {
            width: 'col-span-6',
            content: {
              type: 'tiptap',
              value: {},
            },
          },
          {
            width: 'col-span-6',
            content: {
              type: 'tiptap',
              value: {},
            },
          },
        ],
      },
    ]),
    defaultOptions: () => ({
      gap: 'gap-4',
      mobileStacking: true,
      equalHeight: false,
    }),
    usesSectionChrome: true,
    bandRole: 'band',
    editor: defineAsyncComponent(() => import('./ContentGridEditor.vue')),
    display: defineAsyncComponent(() => import('./ContentGridDisplay.vue')),
  },
  'carousel-slide-deck': {
    value: 'carousel-slide-deck',
    label: 'Karuselė',
    icon: CarouselIcon,
    description: 'Skaidrių karuselė su paveiksliukais ir turiniu',
    isNew: true,
    category: 'section',
    defaultWidth: 'full',
    // Full-bleed is the default, not a lock — these render their own section chrome
    // (background/padding) regardless of width, so authors can still narrow them.
    allowedWidths: ['content', 'wide', 'full'],
    selfSpaced: true,
    defaultContent: () => ([]),
    defaultOptions: () => ({
      autoplay: true,
      autoplayDelay: 8000,
      showNavigation: true,
      showThumbnails: true,
    }),
    usesSectionChrome: true,
    bandRole: 'band',
    editor: defineAsyncComponent(() => import('./CarouselSlideDeckEditor.vue')),
    display: defineAsyncComponent(() => import('../RCCarouselSlideDeck/CarouselSlideDeckDisplay.vue')),
    skeleton: {
      height: 'min-h-[600px]',
      template: `
        <div class="w-full py-12 px-4">
          <div class="grid lg:grid-cols-2 gap-8 md:gap-12 items-center bg-card rounded-2xl p-8 md:p-12 border border-border">
            <div class="space-y-4 md:space-y-6">
              <Skeleton class="h-6 w-32" />
              <Skeleton class="h-8 w-3/4" />
              <Skeleton class="h-20 w-full" />
            </div>
            <Skeleton class="h-64 md:h-80" />
          </div>
        </div>
      `,
    },
  },
  'hero-carousel': {
    value: 'hero-carousel',
    label: 'Hero karuselė',
    icon: HeroCarouselIcon,
    isNew: true,
    description: 'Viso pločio karuselė su didelėmis nuotraukomis ir tekstu ant jų',
    category: 'section',
    defaultWidth: 'full',
    // Full-bleed is the default, not a lock — but unlike RCSection-based types there
    // is no section chrome; narrowing to content/wide insets the photo panel instead.
    allowedWidths: ['content', 'wide', 'full'],
    selfSpaced: true,
    defaultContent: () => ([]),
    defaultOptions: () => ({
      autoplay: true,
      autoplayDelay: 8000,
      showArrows: true,
      showIndicators: true,
      scrim: 'medium',
      height: 'md',
    }),
    bandRole: 'band',
    editor: defineAsyncComponent(() => import('./HeroCarouselEditor.vue')),
    display: defineAsyncComponent(() => import('../RCHeroCarousel/HeroCarouselDisplay.vue')),
    skeleton: {
      height: 'min-h-[22rem]',
      template: `
        <div class="w-full px-4 pt-4 pb-4 sm:px-6 sm:pt-6 sm:pb-5 lg:px-8">
          <div class="min-h-[22rem] rounded-2xl md:rounded-3xl bg-secondary/40 animate-pulse relative overflow-hidden">
            <div class="absolute inset-0 flex items-end p-8">
              <div class="space-y-3 max-w-xl">
                <Skeleton class="h-4 w-24" />
                <Skeleton class="h-10 w-3/4" />
                <Skeleton class="h-5 w-1/2" />
                <Skeleton class="h-11 w-36 rounded-full" />
              </div>
            </div>
          </div>
        </div>
      `,
    },
  },
  'card-stack': {
    value: 'card-stack',
    label: 'Kortelių krūva',
    icon: StackIcon,
    description: 'Interaktyvi kortelių krūva su 3D efektu',
    isNew: true,
    category: 'section',
    defaultWidth: 'full',
    // Full-bleed is the default, not a lock — these render their own section chrome
    // (background/padding) regardless of width, so authors can still narrow them.
    // `prose` lets a card stack align with a `prose` text block.
    allowedWidths: ['prose', 'content', 'wide', 'full'],
    selfSpaced: true,
    defaultContent: () => ([]),
    defaultOptions: () => ({
      autoplay: true,
      autoplayDelay: 5000,
      hintText: '',
    }),
    usesSectionChrome: true,
    bandRole: 'band',
    editor: defineAsyncComponent(() => import('./CardStackEditor.vue')),
    display: defineAsyncComponent(() => import('../RCCardStack/CardStackDisplay.vue')),
    skeleton: {
      height: 'min-h-[500px]',
      template: `
        <div class="w-full py-16 bg-secondary/40 px-4">
          <div class="max-w-lg mx-auto">
            <Skeleton class="h-80 w-full rounded-xl border border-border" />
            <div class="flex justify-center mt-8 space-x-2">
              <Skeleton v-for="i in 3" :key="i" class="w-3 h-3 rounded-full" />
            </div>
          </div>
        </div>
      `,
    },
  },
  'photo-gallery': {
    value: 'photo-gallery',
    label: 'Nuotraukų galerija',
    icon: GalleryIcon,
    description: 'Nuotraukų galerija su švieslente',
    isNew: true,
    category: 'media',
    defaultWidth: 'full',
    // Full-bleed is the default, not a lock — these render their own section chrome
    // (background/padding) regardless of width, so authors can still narrow them.
    allowedWidths: ['content', 'wide', 'full'],
    selfSpaced: true,
    defaultContent: () => ([]),
    defaultOptions: () => ({
      columns: '4',
      gap: 'medium',
      showLightbox: true,
    }),
    usesSectionChrome: true,
    bandRole: 'band',
    editor: defineAsyncComponent(() => import('./PhotoGalleryGridEditor.vue')),
    display: defineAsyncComponent(() => import('../RCPhotoGalleryGrid/PhotoGalleryGridDisplay.vue')),
    skeleton: {
      height: 'min-h-[400px]',
      template: `
        <div class="w-full py-12 px-4">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-2 md:gap-4">
            <Skeleton v-for="i in 8" :key="i" class="h-52 rounded-lg" />
          </div>
        </div>
      `,
    },
  },
  'link-list': {
    value: 'link-list',
    label: 'Nuorodų sąrašas',
    icon: LinkListIcon,
    description: 'Kelios nuorodos į naujienas, puslapius ar rankiniu būdu įvestas nuorodas',
    isNew: true,
    category: 'section',
    defaultWidth: 'full',
    // `prose` lets a link list align with a `prose` text block.
    allowedWidths: ['prose', 'content', 'wide', 'full'],
    selfSpaced: true,
    serverResolved: true,
    defaultContent: () => ({ links: [] }),
    defaultOptions: () => ({
      source: 'news',
      mode: 'latest',
      tenantScope: 'current',
      limit: 3,
      style: 'photo',
    }),
    usesSectionChrome: true,
    bandRole: 'band',
    editor: defineAsyncComponent(() => import('./LinkListEditor.vue')),
    display: defineAsyncComponent(() => import('../RCLinkList/LinkListDisplay.vue')),
    skeleton: {
      height: 'min-h-[300px]',
      template: `
        <div class="w-full py-16 px-4">
          <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <Skeleton v-for="i in 3" :key="i" class="h-64 rounded-2xl" />
          </div>
        </div>
      `,
    },
  },
  'event-list': {
    value: 'event-list',
    label: 'Renginių sąrašas',
    icon: EventListIcon,
    description: 'Filtruotas, po padalinius grupuojamas renginių sąrašas',
    isNew: true,
    category: 'section',
    defaultWidth: 'full',
    allowedWidths: ['content', 'wide', 'full'],
    selfSpaced: true,
    serverResolved: true,
    defaultContent: () => ({}),
    defaultOptions: () => ({
      mode: 'upcoming',
      tenantScope: 'current',
      groupBy: 'none',
      limit: 12,
      style: 'cards',
    }),
    usesSectionChrome: true,
    bandRole: 'band',
    editor: defineAsyncComponent(() => import('./EventListEditor.vue')),
    display: defineAsyncComponent(() => import('../RCEventList/EventListDisplay.vue')),
    skeleton: {
      height: 'min-h-[300px]',
      template: `
        <div class="w-full py-16 px-4">
          <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <Skeleton v-for="i in 3" :key="i" class="h-64 rounded-2xl" />
          </div>
        </div>
      `,
    },
  },
  'section': {
    value: 'section',
    label: 'Sekcija',
    icon: SectionIcon,
    description: 'Sekcijos antraštė, kuri apima sekančius blokus iki kitos sekcijos',
    isNew: true,
    category: 'section',
    defaultWidth: 'full',
    // Locked to full — a section marker is always full-bleed chrome around its
    // children; a `content`/`wide` section would fight with its own nested canvas.
    allowedWidths: ['full'],
    selfSpaced: true,
    defaultContent: () => ({}),
    defaultOptions: () => ({ inner: 'full', wraps: 'following' }),
    usesSectionChrome: true,
    bandRole: 'band',
    editor: defineAsyncComponent(() => import('./SectionEditor.vue')),
    display: defineAsyncComponent(() => import('../RCSection/SectionDisplay.vue')),
    skeleton: {
      height: 'min-h-[120px]',
      template: `
        <div class="w-full py-16 px-4 flex flex-col items-center gap-3">
          <Skeleton class="h-7 w-64 max-w-full" />
          <Skeleton class="h-4 w-40" />
        </div>
      `,
    },
  },
  'process-steps': {
    value: 'process-steps',
    label: 'Žingsniai',
    icon: ProcessStepsIcon,
    description: 'Sunumeruoti proceso žingsniai',
    isNew: true,
    category: 'section',
    defaultWidth: 'wide',
    allowedWidths: ['content', 'wide', 'full'],
    selfSpaced: true,
    defaultContent: () => ([
      { title: '', text: '' },
      { title: '', text: '' },
      { title: '', text: '' },
    ]),
    defaultOptions: () => ({ columns: 3, align: 'start' }),
    usesSectionChrome: true,
    bandRole: 'band',
    editor: defineAsyncComponent(() => import('./ProcessStepsEditor.vue')),
    display: defineAsyncComponent(() => import('../RCProcessSteps/ProcessStepsDisplay.vue')),
    skeleton: {
      height: 'min-h-[160px]',
      template: `
        <div class="grid w-full gap-8 py-16 sm:grid-cols-3">
          <div v-for="i in 3" :key="i" class="space-y-3">
            <Skeleton class="h-8 w-12" />
            <Skeleton class="h-5 w-32" />
            <Skeleton class="h-12 w-full" />
          </div>
        </div>
      `,
    },
  },
  'cta-band': {
    value: 'cta-band',
    label: 'Kvietimas veikti',
    icon: CtaBandIcon,
    description: 'Firminės spalvos juosta su antrašte ir mygtuku',
    isNew: true,
    category: 'section',
    defaultWidth: 'full',
    // Locked to full: the band paints its own ground edge to edge, so a narrower canvas column
    // would just clip the fill without changing where the copy sits.
    allowedWidths: ['full'],
    selfSpaced: true,
    defaultContent: () => ({ heading: '', text: '', items: [], button: { label: '', href: '' } }),
    defaultOptions: () => ({}),
    // Always the one loud emphasis band, regardless of options.presentation (it has no
    // presentation control at all) — see resolveBand's cta-band special-case.
    bandRole: 'band',
    editor: defineAsyncComponent(() => import('./CtaBandEditor.vue')),
    display: defineAsyncComponent(() => import('../RCCtaBand/CtaBandDisplay.vue')),
    skeleton: {
      height: 'min-h-[160px]',
      template: `
        <div class="flex w-full flex-col gap-4 bg-secondary/40 px-5 py-14 sm:px-6 lg:px-8 lg:py-20">
          <Skeleton class="h-9 w-72 max-w-full" />
          <Skeleton class="h-5 w-full max-w-xl" />
          <Skeleton class="h-12 w-48" />
        </div>
      `,
    },
  },
  'person-quote': {
    value: 'person-quote',
    label: 'Asmens citata',
    icon: PersonQuoteIcon,
    description: 'Citata su nurodyto asmens nuotrauka ir pareigomis',
    isNew: true,
    category: 'text',
    defaultWidth: 'content',
    allowedWidths: ['prose', 'content', 'wide'],
    selfSpaced: true,
    defaultContent: () => ({ quote: {}, snapshot: { name: '' } }),
    defaultOptions: () => ({
      align: 'center',
      showAvatar: true,
    }),
    usesSectionChrome: true,
    bandRole: 'band',
    editor: defineAsyncComponent(() => import('./PersonQuoteEditor.vue')),
    display: defineAsyncComponent(() => import('../RCPersonQuote/PersonQuoteDisplay.vue')),
    skeleton: {
      height: 'min-h-[200px]',
      template: `
        <div class="w-full py-12 px-4 flex flex-col items-center gap-4">
          <Skeleton class="h-6 w-2/3" />
          <Skeleton class="h-6 w-1/2" />
          <Skeleton class="h-10 w-10 rounded-full" />
        </div>
      `,
    },
  },
  'spacer': {
    value: 'spacer',
    label: 'Tarpas',
    icon: SpacerIcon,
    description: 'Vertikalus tarpas tarp blokų',
    isNew: true,
    category: 'section',
    defaultWidth: 'prose',
    // No visible chrome — width is a no-op on an empty block, so it's locked to the
    // default prose column rather than offering a meaningless width picker.
    selfSpaced: true,
    defaultContent: () => ({}),
    defaultOptions: () => ({ size: 'md' }),
    editor: defineAsyncComponent(() => import('./SpacerEditor.vue')),
    display: defineAsyncComponent(() => import('./SpacerDisplay.vue')),
    // No skeleton — the block renders instantly (a single <div> with a height class),
    // and the empty fallback would flash more than the real thing.
  },
  'timetable': {
    value: 'timetable',
    label: 'Tvarkaraštis',
    icon: TimetableIcon,
    isNew: true,
    description: 'Laikų ir pavadinimų tvarkaraščio kortelė',
    category: 'section',
    defaultWidth: 'prose',
    allowedWidths: ['prose', 'content', 'wide'],
    // Owns its own card chrome (gradient + heading), so the canvas rhythm should not
    // add a top-margin flow on top of it.
    selfSpaced: true,
    defaultContent: () => ([]),
    defaultOptions: () => ({}),
    editor: defineAsyncComponent(() => import('./TimetableEditor.vue')),
    display: defineAsyncComponent(() => import('../RCTimetable/TimetableDisplay.vue')),
    skeleton: {
      height: 'min-h-[120px]',
      template: `
        <div class="w-full overflow-hidden border border-border bg-secondary/40">
          <div class="flex items-center gap-2 border-b border-border px-5 py-3">
            <Skeleton class="h-4 w-24" />
          </div>
          <div v-for="i in 3" :key="i" class="flex items-center gap-4 px-5 py-3">
            <Skeleton class="h-4 w-16" />
            <Skeleton class="h-4 flex-1" />
          </div>
        </div>
      `,
    },
  },
};

export const getAllContentTypes = (): ContentType[] => {
  return Object.values(contentTypeRegistry);
};

export const getContentType = (type: string): ContentType => {
  return contentTypeRegistry[type] ?? contentTypeRegistry['tiptap']!;
};

export const getSkeletonForType = (type: string): ContentTypeSkeleton => {
  return contentTypeRegistry[type]?.skeleton ?? DEFAULT_SKELETON;
};

export const createContentItem = (type: string) => {
  const contentType = getContentType(type);
  return {
    type,
    json_content: contentType.defaultContent(),
    options: contentType.defaultOptions ? contentType.defaultOptions() : { is_active: true },
    key: Math.random().toString(36).substring(7),
    expanded: true,
  };
};
