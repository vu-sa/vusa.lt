import Heading from '@tiptap/extension-heading';

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
});
