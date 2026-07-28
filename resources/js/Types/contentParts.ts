// Type definitions for content parts used in the rich content editor
// These types correspond to the ContentPartEnum in enums.ts

/**
 * Shared RCSection.vue chrome fields — mixed into the `options` of every type that
 * renders through RCSection (see Editor/RCSectionOptions.vue, the one editor fieldset
 * behind all of them).
 */
export interface SectionOptions {
  title?: string;
  subtitle?: string;
  background?: 'none' | 'muted' | 'contrast' | 'gradient';
  padding?: 'none' | 'sm' | 'md' | 'lg';
}

// Implemented

// Content Grid type - updated with the simplified structure
export interface ContentGrid {
  json_content: {
    columns: {
      width: string; // e.g. "col-span-4", "col-span-6", etc.
      content: {
        type: string; // 'tiptap' | 'image' | 'card'
        value: any; // Content depends on the type — tiptap doc, image src, or card object
        /** `type: 'image'` only. */
        alt?: string;
        title?: string;
        /** `"x% y%"` CSS object-position, set via FocalPointPicker. */
        objectPosition?: string;
        overlayContent?: { title: string; subtitle: string };
        decorations?: DecorationConfig[];
      };
    }[];
  }[];
  options: SectionOptions & {
    gap?: 'gap-2' | 'gap-4' | 'gap-6' | 'gap-8';
    mobileStacking?: boolean;
    equalHeight?: boolean;
  };
}

export interface ImageGrid {
  json_content: {
    colspan: 'col-span-2' | 'col-span-3' | 'col-span-4' | 'col-span-full';
    image: string;
    /** Optional so existing rows saved before this field existed still validate. */
    alt?: string;
    title?: string;
    /** `"x% y%"` CSS object-position, set via FocalPointPicker. Optional — old rows crop from center. */
    objectPosition?: string;
  }[];
  options: null;
}

export interface Tiptap {
  json_content: {
    type: 'doc';
    content: Record<string, any>[];
  };
  options: null;
}

export interface ShadcnAccordion {
  json_content: {
    label: string;
    content: Tiptap['json_content'];
  }[];
  options: SectionOptions | null;
}

export interface ShadcnCard {
  json_content: Tiptap['json_content'];
  options: {
    color?: 'zinc' | 'red' | 'yellow';
    variant?: 'outline' | 'soft';
    title?: string;
    isTitleColored?: boolean;
    /** @deprecated Cards no longer render an icon — ignored on display. Optional so old rows keep validating. */
    showIcon?: boolean;
  };
}

export interface Hero {
  json_content: {
    title: string;
    description: string;
    /** Small uppercase label above the title. Used by `centered`/`banner`/`panel`; optional on `split`. */
    eyebrow?: string;
    imageSrc: string;
    imageAlt: string;
    objectPosition?: string;
    overlayContent?: {
      title: string;
      subtitle: string;
    };
    buttons?: {
      text: string;
      link: string;
      variant?: 'default' | 'outline';
      color?: 'red' | 'yellow' | 'zinc' | 'white';
      /** CMS-stored icon name (see `cardIcons.ts`), rendered before the button text. Optional — most buttons have none. */
      icon?: string;
    }[];
  };
  options: {
    textLeft?: boolean;
    imageDecorations?: DecorationConfig[];
    /**
     * `split` (default): two-column text + image — the original hero.
     * `centered`: no image, centred title/description/buttons — CTA/slogan sections.
     * `banner`: a compact single-row strip, title + one button.
     * `panel`: the SummerCamps-style rounded gradient panel with a square thumbnail.
     */
    variant?: 'split' | 'centered' | 'banner' | 'panel';
  };
}

export interface DecorationConfig {
  type: 'circle' | 'line' | 'square';
  position: 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right';
  size: 'sm' | 'md' | 'lg';
  color?: 'vusa-red' | 'vusa-yellow' | 'zinc';
  opacity?: number;
  rotation?: boolean;
}

export interface SpotifyEmbed {
  json_content: {
    url: string;
  };
  options: null;
}

export interface SocialEmbed {
  json_content: {
    url: string;
    platform: 'facebook' | 'instagram' | null;
    postId?: string;
  };
  options: {
    showCaption?: boolean;
  };
}

export interface FlowGraph {
  json_content: {
    nodes?: Record<string, any>[];
    edges?: Record<string, any>[];
    preset?: 'VusaStructure';
  };
  options: null;
}

export interface NumberStatSection {
  json_content: {
    endNumber: number;
    label: string;
    showPlus?: boolean;
  }[];
  options: SectionOptions & {
    color?: 'zinc' | 'red' | 'yellow';
  };
}

// Now implemented

export interface Calendar {
  json_content: {
    title: string;
  };
  options: {
    allTenants?: boolean;
  } | null;
}

export interface News {
  json_content: {
    title: string;
  };
  options: null;
}

export interface TextBox {
  json_content: Record<string, never>;
  options: {
    title?: { lt: string; en: string };
    placeholder?: { lt: string; en: string };
    isClosed?: boolean;
    closedMessage?: { lt: string; en: string };
  };
}

/**
 * Public-facing news item structure returned by API and Inertia props.
 * Matches the shape from NewsCollection::toPublicArray()
 */
