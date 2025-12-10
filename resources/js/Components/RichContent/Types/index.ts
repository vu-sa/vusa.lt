import { type Component, type AsyncComponentLoader } from 'vue';
import TextCaseUppercase20Filled from '~icons/fluent/text-case-uppercase20-filled';

// Re-export all type definitions
export * from './types';
import AppsListDetail24Regular from '~icons/fluent/apps-list-detail24-regular';
import CalendarDay24Regular from '~icons/fluent/calendar-day24-regular';
import ImageMultiple24Regular from '~icons/fluent/image-multiple24-regular';
import SpotifyIcon from '~icons/mdi/spotify';
import NewsIcon from '~icons/fluent/news24-regular';
import CalendarIcon from '~icons/fluent/calendar-ltr24-regular';
import FlowIcon from '~icons/fluent/flow24-regular';
import NumberIcon from '~icons/fluent/number-symbol24-regular';
import HeroIcon from '~icons/fluent/slide-play24-regular';
import GridIcon from '~icons/fluent/table-simple24-regular';
import SocialIcon from '~icons/fluent/share-24-regular';

export interface ContentType {
  value: string;
  label: string;
  icon: Component;
  description?: string;
  defaultContent: () => any;
  defaultOptions?: () => Record<string, any>;
}

export const contentTypeRegistry: Record<string, ContentType> = {
  "tiptap": {
    value: "tiptap",
    label: "Tekstas",
    icon: TextCaseUppercase20Filled,
    description: "Redaguojamas teksto blokas su formatavimo galimybėmis",
    defaultContent: () => ({}),
  },
  "shadcn-accordion": {
    value: "shadcn-accordion",
    label: "Išsiskleidžiantis sąrašas",
    icon: AppsListDetail24Regular,
    description: "Išsiskleidžiantis turinio blokas, kur rodomas tik pavadinimas",
    defaultContent: () => ([]),
  },
  "shadcn-card": {
    value: "shadcn-card",
    label: "Kortelė",
    icon: CalendarDay24Regular,
    description: "Specialiai apipavidalintas tekstas su antrašte",
    defaultContent: () => ({}),
    defaultOptions: () => ({
      variant: 'outline',
      color: 'zinc',
      title: '',
      isTitleColored: false,
      showIcon: false
    }),
  },
  "image-grid": {
    value: "image-grid",
    label: "Nuotraukų tinklelis",
    icon: ImageMultiple24Regular,
    description: "Kelių nuotraukų išdėstymas tinkleliu",
    defaultContent: () => ([]),
  },
  "hero": {
    value: "hero",
    label: "Hero",
    icon: HeroIcon,
    description: "Didelis turinio blokas su paveiksliuku",
    defaultContent: () => ({
      title: '',
      subtitle: '',
      backgroundMedia: null,
      rightMedia: null,
      buttonText: '',
      buttonLink: '',
    }),
    defaultOptions: () => ({ is_active: true, backgroundBlur: false, buttonColor: 'red' }),
  },
  "news": {
    value: "news",
    label: "Naujienos",
    icon: NewsIcon,
    description: "Naujienų blokas",
    defaultContent: () => ({ title: "" }),
  },
  "calendar": {
    value: "calendar",
    label: "Kalendorius",
    icon: CalendarIcon,
    description: "Kalendoriaus blokas",
    defaultContent: () => ({ title: "" }),
  },
  "spotify-embed": {
    value: "spotify-embed",
    label: "Spotify",
    icon: SpotifyIcon,
    description: "Spotify grojaraščio įterpimas",
    defaultContent: () => ({ url: "" }),
  },
  "social-embed": {
    value: "social-embed",
    label: "Facebook / Instagram",
    icon: SocialIcon,
    description: "Facebook arba Instagram įrašo įterpimas",
    defaultContent: () => ({ url: "", platform: null, postId: "" }),
    defaultOptions: () => ({ showCaption: true }),
  },
  "flow-graph": {
    value: "flow-graph",
    label: "Flow Graph",
    icon: FlowIcon,
    description: "Proceso eigos schema",
    defaultContent: () => ({ preset: "VusaStructure" }),
  },
  "number-stat-section": {
    value: "number-stat-section",
    label: "Skaitinės statistikos",
    icon: NumberIcon,
    description: "Skaičių statistikos sekcija",
    defaultContent: () => ([]),
    defaultOptions: () => ({ title: "", color: "zinc" }),
  },
  "content-grid": {
    value: "content-grid",
    label: "Tinklelis",
    icon: GridIcon,
    description: "Lankstus turinys stulpeliais ir eilutėmis",
    defaultContent: () => ([
      {
        columns: [
          {
            width: "col-span-6",
            content: {
              type: "tiptap",
              value: {}
            }
          },
          {
            width: "col-span-6",
            content: {
              type: "tiptap",
              value: {}
            }
          }
        ]
      }
    ]),
    defaultOptions: () => ({ 
      gap: "gap-4",
      mobileStacking: true,
      equalHeight: false
    }),
  },
};

export const getAllContentTypes = (): ContentType[] => {
  return Object.values(contentTypeRegistry);
};

export const getContentType = (type: string): ContentType => {
  return contentTypeRegistry[type] ?? contentTypeRegistry["tiptap"]!;
};

export const createContentItem = (type: string) => {
  const contentType = getContentType(type);
  return {
    type,
    json_content: contentType.defaultContent(),
    options: contentType.defaultOptions ? contentType.defaultOptions() : { is_active: true },
    key: Math.random().toString(36).substring(7),
    expanded: true
  };
};

