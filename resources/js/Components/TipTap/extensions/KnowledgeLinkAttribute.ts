import { Extension } from '@tiptap/core';

/**
 * Keeps `data-knowledge-id` on links, so a wiki link survives any editor that opens the HTML,
 * even one that cannot insert new ones.
 */
export const KnowledgeLinkAttribute = Extension.create({
  name: 'knowledgeLinkAttribute',
  addGlobalAttributes() {
    return [{
      types: ['link'],
      attributes: {
        knowledgeId: {
          default: null,
          parseHTML: element => element.getAttribute('data-knowledge-id'),
          renderHTML: attributes => attributes.knowledgeId ? { 'data-knowledge-id': attributes.knowledgeId } : {},
        },
      },
    }];
  },
});
