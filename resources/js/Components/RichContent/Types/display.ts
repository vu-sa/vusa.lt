import { defineAsyncComponent, type Component } from 'vue';

export type BlockWidth = 'prose' | 'content' | 'wide' | 'full';
export interface ContentTypeSkeleton { height: string }
export interface ContentDisplayType {
  value: string;
  defaultWidth: BlockWidth;
  allowedWidths?: BlockWidth[];
  selfSpaced?: boolean;
  usesSectionChrome?: boolean;
  bandRole?: 'flow' | 'band' | ((options?: Record<string, unknown> | null) => 'flow' | 'band');
  serverResolved?: true;
  display: Component;
  skeleton?: ContentTypeSkeleton;
}
const DEFAULT_SKELETON: ContentTypeSkeleton = { height: 'min-h-[100px]' };
export const displayTypeRegistry: Record<string, ContentDisplayType> = {
  'tiptap': {
    value: 'tiptap',
    defaultWidth: 'prose',
    allowedWidths: ['prose', 'content'],
    display: defineAsyncComponent(() => import('./TiptapDisplay.vue')),
  },
  'shadcn-accordion': {
    value: 'shadcn-accordion',
    defaultWidth: 'full',
    allowedWidths: ['prose', 'content', 'wide', 'full'],
    selfSpaced: true,
    bandRole: 'band',
    usesSectionChrome: true,
    display: defineAsyncComponent(() => import('../RCAccordion.vue')),
    skeleton: { height: 'min-h-[200px]' },
  },
  'shadcn-card': {
    value: 'shadcn-card',
    defaultWidth: 'prose',
    allowedWidths: ['prose', 'content'],
    display: defineAsyncComponent(() => import('../RichContentCard.vue')),
  },
  'image-grid': {
    value: 'image-grid',
    defaultWidth: 'wide',
    allowedWidths: ['content', 'wide', 'full'],
    display: defineAsyncComponent(() => import('./ImageGridDisplay.vue')),
    skeleton: { height: 'min-h-[300px]' },
  },
  'hero': {
    value: 'hero',
    defaultWidth: 'full',
    allowedWidths: ['content', 'wide', 'full'],
    selfSpaced: true,
    bandRole: options => (options?.variant === 'panel' ? 'flow' : 'band'),
    display: defineAsyncComponent(() => import('../RCHeroSection/HeroElement.vue')),
    skeleton: { height: 'min-h-[45rem]' },
  },
  'news': {
    value: 'news',
    defaultWidth: 'full',
    allowedWidths: ['content', 'wide', 'full'],
    selfSpaced: true,
    bandRole: 'band',
    serverResolved: true,
    display: defineAsyncComponent(() => import('@/Components/Public/NewsElement.vue')),
    skeleton: { height: 'min-h-[400px]' },
  },
  'calendar': {
    value: 'calendar',
    defaultWidth: 'full',
    allowedWidths: ['content', 'wide', 'full'],
    selfSpaced: true,
    bandRole: 'band',
    serverResolved: true,
    display: defineAsyncComponent(() => import('@/Components/Public/FullWidth/EventCalendarElement.vue')),
    skeleton: { height: 'min-h-[500px]' },
  },
  'institution-list': {
    value: 'institution-list',
    defaultWidth: 'full',
    allowedWidths: ['content', 'wide', 'full'],
    selfSpaced: true,
    bandRole: 'band',
    serverResolved: true,
    display: defineAsyncComponent(() => import('../RCInstitutionList/InstitutionListDisplay.vue')),
    skeleton: { height: 'min-h-[400px]' },
  },
  'spotify-embed': {
    value: 'spotify-embed',
    defaultWidth: 'prose',
    allowedWidths: ['prose', 'content', 'wide', 'full'],
    selfSpaced: true,
    bandRole: options => (options?.variant === 'promo' ? 'band' : 'flow'),
    display: defineAsyncComponent(() => import('../RCSpotifyEmbed.vue')),
  },
  'social-embed': {
    value: 'social-embed',
    defaultWidth: 'prose',
    allowedWidths: ['prose', 'content'],
    display: defineAsyncComponent(() => import('../RCSocialEmbed.vue')),
  },
  'flow-graph': {
    value: 'flow-graph',
    defaultWidth: 'wide',
    allowedWidths: ['content', 'wide', 'full'],
    display: defineAsyncComponent(() => import('../RCFlowGraph.vue')),
  },
  'number-stat-section': {
    value: 'number-stat-section',
    defaultWidth: 'full',
    allowedWidths: ['prose', 'content', 'wide', 'full'],
    selfSpaced: true,
    bandRole: 'band',
    usesSectionChrome: true,
    display: defineAsyncComponent(() => import('../RCNumberStatSection/RCNumberSection.vue')),
    skeleton: { height: 'min-h-[200px]' },
  },
  'text-box': {
    value: 'text-box',
    defaultWidth: 'prose',
    allowedWidths: ['prose', 'content'],
    display: defineAsyncComponent(() => import('./TextBoxDisplay.vue')),
    skeleton: { height: 'min-h-[200px]' },
  },
  'content-grid': {
    value: 'content-grid',
    defaultWidth: 'wide',
    allowedWidths: ['content', 'wide', 'full'],
    bandRole: 'band',
    usesSectionChrome: true,
    display: defineAsyncComponent(() => import('./ContentGridDisplay.vue')),
  },
  'carousel-slide-deck': {
    value: 'carousel-slide-deck',
    defaultWidth: 'full',
    allowedWidths: ['content', 'wide', 'full'],
    selfSpaced: true,
    bandRole: 'band',
    usesSectionChrome: true,
    display: defineAsyncComponent(() => import('../RCCarouselSlideDeck/CarouselSlideDeckDisplay.vue')),
    skeleton: { height: 'min-h-[600px]' },
  },
  'hero-carousel': {
    value: 'hero-carousel',
    defaultWidth: 'full',
    allowedWidths: ['full'],
    selfSpaced: true,
    bandRole: 'band',
    display: defineAsyncComponent(() => import('../RCHeroCarousel/HeroCarouselDisplay.vue')),
    skeleton: { height: 'min-h-[22rem]' },
  },
  'card-stack': {
    value: 'card-stack',
    defaultWidth: 'full',
    allowedWidths: ['prose', 'content', 'wide', 'full'],
    selfSpaced: true,
    bandRole: 'band',
    usesSectionChrome: true,
    display: defineAsyncComponent(() => import('../RCCardStack/CardStackDisplay.vue')),
    skeleton: { height: 'min-h-[500px]' },
  },
  'photo-gallery': {
    value: 'photo-gallery',
    defaultWidth: 'full',
    allowedWidths: ['content', 'wide', 'full'],
    selfSpaced: true,
    bandRole: 'band',
    usesSectionChrome: true,
    display: defineAsyncComponent(() => import('../RCPhotoGalleryGrid/PhotoGalleryGridDisplay.vue')),
    skeleton: { height: 'min-h-[400px]' },
  },
  'link-list': {
    value: 'link-list',
    defaultWidth: 'full',
    allowedWidths: ['prose', 'content', 'wide', 'full'],
    selfSpaced: true,
    bandRole: 'band',
    serverResolved: true,
    usesSectionChrome: true,
    display: defineAsyncComponent(() => import('../RCLinkList/LinkListDisplay.vue')),
    skeleton: { height: 'min-h-[300px]' },
  },
  'event-list': {
    value: 'event-list',
    defaultWidth: 'full',
    allowedWidths: ['content', 'wide', 'full'],
    selfSpaced: true,
    bandRole: 'band',
    serverResolved: true,
    usesSectionChrome: true,
    display: defineAsyncComponent(() => import('../RCEventList/EventListDisplay.vue')),
    skeleton: { height: 'min-h-[300px]' },
  },
  'section': {
    value: 'section',
    defaultWidth: 'full',
    allowedWidths: ['content', 'wide', 'full'],
    selfSpaced: true,
    bandRole: 'band',
    usesSectionChrome: true,
    display: defineAsyncComponent(() => import('../RCSection/SectionDisplay.vue')),
    skeleton: { height: 'min-h-[120px]' },
  },
  'process-steps': {
    value: 'process-steps',
    defaultWidth: 'wide',
    allowedWidths: ['content', 'wide', 'full'],
    selfSpaced: true,
    bandRole: 'band',
    usesSectionChrome: true,
    display: defineAsyncComponent(() => import('../RCProcessSteps/ProcessStepsDisplay.vue')),
    skeleton: { height: 'min-h-[160px]' },
  },
  'person-quote': {
    value: 'person-quote',
    defaultWidth: 'content',
    allowedWidths: ['prose', 'content', 'wide'],
    selfSpaced: true,
    bandRole: 'band',
    usesSectionChrome: true,
    display: defineAsyncComponent(() => import('../RCPersonQuote/PersonQuoteDisplay.vue')),
    skeleton: { height: 'min-h-[200px]' },
  },
  'timetable': {
    value: 'timetable',
    defaultWidth: 'prose',
    allowedWidths: ['prose', 'content', 'wide'],
    selfSpaced: true,
    display: defineAsyncComponent(() => import('../RCTimetable/TimetableDisplay.vue')),
    skeleton: { height: 'min-h-[120px]' },
  },
};
export function getDisplayType(type: string): ContentDisplayType {
  return displayTypeRegistry[type] ?? displayTypeRegistry.tiptap!;
}
export function getSkeletonForType(type: string): ContentTypeSkeleton {
  return getDisplayType(type).skeleton ?? DEFAULT_SKELETON;
}
