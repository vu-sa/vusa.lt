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
  rounded?: 'none' | 'sm' | 'md' | 'lg';
  /** Semantic heading level for the section title (rendered by SectionHeader). Defaults to 2. */
  headingLevel?: 2 | 3 | 4;
  /** Title alignment, forwarded to SectionHeader. Defaults to 'center'. */
  align?: 'center' | 'start';
  /** Whether to render the separator bar beneath the title. Defaults to true. */
  showSeparator?: boolean;
}

// Implemented

/**
 * A marker block, not a container — see `RichContentParser.vue`'s `groupedContent`.
 * It carries no content of its own; every part that follows it in the flat
 * `content_parts` order becomes a child rendered inside its `<section>`, up to the
 * next `section` marker (or the end of the content).
 */
export interface Section {
  json_content: Record<string, never>;
  options: SectionOptions & {
    /** Inner content max-width for the section's own header/canvas — independent of the block's own canvas column. */
    inner?: 'prose' | 'content' | 'wide' | 'full';
    /** `following` (default): wraps every part up to the next section marker. `none`: header-only, wraps nothing. */
    wraps?: 'following' | 'none';
  };
}

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
        /** Which image corner the overlay card sits in — see ImageWithDecorations.vue. */
        overlayCorner?: 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right';
        /** `true` lets the card straddle the image edge; `false` (default) keeps it fully contained. */
        overlayOverhang?: boolean;
        overlayPadding?: 'sm' | 'md' | 'lg';
        decorations?: DecorationConfig[];
      };
    }[];
  }[];
  options: SectionOptions & {
    gap?: 'gap-2' | 'gap-4' | 'gap-6' | 'gap-8';
    mobileStacking?: boolean;
    equalHeight?: boolean;
    /** Vertical alignment of column content within each row — grid items stretch by default. */
    verticalAlign?: 'stretch' | 'start' | 'center' | 'end';
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
    /** `split` only. Which image corner the overlay card sits in — see ImageWithDecorations.vue. */
    overlayCorner?: 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right';
    /** `true` lets the card straddle the image edge; `false` (default) keeps it fully contained. */
    overlayOverhang?: boolean;
    overlayPadding?: 'sm' | 'md' | 'lg';
    buttons?: {
      text: string;
      link: string;
      variant?: 'default' | 'outline';
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
    /**
     * `split`/`centered`/`banner` only — `panel` keeps its own fixed gradient chrome.
     * Defaults reproduce each variant's previous hardcoded look exactly (see HeroElement.vue).
     */
    background?: 'none' | 'muted' | 'contrast' | 'gradient';
    padding?: 'none' | 'sm' | 'md' | 'lg';
    rounded?: 'none' | 'sm' | 'md' | 'lg';
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
  /** Localized category name, or null for the many articles filed under none. */
  category?: string | null;
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
    /** CMS-stored icon name (see cardIcons.ts) shown in a badge above the title. Optional — a card with no icon centers its text vertically instead (RCCardStack/CardStackDisplay.vue). */
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

/**
 * Full-bleed hero carousel: each slide is a big background photo with a gradient
 * scrim and an overlaid text block (eyebrow/title/subtitle/Tiptap description) plus
 * Hero-shaped buttons. The deck-style card carousel is `CarouselSlideDeck` above.
 * No SectionOptions — the photo is the background, so section chrome has no surface.
 */
export interface HeroCarousel {
  json_content: {
    /** Small uppercase label above the title (Hero's `eyebrow`). Optional. */
    eyebrow?: string;
    title: string;
    /** Short line under the title, rendered above the Tiptap description. Optional. */
    subtitle?: string;
    /** Authored as Tiptap JSON and rendered client-side via RichContentTiptapHTML (same as CarouselSlideDeck). */
    description: Tiptap['json_content'];
    imageSrc: string;
    imageAlt: string;
    /** `"x% y%"` CSS object-position, set via FocalPointPicker. Optional — old rows crop from center. */
    objectPosition?: string;
    /** Where the overlaid text block sits on the photo. Defaults to 'start' (bottom-left). */
    align?: 'start' | 'center' | 'end';
    /** Exact Hero button shape — rendered by the shared HeroButtons.vue. */
    buttons?: Hero['json_content']['buttons'];
  }[];
  options: {
    autoplay?: boolean;
    autoplayDelay?: number;
    showArrows?: boolean;
    showIndicators?: boolean;
    /** Gradient scrim strength over the photos — keeps overlaid text legible. */
    scrim?: 'light' | 'medium' | 'dark';
    /** Panel height preset — the photo panel is inset, so this is the panel's own height. */
    height?: 'sm' | 'md' | 'lg';
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
    /** `imageUrl` only renders in `style: 'photo'` — `compact` never shows it. */
    links: { title: string; url: string; imageUrl?: string | null }[];
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
    /** e.g. `"VU "` — prefixed onto the tenant fullname when grouped, reproducing SummerCamps' faculty naming. `full` style only. */
    tenantLabelPrefix?: string;
    /**
     * `full` (default): `tenantLabelPrefix` + the locative `fullname`.
     * `faculty`: `"VU " + nominative faculty` derived from the fullname (e.g. "VU Filologijos fakultetas"
     * from "...atstovybė Filologijos fakultete"); the central tenant falls back to its fullname.
     * Mirrors the client-side `getFacultyName` util (see EventListResolver::facultyLabel).
     */
    tenantLabelStyle?: 'full' | 'faculty';
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
 * Empty layout block whose only job is to insert a controlled vertical gap between
 * its siblings. The canvas's own `--rc-flow` rhythm is fixed (~2.5rem) and applies
 * uniformly to every sibling pair; this block — flagged `selfSpaced` so it picks up
 * `.rc-flush` — replaces that rhythm with a height the author picks from `options.size`.
 * No `json_content`, no resolver, no visible chrome.
 */
export interface Spacer {
  json_content: Record<string, never>;
  options: {
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl';
  };
}

/**
 * A static schedule of time rows — start/end time + a label each. The optional
 * "import from meeting" helper in the editor pre-fills rows from a meeting's
 * agenda, but they persist as a snapshot (never re-fetch on read), so a page
 * keeps the timetable the author approved even after the meeting's agenda moves.
 */
export interface Timetable {
  json_content: {
    startTime?: string;
    endTime?: string;
    title: string;
  }[];
  options: {
    title?: string;
  } | null;
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
    showAvatar?: boolean;
  };
}
