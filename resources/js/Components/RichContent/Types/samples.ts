/**
 * Realistic fixture data for the block picker's live preview (BlockPickerDialog).
 * Only types that render purely from their own json_content/options — no network
 * fetch, no third-party embed — get a sample here. `news`/`calendar` prefetch data
 * through RichContentParser and `spotify-embed`/`social-embed` load external iframes;
 * none of those should fire just because someone is browsing the picker, so they're
 * left out and the dialog falls back to a static description card for them.
 */

const PLACEHOLDER_IMAGES = [
  '/images/placeholders/foto1.jpg',
  '/images/placeholders/foto2.jpg',
  '/images/placeholders/foto3.jpg',
  '/images/placeholders/foto4.jpg',
  '/images/placeholders/foto5.jpg',
];

function tiptapDoc(paragraphs: string[]) {
  return {
    type: 'doc',
    content: paragraphs.map(text => ({
      type: 'paragraph',
      content: [{ type: 'text', text }],
    })),
  };
}

const LOREM = [
  'Vilniaus universiteto Studentų atstovybė vienija visus Universiteto fakultetus ir atstovauja studentų interesams.',
  'Kiekvienas narys gali prisidėti prie sprendimų priėmimo, iniciatyvų kūrimo ir bendruomenės stiprinimo.',
];

export interface ContentSample {
  json_content: any;
  options?: Record<string, any>;
  /**
   * Fabricated server-resolved payload for `serverResolved` types (link-list,
   * event-list) — the picker still never hits the network; this is what
   * `BlockPreviewRenderer` would otherwise fetch from `useContentPartPreview`.
   */
  resolved?: unknown;
}

