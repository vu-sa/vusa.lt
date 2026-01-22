/**
 * TipTap Extension Presets
 * 
 * Centralized extension configurations for different editor use cases.
 * Presets: minimal, compact, full
 */
import type { AnyExtension } from '@tiptap/core';
import { StarterKit } from '@tiptap/starter-kit';
import { CharacterCount, Placeholder } from '@tiptap/extensions';
import { FileHandler } from '@tiptap/extension-file-handler';
import { TableKit } from '@tiptap/extension-table';
import { Youtube } from '@tiptap/extension-youtube';
import { Subscript } from '@tiptap/extension-subscript';
import { Superscript } from '@tiptap/extension-superscript';
import { Image } from '@tiptap/extension-image';
import { BubbleMenu } from '@tiptap/vue-3/menus';

import { AccessibleImage } from '../AccessibleImage';
import { CustomHeading } from '../CustomHeading';
import { Video } from '../Video';

export type EditorPreset = 'minimal' | 'compact' | 'full';

export interface PresetOptions {
  placeholder?: string;
  maxCharacters?: number | null;
  disableTables?: boolean;
  onFileDrop?: (editor: any, files: File[], pos?: number) => void;
  onFilePaste?: (editor: any, files: File[]) => void;
}

/**
 * Shared StarterKit configuration used across all presets
 */
function createStarterKit(enableHeading: boolean = false) {
  return StarterKit.configure({
    heading: enableHeading ? undefined : false,
    codeBlock: false,
    link: {
      openOnClick: false,
      HTMLAttributes: {
        class: 'text-vusa-red underline font-medium',
      },
    },
  });
}

/**
 * Allowed MIME types for file uploads
 */
const ALLOWED_MIME_TYPES = [
  // Images
  'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
  // Documents
  'application/pdf',
  'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
  // Text files
  'text/plain', 'text/csv',
  // Archives
  'application/zip', 'application/x-rar-compressed',
  // Web files
  'text/html', 'text/css', 'text/javascript', 'application/javascript', 'application/json', 'text/xml', 'application/xml',
  // Audio/Video
  'audio/mpeg', 'video/mp4', 'video/x-msvideo', 'video/quicktime', 'video/webm'
];

/**
 * Minimal preset - for comments and simple text input
 * Features: bold, italic, underline, strike, lists, links
 */
export function createMinimalExtensions(options: PresetOptions = {}): AnyExtension[] {
  const extensions: AnyExtension[] = [
    createStarterKit(false),
  ];

  if (options.placeholder) {
    extensions.push(
      Placeholder.configure({
        placeholder: options.placeholder,
      })
    );
  }

  return extensions;
}

/**
 * Compact preset - for grid/content blocks with basic media
 * Features: minimal + headings (h2), images, youtube
 */
export function createCompactExtensions(options: PresetOptions = {}): AnyExtension[] {
  const extensions: AnyExtension[] = [
    createStarterKit(false),
    CustomHeading.configure({
      levels: [2],
    }),
    BubbleMenu,
    Image.configure({
      HTMLAttributes: {
        class: 'w-full rounded-md',
      },
    }),
    Youtube.configure({
      HTMLAttributes: {
        class: 'aspect-video w-auto my-2',
      },
    }),
  ];

  if (options.placeholder) {
    extensions.push(
      Placeholder.configure({
        placeholder: options.placeholder,
      })
    );
  }

  return extensions;
}

/**
 * Full preset - for main content editing
 * Features: compact + tables, videos, file uploads, accessible images, subscript/superscript
 */
export function createFullExtensions(options: PresetOptions = {}): AnyExtension[] {
  const extensions: AnyExtension[] = [
    createStarterKit(false),
    CustomHeading.configure({
      levels: [2, 3],
    }),
    Subscript,
    Superscript,
    AccessibleImage.configure({
      HTMLAttributes: {
        class: 'max-w-full h-auto rounded-md',
        loading: 'lazy',
      },
      allowBase64: true,
    }),
    Video,
    Youtube.configure({
      HTMLAttributes: {
        class: 'aspect-video h-36 w-auto my-2',
      },
    }),
  ];

  // Character count
  if (options.maxCharacters !== undefined) {
    extensions.push(
      CharacterCount.configure({
        limit: options.maxCharacters,
      })
    );
  }

  // Placeholder
  if (options.placeholder) {
    extensions.push(
      Placeholder.configure({
        placeholder: options.placeholder,
      })
    );
  }

  // Tables (optional)
  if (!options.disableTables) {
    extensions.push(
      TableKit.configure({
        table: { resizable: true },
      })
    );
  }

  // File handling (if handlers provided)
  if (options.onFileDrop || options.onFilePaste) {
    extensions.push(
      FileHandler.configure({
        allowedMimeTypes: ALLOWED_MIME_TYPES,
        onDrop: (currentEditor, files, pos) => {
          options.onFileDrop?.(currentEditor, files, pos);
        },
        onPaste: (currentEditor, files) => {
          options.onFilePaste?.(currentEditor, files);
        },
      })
    );
  }

  return extensions;
}

/**
 * Get extensions for a preset
 */
export function getExtensionsForPreset(preset: EditorPreset, options: PresetOptions = {}): AnyExtension[] {
  switch (preset) {
    case 'minimal':
      return createMinimalExtensions(options);
    case 'compact':
      return createCompactExtensions(options);
    case 'full':
      return createFullExtensions(options);
    default:
      return createMinimalExtensions(options);
  }
}

/**
 * Extensions for rendering HTML (display-only)
 * Used by RichContentTiptapHTML
 */
export function createRenderExtensions(): AnyExtension[] {
  return [
    StarterKit.configure({
      heading: false,
      codeBlock: false,
      link: {
        HTMLAttributes: {
          class: 'text-blue-500 underline tracking-normal',
        },
      },
    }),
    CustomHeading.configure({
      levels: [2, 3],
    }),
    AccessibleImage.configure({
      HTMLAttributes: {
        class: 'w-full rounded-md',
        loading: 'lazy',
      },
      allowBase64: true,
    }),
    TableKit.configure({
      table: {
        HTMLAttributes: {
          class: 'border-collapse table-auto w-full tracking-normal',
        },
      },
      tableCell: {
        HTMLAttributes: {
          class: 'border border-zinc-400 dark:border-zinc-500 px-4 py-1 text-left tracking-normal [&[align=center]]:text-center [&[align=right]]:text-right',
        },
      },
      tableHeader: {
        HTMLAttributes: {
          class: 'border border-zinc-400 dark:border-zinc-500 px-4 py-1 text-left font-bold tracking-normal [&[align=center]]:text-center [&[align=right]]:text-right',
        },
      },
      tableRow: {
        HTMLAttributes: {
          class: 'm-0 border-t p-0 even:bg-zinc-100 dark:even:bg-zinc-800/20',
        },
      },
    }),
    Video,
    Youtube.configure({
      HTMLAttributes: {
        class: 'aspect-video h-auto w-full rounded-xl shadow-lg',
      },
    }),
  ];
}
