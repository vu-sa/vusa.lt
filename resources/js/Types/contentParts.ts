// Type definitions for content parts used in the rich content editor
// These types correspond to the ContentPartEnum in enums.ts

// Implemented

// Content Grid type - updated with the simplified structure
export interface ContentGrid {
  json_content: {
    columns: {
      width: string; // e.g. "col-span-4", "col-span-6", etc.
      content: {
        type: string; // Can be "tiptap", "image", etc.
        value: any; // Content depends on the type
      };
    }[];
  }[];
  options: {
    gap?: 'gap-2' | 'gap-4' | 'gap-6' | 'gap-8';
    mobileStacking?: boolean;
    equalHeight?: boolean;
  };
}

export interface ImageGrid {
  json_content: {
    colspan: 'col-span-2' | 'col-span-3' | 'col-span-4' | 'col-span-full';
    image: string;
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
  options: null;
}

export interface ShadcnCard {
  json_content: Tiptap['json_content'];
  options: {
    color?: 'zinc' | 'red' | 'yellow';
    variant?: 'outline' | 'soft';
    title?: string;
    isTitleColored?: boolean;
    showIcon?: boolean;
  };
}

export interface Hero {
  json_content: {
    title: string;
    subtitle: string;
    backgroundMedia: string;
    rightMedia?: string;
    buttonText?: string;
    buttonLink?: string;
  };
  options: {
    backgroundBlur?: boolean;
    is_active?: boolean;
    buttonColor: 'red' | 'yellow' | 'zinc' | 'white';
  };
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
  }[];
  options: {
    color?: 'zinc' | 'red' | 'yellow';
    title: string;
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