export const contentSamples: Record<string, () => ContentSample> = {
  'tiptap': () => ({
    json_content: tiptapDoc(LOREM),
  }),
  'shadcn-accordion': () => ({
    json_content: [
      { label: 'Kaip galiu tapti nariu?', content: tiptapDoc([LOREM[0]!]) },
      { label: 'Ar narystė mokama?', content: tiptapDoc(['Ne, narystė visiškai nemokama.']) },
    ],
  }),
  'shadcn-card': () => ({
    json_content: tiptapDoc(['Svarbi informacija, kurią verta žinoti prieš registruojantis.']),
    options: { variant: 'outline', color: 'red', title: 'Svarbu', isTitleColored: true },
  }),
  'image-grid': () => ({
    json_content: [
      { colspan: 'col-span-3', image: PLACEHOLDER_IMAGES[0], alt: 'Studentų renginys' },
      { colspan: 'col-span-3', image: PLACEHOLDER_IMAGES[1], alt: 'Bendruomenės susitikimas' },
    ],
  }),
  'hero': () => ({
    json_content: {
      title: 'Prisijunk prie bendruomenės',
      description: 'Atrask naujas galimybes ir rask bendraminčių.',
      imageSrc: PLACEHOLDER_IMAGES[0],
      imageAlt: 'Studentai',
      objectPosition: '50% 50%',
      overlayContent: { title: '1500+', subtitle: 'aktyvių narių' },
      buttons: [{ text: 'Sužinoti daugiau', link: '#', variant: 'default', color: 'red' }],
    },
    options: { textLeft: true, imageDecorations: [] },
  }),
  'flow-graph': () => ({
    json_content: { preset: 'VusaStructure' },
  }),
  'number-stat-section': () => ({
    json_content: [
      { endNumber: 35, label: 'metų veikimo' },
      { endNumber: 1500, label: 'narių', showPlus: true },
      { endNumber: 20, label: 'padalinių' },
    ],
    options: { title: 'VU SA skaičiais', color: 'zinc' },
  }),
  'text-box': () => ({
    json_content: {},
    options: {
      title: { lt: 'Pasiūlyk idėją', en: 'Suggest an idea' },
      placeholder: { lt: 'Įrašykite savo pasiūlymą...', en: 'Write your suggestion...' },
      isClosed: false,
      closedMessage: { lt: '', en: '' },
    },
  }),
  'content-grid': () => ({
    json_content: [
      {
        columns: [
          { width: 'col-span-6', content: { type: 'tiptap', value: tiptapDoc([LOREM[0]!]) } },
          { width: 'col-span-6', content: { type: 'tiptap', value: tiptapDoc([LOREM[1]!]) } },
        ],
      },
    ],
    options: { gap: 'gap-4', mobileStacking: true, equalHeight: false },
  }),
  'carousel-slide-deck': () => ({
    json_content: [
      {
        icon: 'users', badge: 'Bendruomenė', title: 'Rask bendraminčių',
        description: tiptapDoc([LOREM[0]!]), imageSrc: PLACEHOLDER_IMAGES[2], imageAlt: 'Studentai', imageLeft: false, decorations: [],
      },
      {
        icon: 'star', badge: 'Poveikis', title: 'Palik pokytį', description: tiptapDoc([LOREM[1]!]),
        imageSrc: PLACEHOLDER_IMAGES[3], imageAlt: 'Renginys', imageLeft: true, decorations: [],
      },
    ],
    options: { autoplay: false, autoplayDelay: 8000, showNavigation: true, showThumbnails: true },
  }),
  'card-stack': () => ({
    json_content: [
      { icon: 'book-open', title: 'Studijos', description: 'Kokybiškos studijos ir joms pritaikyta aplinka.' },
      { icon: 'users', title: 'Bendruomenė', description: 'Stipri ir vieninga universiteto bendruomenė.' },
      { icon: 'trending-up', title: 'Tobulėjimas', description: 'Asmeninis ir profesinis augimas.' },
    ],
    options: { autoplay: false, autoplayDelay: 5000, hintText: 'Spustelėk kortelę' },
  }),
  'photo-gallery': () => ({
    json_content: PLACEHOLDER_IMAGES.map((src, i) => ({ src, alt: `Nuotrauka ${i + 1}`, heightClass: 'h-40', decorations: [] })),
    options: { columns: '3', gap: 'medium', showLightbox: false },
  }),
  'link-list': () => ({
    json_content: { links: [] },
    options: { title: 'Naujausios naujienos', source: 'news', mode: 'latest', limit: 3, style: 'photo' },
    resolved: {
      type: 'link-list',
      items: [
        { id: 1, title: 'Prasideda studentų atstovų rinkimai', href: '#', imageUrl: PLACEHOLDER_IMAGES[0], publishedAt: '2026-01-10T00:00:00+00:00' },
        { id: 2, title: 'Naujas bendradarbiavimas su fakultetais', href: '#', imageUrl: PLACEHOLDER_IMAGES[1], publishedAt: '2026-01-05T00:00:00+00:00' },
        { id: 3, title: 'Kviečiame į metinį susitikimą', href: '#', imageUrl: PLACEHOLDER_IMAGES[2], publishedAt: '2025-12-20T00:00:00+00:00' },
      ],
      meta: { total: 3, truncated: false, droppedForLocale: 0 },
    },
  }),
  'event-list': () => ({
    json_content: {},
    options: { title: 'Pirmakursių stovyklos', mode: 'upcoming', groupBy: 'tenant', limit: 12, style: 'cards' },
    resolved: {
      type: 'event-list',
      groups: [
        {
          key: '1', label: 'VU SA MIF', items: [
            { id: 1, title: 'MIF stovykla', date: '2026-08-25T09:00:00+00:00', endDate: '2026-08-27T18:00:00+00:00', location: 'Trakai', isAllDay: false, ctoUrl: null, imageUrl: PLACEHOLDER_IMAGES[0], href: '#' },
          ],
        },
        {
          key: '2', label: 'VU SA FF', items: [
            { id: 2, title: 'FF stovykla', date: '2026-08-20T09:00:00+00:00', endDate: '2026-08-22T18:00:00+00:00', location: 'Druskininkai', isAllDay: false, ctoUrl: null, imageUrl: PLACEHOLDER_IMAGES[1], href: '#' },
          ],
        },
      ],
      items: [],
      meta: { total: 2, truncated: false, style: 'cards' },
    },
  }),
  'section': () => ({
    json_content: {},
    options: { title: 'VU SA skaičiais', subtitle: 'Sužinok daugiau apie mus', background: 'muted', padding: 'lg' },
  }),
  'spacer': () => ({
    json_content: {},
    options: { size: 'lg' },
  }),
  'person-quote': () => ({
    json_content: {
      quote: tiptapDoc(['Narystė VU SA man atvėrė galimybę prisidėti prie realių pokyčių universitete.']),
      snapshot: { name: 'Vardenė Pavardenė', photoUrl: PLACEHOLDER_IMAGES[4], attribution: 'Koordinatorė, VU SA MIF' },
    },
    options: { align: 'center', showAvatar: true },
  }),
};

