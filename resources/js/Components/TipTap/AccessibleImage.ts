import { Image } from '@tiptap/extension-image';
import { Plugin } from '@tiptap/pm/state';

/**
 * Enhanced Image extension with proper alt text support and accessibility features
 */
export const AccessibleImage = Image.extend({
  name: 'accessibleImage',

  addAttributes() {
    return {
      ...this.parent?.(),
      alt: {
        default: '',
        parseHTML: (element) => element.getAttribute('alt') || '',
        renderHTML: (attributes) => {
          if (!attributes.alt) {
            return {};
          }
          return { alt: attributes.alt };
        },
      },
      title: {
        default: '',
        parseHTML: (element) => element.getAttribute('title') || '',
        renderHTML: (attributes) => {
          if (!attributes.title) {
            return {};
          }
          return { title: attributes.title };
        },
      },
      loading: {
        default: 'lazy',
        parseHTML: (element) => element.getAttribute('loading') || 'lazy',
        renderHTML: (attributes) => {
          return { loading: attributes.loading || 'lazy' };
        },
      },
    };
  },

  addCommands() {
    return {
      ...this.parent?.(),
      setImageWithAlt: (options) => ({ commands }) => {
        return commands.insertContent({
          type: this.name,
          attrs: {
            src: options.src,
            alt: options.alt || '',
            title: options.title || '',
            loading: 'lazy',
          },
        });
      },
      updateImageAlt: (alt) => ({ commands }) => {
        return commands.updateAttributes(this.name, { alt });
      },
      updateImageTitle: (title) => ({ commands }) => {
        return commands.updateAttributes(this.name, { title });
      },
    };
  },

  addProseMirrorPlugins() {
    return [
      new Plugin({
        props: {
          handleDOMEvents: {
            // Add keyboard accessibility for image selection
            keydown: (view, event) => {
              if (event.key === 'Enter' || event.key === ' ') {
                const { state } = view;
                const { selection } = state;
                const node = state.doc.nodeAt(selection.from);
                
                if (node && node.type.name === this.name) {
                  // Could add image editing dialog here
                  event.preventDefault();
                  return true;
                }
              }
              return false;
            },
          },
        },
      }),
    ];
  },

  renderHTML({ HTMLAttributes }) {
    const attrs = { ...HTMLAttributes };
    
    // Ensure alt attribute is always present (even if empty for decorative images)
    if (!attrs.alt && attrs.alt !== '') {
      attrs.alt = '';
    }
    
    // Add role for better screen reader support
    if (!attrs.role) {
      attrs.role = attrs.alt ? 'img' : 'presentation';
    }

    return ['img', attrs];
  },
});

export default AccessibleImage;
