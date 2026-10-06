import { defineAsyncComponent, type Component } from 'vue';

import { displayTypeRegistry, type ContentDisplayType } from './display';

export { getSkeletonForType } from './display';
export type { BlockWidth, ContentTypeSkeleton } from './display';

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
import TimetableIcon from '~icons/fluent/calendar-clock20-regular';
import ProcessStepsIcon from '~icons/fluent/text-number-list-ltr-24-regular';
import InstitutionListIcon from '~icons/fluent/building-multiple24-regular';

/** Picker grouping — `special` is for rare, very-specific-rendering blocks (news/calendar/flow-graph). */
export type BlockCategory = 'text' | 'media' | 'section' | 'embed' | 'special';

export interface ContentType extends ContentDisplayType {
  category?: BlockCategory;
  label: string;
  icon: Component;
  description?: string;
  isNew?: boolean;

  defaultContent: () => unknown;
  defaultOptions?: () => Record<string, unknown>;

  /** Async-loaded editor component (`ContentEditorFactory`'s edit mode). */
  editor: Component;
  /** Canvas-only editing surface; published rendering always uses display. */
  editableDisplay?: Component;

  /** Legacy displays accept inline editing props. */
  inlineEditable?: true;
}

export const contentTypeRegistry: Record<string, ContentType> = {
  'tiptap': {
    ...displayTypeRegistry['tiptap'],
    editableDisplay: defineAsyncComponent(() => import('./TiptapEditable.vue')),
    label: 'Tekstas',
    icon: TextCaseUppercase20Filled,
    description: 'Redaguojamas teksto blokas su formatavimo galimybėmis',
    category: 'text',
    defaultContent: () => ({}),
    inlineEditable: true,
    editor: defineAsyncComponent(() => import('./TiptapEditor.vue')),
  },
  'shadcn-accordion': {
    ...displayTypeRegistry['shadcn-accordion'],
    label: 'Išsiskleidžiantis sąrašas',
    icon: AppsListDetail24Regular,
    description: 'Išsiskleidžiantis turinio blokas, kur rodomas tik pavadinimas',
    category: 'text',
    // Full-bleed is the default, not a lock — these render their own section chrome
    // (background/padding) regardless of width, so authors can still narrow them.
    // `prose` is offered so an accordion can line up with a `prose` text block.
    defaultContent: () => ([
      { label: '', content: {} },
    ]),
    inlineEditable: true,
    editor: defineAsyncComponent(() => import('./AccordionEditor.vue')),
  },
  'shadcn-card': {
    ...displayTypeRegistry['shadcn-card'],
    label: 'Kortelė',
    icon: CalendarDay24Regular,
    description: 'Specialiai apipavidalintas tekstas su antrašte',
    category: 'text',
    defaultContent: () => ({}),
    defaultOptions: () => ({
      title: '',
      verticalSpacing: 'default',
    }),
    inlineEditable: true,
    editor: defineAsyncComponent(() => import('./CardEditor.vue')),
  },
  'image-grid': {
    ...displayTypeRegistry['image-grid'],
    label: 'Nuotraukų tinklelis',
    icon: ImageMultiple24Regular,
    description: 'Kelių nuotraukų išdėstymas tinkleliu',
    category: 'media',
    inlineEditable: true,
    defaultContent: () => ([
      { colspan: 'col-span-2', image: '', alt: '', title: '' },
    ]),
    editor: defineAsyncComponent(() => import('./ImageGridEditor.vue')),
  },
  'hero': {
    ...displayTypeRegistry['hero'],
    label: 'Hero',
    icon: HeroIcon,
    description: 'Didelis turinio blokas su paveiksliuku',
    category: 'section',
    // Full-bleed is the default, not a lock — these render their own section chrome
    // (background/padding) regardless of width, so authors can still narrow them.
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
    inlineEditable: true,
    editor: defineAsyncComponent(() => import('../RCHeroSection/HeroForm.vue')),
    editableDisplay: defineAsyncComponent(() => import('../RCHeroSection/HeroEditableElement.vue')),
  },
  'news': {
    ...displayTypeRegistry['news'],
    label: 'Naujienos',
    icon: NewsIcon,
    description: 'Naujienų blokas',
    category: 'special',
    // Full-bleed is the default, not a lock — these render their own section chrome
    // (background/padding) regardless of width, so authors can still narrow them.
    inlineEditable: true,
    defaultContent: () => ({ title: '', eyebrow: '' }),
    defaultOptions: () => ({ tenantScope: 'all', limit: 4 }),
    editor: defineAsyncComponent(() => import('./NewsEditor.vue')),
  },
  'calendar': {
    ...displayTypeRegistry['calendar'],
    label: 'Kalendorius',
    icon: CalendarIcon,
    description: 'Kalendoriaus blokas',
    category: 'special',
    // Full-bleed is the default, not a lock — these render their own section chrome
    // (background/padding) regardless of width, so authors can still narrow them.
    inlineEditable: true,
    defaultContent: () => ({ title: '' }),
    defaultOptions: () => ({ tenantScope: 'all', limit: 3 }),
    editor: defineAsyncComponent(() => import('./CalendarEditor.vue')),
  },
  'institution-list': {
    ...displayTypeRegistry['institution-list'],
    label: 'Institucijų sąrašas',
    icon: InstitutionListIcon,
    description: 'Institucijų ar iniciatyvų sąrašo blokas pagal tipą',
    category: 'special',
    inlineEditable: true,
    defaultContent: () => ({ title: '', eyebrow: '' }),
    defaultOptions: () => ({ tenantScope: 'all', typeSlug: 'pkp', limit: null }),
    editor: defineAsyncComponent(() => import('./InstitutionListEditor.vue')),
  },
  'spotify-embed': {
    ...displayTypeRegistry['spotify-embed'],
    label: 'Spotify / Mixcloud',
    icon: SpotifyIcon,
    description: 'Spotify grojaraščio ar Mixcloud įrašo įterpimas, arba pilna reklaminė sekcija su grotuvu',
    category: 'embed',
    // `prose` stays the default — the common case is still a link dropped mid-article, and
    // every embed already saved has no `options.width` of its own so must keep resolving here.
    // `promo` (see SpotifyEmbedEditor/RCSpotifyEmbed) additionally offers wide/full so the
    // two-column section isn't stuck at the reading measure.
    // Self-spaced for both variants: `promo` paints its own vertical rhythm, and `inline`
    // already carries its own `my-8` on the embed frame, so the canvas's flow margin on top of
    // that was only ever double-spacing it.
    defaultContent: () => ({ url: '' }),
    defaultOptions: () => ({ variant: 'inline' }),
    // `inline` is a plain bordered embed dropped into prose — flow. `promo` reads as its
    // own section beside the page's other bands.
    inlineEditable: true,
    editor: defineAsyncComponent(() => import('./SpotifyEmbedEditor.vue')),
  },
  'social-embed': {
    ...displayTypeRegistry['social-embed'],
    label: 'Facebook / Instagram',
    icon: SocialIcon,
    description: 'Facebook arba Instagram įrašo įterpimas',
    category: 'embed',
    defaultContent: () => ({ url: '', platform: null, postId: '' }),
    defaultOptions: () => ({ showCaption: true }),
    inlineEditable: true,
    editor: defineAsyncComponent(() => import('./SocialEmbedEditor.vue')),
  },
  'flow-graph': {
    ...displayTypeRegistry['flow-graph'],
    label: 'Flow Graph',
    icon: FlowIcon,
    description: 'Proceso eigos schema',
    category: 'special',
    defaultContent: () => ({ preset: 'VusaStructure' }),
    inlineEditable: true,
    editor: defineAsyncComponent(() => import('./FlowGraphEditor.vue')),
  },
  'number-stat-section': {
    ...displayTypeRegistry['number-stat-section'],
    label: 'Skaitinės statistikos',
    icon: NumberIcon,
    description: 'Skaičių statistikos sekcija',
    category: 'section',
    // Full-bleed is the default, not a lock — these render their own section chrome
    // (background/padding) regardless of width, so authors can still narrow them.
    // `prose` lets a number row align with a `prose` text block.
    defaultContent: () => ([
      { endNumber: 0, label: '' },
    ]),
    defaultOptions: () => ({ title: '' }),
    inlineEditable: true,
    editor: defineAsyncComponent(() => import('./NumberStatEditor.vue')),
  },
  'text-box': {
    ...displayTypeRegistry['text-box'],
    label: 'Teksto laukas',
    icon: TextBoxIcon,
    description: 'Teksto įvedimo laukas su pateikimo mygtuku',
    category: 'embed',
    defaultContent: () => ({}),
    defaultOptions: () => ({
      title: { lt: '', en: '' },
      placeholder: { lt: '', en: '' },
      isClosed: false,
      closedMessage: { lt: '', en: '' },
    }),
    inlineEditable: true,
    editor: defineAsyncComponent(() => import('./TextBoxEditor.vue')),
  },
  'content-grid': {
    ...displayTypeRegistry['content-grid'],
    label: 'Tinklelis',
    icon: GridIcon,
    description: 'Lankstus turinys stulpeliais ir eilutėmis',
    category: 'section',
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
    inlineEditable: true,
    editor: defineAsyncComponent(() => import('./ContentGridEditor.vue')),
  },
  'carousel-slide-deck': {
    ...displayTypeRegistry['carousel-slide-deck'],
    label: 'Karuselė',
    icon: CarouselIcon,
    description: 'Skaidrių karuselė su paveiksliukais ir turiniu',
    isNew: false,
    category: 'section',
    // Full-bleed is the default, not a lock — these render their own section chrome
    // (background/padding) regardless of width, so authors can still narrow them.
    defaultContent: () => ([
      {
        icon: 'info',
        badge: '',
        title: '',
        description: '',
        imageSrc: '',
        imageAlt: '',
        imageLeft: false,
        decorations: [],
      },
    ]),
    defaultOptions: () => ({
      autoplay: true,
      autoplayDelay: 8000,
      showNavigation: true,
      showThumbnails: true,
    }),
    inlineEditable: true,
    editor: defineAsyncComponent(() => import('./CarouselSlideDeckEditor.vue')),
  },
  'hero-carousel': {
    ...displayTypeRegistry['hero-carousel'],
    label: 'Hero karuselė',
    icon: HeroCarouselIcon,
    description: 'Viso pločio karuselė su didelėmis nuotraukomis ir tekstu ant jų',
    isNew: false,
    category: 'section',
    // Full-bleed only: the hero-carousel breaks page measure using .rc-viewport, so narrowing is disallowed.
    inlineEditable: true,
    defaultContent: () => ([]),
    defaultOptions: () => ({
      autoplay: true,
      autoplayDelay: 8000,
      showArrows: true,
      showIndicators: true,
      scrim: 'medium',
      height: 'md',
      width: 'full',
    }),
    editor: defineAsyncComponent(() => import('./HeroCarouselEditor.vue')),
  },
  'card-stack': {
    ...displayTypeRegistry['card-stack'],
    label: 'Kortelių krūva',
    icon: StackIcon,
    description: 'Interaktyvi kortelių krūva su 3D efektu',
    isNew: false,
    category: 'section',
    // Full-bleed is the default, not a lock — these render their own section chrome
    // (background/padding) regardless of width, so authors can still narrow them.
    // `prose` lets a card stack align with a `prose` text block.
    defaultContent: () => ([
      { icon: '', title: '', description: '' },
    ]),
    defaultOptions: () => ({
      autoplay: true,
      autoplayDelay: 5000,
      hintText: '',
    }),
    inlineEditable: true,
    editor: defineAsyncComponent(() => import('./CardStackEditor.vue')),
  },
  'photo-gallery': {
    ...displayTypeRegistry['photo-gallery'],
    label: 'Nuotraukų galerija',
    icon: GalleryIcon,
    description: 'Nuotraukų galerija su švieslente',
    isNew: false,
    category: 'media',
    // Full-bleed is the default, not a lock — these render their own section chrome
    // (background/padding) regardless of width, so authors can still narrow them.
    defaultContent: () => ([
      { src: '', alt: '', heightClass: 'h-52', decorations: [] },
    ]),
    defaultOptions: () => ({
      columns: '4',
      gap: 'medium',
      showLightbox: true,
    }),
    inlineEditable: true,
    editor: defineAsyncComponent(() => import('./PhotoGalleryGridEditor.vue')),
  },
  'link-list': {
    ...displayTypeRegistry['link-list'],
    label: 'Nuorodų sąrašas',
    icon: LinkListIcon,
    description: 'Kelios nuorodos į naujienas, puslapius ar rankiniu būdu įvestas nuorodas',
    isNew: false,
    category: 'section',
    // `prose` lets a link list align with a `prose` text block.
    defaultContent: () => ({ links: [] }),
    defaultOptions: () => ({
      source: 'news',
      mode: 'latest',
      tenantScope: 'current',
      limit: 3,
      style: 'photo',
    }),
    inlineEditable: true,
    editor: defineAsyncComponent(() => import('./LinkListEditor.vue')),
  },
  'event-list': {
    ...displayTypeRegistry['event-list'],
    label: 'Renginių sąrašas',
    icon: EventListIcon,
    description: 'Filtruotas, po padalinius grupuojamas renginių sąrašas',
    isNew: false,
    category: 'section',
    defaultContent: () => ({}),
    defaultOptions: () => ({
      mode: 'upcoming',
      tenantScope: 'current',
      groupBy: 'none',
      limit: 12,
      style: 'cards',
    }),
    inlineEditable: true,
    editor: defineAsyncComponent(() => import('./EventListEditor.vue')),
  },
  'section': {
    ...displayTypeRegistry['section'],
    label: 'Sekcija',
    icon: SectionIcon,
    description: 'Sekcijos antraštė, kuri apima sekančius blokus iki kitos sekcijos',
    isNew: false,
    category: 'section',
    // `prose` excluded (unlike accordion/card-stack): a section wraps children with
    // their own independent width choices via the nested canvas
    // (`.rc-canvas-nested` — see canvas.css), and narrowing the section itself caps
    // how wide a `full`-width child can visually reach, regardless of that child's
    // own setting. `prose` would make that mismatch the common case rather than an
    // edge case; `content`/`wide` are narrow enough to be a deliberate authoring
    // choice (a boxed section) without making every full-width child surprising.
    defaultContent: () => ({}),
    defaultOptions: () => ({ inner: 'full', wraps: 'following' }),
    inlineEditable: true,
    editor: defineAsyncComponent(() => import('./SectionEditor.vue')),
  },
  'process-steps': {
    ...displayTypeRegistry['process-steps'],
    label: 'Žingsniai',
    icon: ProcessStepsIcon,
    description: 'Sunumeruoti proceso žingsniai',
    isNew: false,
    category: 'section',
    defaultContent: () => ([
      { title: '', text: '' },
      { title: '', text: '' },
      { title: '', text: '' },
    ]),
    defaultOptions: () => ({ columns: 3, align: 'start' }),
    inlineEditable: true,
    editor: defineAsyncComponent(() => import('./ProcessStepsEditor.vue')),
  },
  'person-quote': {
    ...displayTypeRegistry['person-quote'],
    label: 'Asmens citata',
    icon: PersonQuoteIcon,
    description: 'Citata su nurodyto asmens nuotrauka ir pareigomis',
    isNew: false,
    category: 'text',
    defaultContent: () => ({ quote: {}, snapshot: { name: '' } }),
    defaultOptions: () => ({
      align: 'center',
      showAvatar: true,
    }),
    inlineEditable: true,
    editor: defineAsyncComponent(() => import('./PersonQuoteEditor.vue')),
  },
  'timetable': {
    ...displayTypeRegistry['timetable'],
    label: 'Tvarkaraštis',
    icon: TimetableIcon,
    description: 'Laikų ir pavadinimų tvarkaraščio kortelė',
    isNew: false,
    category: 'section',
    // Owns its own card chrome (gradient + heading), so the canvas rhythm should not
    // add a top-margin flow on top of it.
    defaultContent: () => ([{ startTime: '09:00', endTime: '10:00', title: '' }]),
    defaultOptions: () => ({}),
    inlineEditable: true,
    editor: defineAsyncComponent(() => import('./TimetableEditor.vue')),
  },
};

export const getAllContentTypes = (): ContentType[] => {
  return Object.values(contentTypeRegistry);
};

export const getContentType = (type: string): ContentType => {
  return contentTypeRegistry[type] ?? contentTypeRegistry['tiptap']!;
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
