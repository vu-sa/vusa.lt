import Heading from '@tiptap/extension-heading';
import { mergeAttributes } from '@tiptap/core';

import { latinizeId } from '@/Utils/String';

export const CustomHeading = Heading.extend({
  addAttributes() {
    return {
      ...this.parent?.(),
      id: {
        default: null,
      },
    };
  },
  parseHTML() {
    return [
      {
        tag: 'h2',
        getAttrs: (dom) => {
          return {
            id: dom.getAttribute('id'),
          };
        },
      },
      {
        tag: 'h3',
        getAttrs: (dom) => {
          return {
            id: dom.getAttribute('id'),
          };
        },
      },
    ];
  },
  renderHTML({ node, HTMLAttributes }) {
    const hasLevel = this.options.levels.includes(node.attrs.level);
    const level = hasLevel ? node.attrs.level : this.options.levels[0];

    // Extract text content from the node
    const text = extractTextFromNode(node);
    const id = latinizeId(text);

    return [
      `h${level}`,
      mergeAttributes(this.options.HTMLAttributes, HTMLAttributes, { id }),
      0,
    ];
  },
});

/**
 * Extract plain text content from a TipTap node recursively
 */
function extractTextFromNode(node: any): string {
  let text = '';

  if (node.content && Array.isArray(node.content)) {
    for (const child of node.content) {
      text += extractTextFromNode(child);
    }
  }
  else if (node.text) {
    text += node.text;
  }

  return text;
}