export function getContentSample(type: string): ContentSample | null {
  return contentSamples[type]?.() ?? null;
}

export interface ContentSampleVariant {
  label: string;
  sample: () => ContentSample;
}

/**
 * Types whose render shape changes meaningfully by `options.variant` get more than
 * one preview here, so the block picker can show what each variant actually looks
 * like instead of always the default one (`hero`'s `split` was the only one visible
 * before this — `centered`/`banner`/`panel` are different enough to be worth seeing
 * up front). Only populate this for types where the difference is structural, not
 * cosmetic — most types don't need it.
 */
export const contentSampleVariants: Partial<Record<string, ContentSampleVariant[]>> = {
  hero: [
    {
      label: 'Dviejų stulpelių',
      sample: () => ({
        json_content: {
          title: 'Prisijunk prie bendruomenės',
          description: 'Atrask naujas galimybes ir rask bendraminčių.',
          eyebrow: '',
          imageSrc: PLACEHOLDER_IMAGES[0],
          imageAlt: 'Studentai',
          objectPosition: '50% 50%',
          overlayContent: { title: '1500+', subtitle: 'aktyvių narių' },
          buttons: [{ text: 'Sužinoti daugiau', link: '#', variant: 'default', color: 'red' }],
        },
        options: { variant: 'split', textLeft: true, imageDecorations: [] },
      }),
    },
    {
      label: 'Centruota',
      sample: () => ({
        json_content: {
          title: 'Prisijunk jau šiandien',
          description: 'Atrask naujas galimybes ir rask bendraminčių.',
          eyebrow: '',
          imageSrc: '',
          imageAlt: '',
          buttons: [{ text: 'Registruotis', link: '#', variant: 'default', color: 'red' }],
        },
        options: { variant: 'centered' },
      }),
    },
    {
      label: 'Juosta',
      sample: () => ({
        json_content: {
          title: 'Prisijunk jau šiandien',
          description: '',
          eyebrow: '',
          imageSrc: '',
          imageAlt: '',
          buttons: [{ text: 'Registruotis', link: '#', variant: 'default', color: 'red' }],
        },
        options: { variant: 'banner' },
      }),
    },
    {
      label: 'Panelė',
      sample: () => ({
        json_content: {
          title: 'Pirmakursių stovyklos',
          description: 'Kiekvieną rugpjūtį padaliniai kviečia į stovyklas.',
          eyebrow: 'VU SA organizuoja',
          imageSrc: PLACEHOLDER_IMAGES[1],
          imageAlt: 'Stovyklos nuotrauka',
          buttons: [],
        },
        options: { variant: 'panel' },
      }),
    },
  ],
};

export function getContentSampleVariants(type: string): ContentSampleVariant[] | null {
  return contentSampleVariants[type] ?? null;
}