export interface NewsItem {
  id: number;
  title: string;
  lang: string;
  short: string;
  publish_time: string;
  permalink: string | null;
  image: string;
}

export interface CarouselSlideDeck {
  json_content: {
    icon: string;
    badge: string;
    title: string;
    description: string;
    imageSrc: string;
    imageAlt: string;
    imageLeft: boolean;
    decorations?: DecorationConfig[];
    /** `"x% y%"` CSS object-position, set via FocalPointPicker. Optional — old rows crop from center. */
    objectPosition?: string;
  }[];
  options: SectionOptions & {
    autoplay?: boolean;
    autoplayDelay?: number;
    showNavigation?: boolean;
    showThumbnails?: boolean;
  };
}

export interface CardStack {
  json_content: {
    /** @deprecated Cards no longer render an icon — kept optional so old rows still validate. */
    icon?: string;
    title: string;
    description: string;
  }[];
  options: SectionOptions & {
    autoplay?: boolean;
    autoplayDelay?: number;
    hintText?: string;
  };
}

export interface PhotoGalleryGrid {
  json_content: {
    src: string;
    alt: string;
    title?: string;
    heightClass?: string;
    decorations?: DecorationConfig[];
    /** `"x% y%"` CSS object-position, set via FocalPointPicker. Optional — old rows crop from center. */
    objectPosition?: string;
  }[];
  options: SectionOptions & {
    columns?: '2' | '3' | '4';
    gap?: 'small' | 'medium' | 'large';
    showLightbox?: boolean;
  };
}

/**
 * Server-resolved (see `App\Services\ContentResolution\Resolvers\LinkListResolver`)
 * dynamic list of links to news, pages, or manually-typed URLs. Only `manual` links
 * are stored author-side (`json_content.links`) — `news`/`pages` sources are resolved
 * fresh on every request from `options`, so the editor never stores the actual titles
 * or hrefs shown publicly.
 */
export interface LinkList {
  json_content: {
    links: { title: string; url: string }[];
    /**
     * Editor-only bookkeeping so `CollectionSelectDialog` can re-open with the
     * currently-pinned news/pages pre-checked (it needs a title to render a pinned
     * row, which `options.newsIds`/`pageIds` alone don't carry). Never read by
     * `LinkListResolver` — it re-fetches the live records by id on every request.
     */
    pinnedNews?: { id: number; title: string }[];
    pinnedPages?: { id: number; title: string }[];
  };
  options: SectionOptions & {
    source?: 'news' | 'pages' | 'manual';
    mode?: 'latest' | 'specific';
    categoryAlias?: string;
    tenantScope?: 'current' | 'all' | number[];
    newsIds?: number[];
    pageIds?: number[];
    limit?: number;
    style?: 'photo' | 'compact';
    emptyMessage?: string;
  };
}

/** One resolved link-list item — shape emitted by `LinkListResolver::resolvePart()`. */
export interface LinkListResolvedItem {
  id: number | string | null;
  title: string;
  href: string;
  imageUrl: string | null;
  publishedAt: string | null;
}

export interface LinkListResolved {
  type: 'link-list';
  items: LinkListResolvedItem[];
  meta: { total: number; truncated: boolean; droppedForLocale: number };
}

/**
 * Server-resolved (see `EventListResolver`) filtered, optionally tenant-grouped list
 * of Calendar events — the generalization of `PublicPageController::summerCamps()`.
 * Entirely option-driven; there is no author-written `json_content`.
 */
export interface EventList {
  json_content: Record<string, never>;
  options: SectionOptions & {
    mode?: 'upcoming' | 'range' | 'year';
    year?: number;
    dateFrom?: string;
    dateTo?: string;
    categoryAlias?: string;
    tenantScope?: 'current' | 'all' | number[];
    groupBy?: 'none' | 'tenant';
    limit?: number;
    style?: 'cards' | 'list';
    /** e.g. `"VU "` — prefixed onto the tenant fullname when grouped, reproducing SummerCamps' faculty naming. */
    tenantLabelPrefix?: string;
    emptyMessage?: string;
  };
}

/** One resolved event — shape emitted by `EventListResolver::mapEvent()`. */
export interface EventListResolvedItem {
  id: number;
  title: string;
  date: string | null;
  endDate: string | null;
  location: string | null;
  isAllDay: boolean;
  ctoUrl: string | null;
  imageUrl: string | null;
  href: string;
}

export interface EventListResolvedGroup {
  key: string;
  label: string;
  items: EventListResolvedItem[];
}

export interface EventListResolved {
  type: 'event-list';
  groups: EventListResolvedGroup[];
  items: EventListResolvedItem[];
  meta: { total: number; truncated: boolean; style: 'cards' | 'list' };
}

/**
 * Static — no resolver. Stores an author-approved snapshot of a picked user rather
 * than a live reference, so the quote never re-renders a departed person's current
 * photo/duty (see `ContentPartResolver`'s docblock for why this type is excluded).
 */
export interface PersonQuote {
  json_content: {
    quote: Tiptap['json_content'];
    snapshot: {
      userId?: number;
      name: string;
      photoUrl?: string;
      attribution?: string;
    };
  };
  options: SectionOptions & {
    align?: 'start' | 'center';
    showAvatar?: boolean;
  };
}
