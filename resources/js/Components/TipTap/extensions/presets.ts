/**
 * TipTap Extension Presets
 *
 * Centralized extension configurations for different editor use cases.
 * Presets: minimal, compact, full
 */
import type { AnyExtension, Editor } from '@tiptap/core';
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
import { TextAlign } from '../TextAlign';
import { RCTag } from '../RCTag';
import { Video } from '../Video';
import { isValidTiptapLink } from '../linkUrl';

export type EditorPreset = 'minimal' | 'compact' | 'full' | 'marks';

export interface PresetOptions {
  placeholder?: string;
  maxCharacters?: number | null;
  disableTables?: boolean;
  disableLinks?: boolean;
  onFileDrop?: (editor: Editor, files: File[], pos?: number) => void;
  onFilePaste?: (editor: Editor, files: File[]) => void;
}

function getStarterKitLinkConfig(disableLinks: boolean) {
  if (disableLinks) {
    return false;
  }

  return {
    openOnClick: false,
    HTMLAttributes: {
      // `text-brand`, not the old `text-vusa-red`: it resolves per theme (red on
      // light, amber on dark) via the token system. Rich-content surfaces let
      // `.rc-prose a` own the final colour anyway — this is for editors and
      // stored HTML outside a prose wrapper (comments).
      class: 'text-brand underline font-medium',
    },
  };
}

/**
 * Shared StarterKit configuration used across all presets
 */
function createStarterKit(enableHeading: boolean = false, disableLinks: boolean = false) {
  return StarterKit.configure({
    heading: enableHeading ? undefined : false,
    codeBlock: false,
    link: getStarterKitLinkConfig(disableLinks),
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
  'audio/mpeg', 'video/mp4', 'video/x-msvideo', 'video/quicktime', 'video/webm',
];

/**
 * Minimal preset - for comments and simple text input
 * Features: bold, italic, underline, strike, lists, links
 */
export function createMinimalExtensions(options: PresetOptions = {}): AnyExtension[] {
  const extensions: AnyExtension[] = [
    createStarterKit(false, options.disableLinks),
  ];

  if (options.placeholder) {
    extensions.push(
      Placeholder.configure({
        placeholder: options.placeholder,
      }),
    );
  }

  return extensions;
}

export function createCommentExtensions(options: PresetOptions = {}): AnyExtension[] {
  const extensions: AnyExtension[] = [
    StarterKit.configure({
      heading: false,
      codeBlock: false,
      code: false,
      horizontalRule: false,
      blockquote: false,
      link: {
        ...getStarterKitLinkConfig(false),
        isAllowedUri: isValidTiptapLink,
      },
    }),
  ];
  if (options.placeholder) extensions.push(Placeholder.configure({ placeholder: options.placeholder }));
  return extensions;
}

/** Inline-only fields disable block input rules as well as their toolbar controls. */
export function createMarksExtensions(options: PresetOptions = {}): AnyExtension[] {
  const extensions: AnyExtension[] = [
    StarterKit.configure({
      heading: false,
      codeBlock: false,
      code: false,
      strike: false,
      link: getStarterKitLinkConfig(!!options.disableLinks),
      blockquote: false,
      bulletList: false,
      orderedList: false,
      listItem: false,
      horizontalRule: false,
    }),
  ];

  if (options.placeholder) {
    extensions.push(
      Placeholder.configure({
        placeholder: options.placeholder,
      }),
    );
  }

  if (options.maxCharacters != null) extensions.push(CharacterCount.configure({ limit: options.maxCharacters, autoTrim: false }));

  return extensions;
}

/**
 * Compact preset - for grid/content blocks with basic media
 * Features: minimal + headings (h2), images, youtube
 */
export function createCompactExtensions(options: PresetOptions = {}): AnyExtension[] {
  const extensions: AnyExtension[] = [
    createStarterKit(false, options.disableLinks),
    CustomHeading.configure({
      levels: [2],
    }),
    // Compact fields may contain full-schema tags/alignment; preserve them on edits.
    TextAlign,
    RCTag,
    BubbleMenu,
    Image.configure({
      HTMLAttributes: {
        class: 'w-full',
      },
    }),
    Youtube.configure({
      nocookie: true,
      HTMLAttributes: {
        class: 'rc-embed',
      },
    }),
  ];

  if (options.placeholder) {
    extensions.push(
      Placeholder.configure({
        placeholder: options.placeholder,
      }),
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
    createStarterKit(false, options.disableLinks),
    CustomHeading.configure({
      levels: [2, 3, 4],
    }),
    TextAlign,
    RCTag,
    Subscript,
    Superscript,
    AccessibleImage.configure({
      HTMLAttributes: {
        class: 'max-w-full h-auto',
        loading: 'lazy',
      },
      allowBase64: true,
    }),
    Video,
    // Only the `rc-embed` hook, as in `createRenderExtensions` and App\Tiptap\TiptapEditor;
    // the frame itself lives in typography.css so editor and published page match.
    Youtube.configure({
      nocookie: true,
      HTMLAttributes: {
        class: 'rc-embed',
      },
    }),
  ];

  // Character count
  if (options.maxCharacters !== undefined) {
    extensions.push(
      CharacterCount.configure({
        limit: options.maxCharacters,
        autoTrim: false,
      }),
    );
  }

  // Placeholder
  if (options.placeholder) {
    extensions.push(
      Placeholder.configure({
        placeholder: options.placeholder,
      }),
    );
  }

  // Tables (optional). Only the `rc-table` hook is stored, as in `createRenderExtensions`
  // and App\Tiptap\TiptapEditor; the chrome itself lives in typography.css.
  if (!options.disableTables) {
    extensions.push(
      TableKit.configure({
        table: {
          resizable: true,
          HTMLAttributes: {
            class: 'rc-table',
          },
        },
      }),
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
      }),
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
    case 'marks':
      return createMarksExtensions(options);
    case 'compact':
      return createCompactExtensions(options);
    case 'full':
      return createFullExtensions(options);
    default:
      return createMinimalExtensions(options);
  }
}

export { createRenderExtensions } from './render';
