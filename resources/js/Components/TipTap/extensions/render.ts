import type { AnyExtension } from '@tiptap/core';
import { StarterKit } from '@tiptap/starter-kit';
import { TableKit } from '@tiptap/extension-table';
import { Youtube } from '@tiptap/extension-youtube';

import { AccessibleImage } from '../AccessibleImage';
import { CustomHeading } from '../CustomHeading';
import { TextAlign } from '../TextAlign';
import { RCTag } from '../RCTag';
import { Video } from '../Video';

export function createRenderExtensions(): AnyExtension[] {
  return [
    StarterKit.configure({
      heading: false,
      codeBlock: false,
      link: {
        HTMLAttributes: {
          class: 'tracking-normal',
        },
      },
    }),
    CustomHeading.configure({
      levels: [2, 3, 4],
    }),
    TextAlign,
    RCTag,
    AccessibleImage.configure({
      HTMLAttributes: {
        class: 'w-full',
        loading: 'lazy',
      },
      allowBase64: true,
    }),
    TableKit.configure({
      table: {
        HTMLAttributes: {
          class: 'rc-table',
        },
      },
    }),
    Video,
    Youtube.configure({
      nocookie: true,
      HTMLAttributes: {
        class: 'rc-embed',
      },
    }),
  ];
}
