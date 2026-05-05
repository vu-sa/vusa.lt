import { Image } from '@tiptap/extension-image';
import type { CommandProps } from '@tiptap/core';

interface SetImageWithAltOptions {
  src: string;
  alt?: string;
  title?: string;
  width?: string | number | null;
  height?: string | number | null;
  align?: 'left' | 'center' | 'right';
}

interface UpdateSizeOptions {
  width?: string | number | null;
  height?: string | number | null;
}

/**
 * Simple Image extension with alt text, alignment, and resizing commands
 */
export const AccessibleImage = Image.extend({
  name: 'image',

  addAttributes() {
    return {
      src: {
        default: null,
      },
      alt: {
        default: '',
      },
      title: {
        default: '',
      },
      width: {
        default: null,
      },
      height: {
        default: null,
      },
      align: {
        default: 'center',
        parseHTML: (element: HTMLElement) => element.getAttribute('data-align') || 'center',
        renderHTML: (attributes: { align?: string }) => ({ 'data-align': attributes.align || 'center' }),
      },
    };
  },

  addCommands() {
    return {
      setImageWithAlt: (options: SetImageWithAltOptions) => ({ commands }: CommandProps) =>
        commands.insertContent({
          type: this.name,
          attrs: {
            src: options.src,
            alt: options.alt || '',
            title: options.title || '',
            width: options.width || null,
            height: options.height || null,
            align: options.align || 'center',
          },
        }),
      updateImageAlt: (alt: string) => ({ commands }: CommandProps) =>
        commands.updateAttributes(this.name, { alt }),
      updateImageTitle: (title: string) => ({ commands }: CommandProps) =>
        commands.updateAttributes(this.name, { title }),
      updateImageSize: (options: UpdateSizeOptions) => ({ commands }: CommandProps) =>
        commands.updateAttributes(this.name, { width: options.width || null, height: options.height || null }),
      updateImageAlignment: (align: string) => ({ commands }: CommandProps) =>
        commands.updateAttributes(this.name, { align }),
    };
  },

  renderHTML({ HTMLAttributes }: { HTMLAttributes: Record<string, unknown> }) {
    const align = (HTMLAttributes['data-align'] as string) || 'center';
    const alignmentClasses: Record<string, string> = {
      left: 'float-left mr-4 mb-2',
      right: 'float-right ml-4 mb-2',
      center: 'mx-auto block',
    };

    return ['img', {
      ...HTMLAttributes,
      class: `tiptap-image max-w-full h-auto rounded-md ${alignmentClasses[align] || alignmentClasses.center}`,
    }];
  },
});

export default AccessibleImage;
