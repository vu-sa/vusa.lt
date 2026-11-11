import { Extension } from '@tiptap/core';
import { PluginKey } from '@tiptap/pm/state';
import Suggestion from '@tiptap/suggestion';

import { createSuggestionRenderer } from '@/Components/TipTap/composables/createSuggestionRenderer';
import type { WikiIndexEntry } from '@/Types/learning';

import WikiLinkList from './WikiLinkList.vue';
import { loadWikiLinkTargets } from './useWikiLinkTargets';
import { searchWiki } from './wikiSearch';

export const wikiLinkPluginKey = new PluginKey('wikiLink');

/** Stored as an app-relative href, so links survive a domain change. */
export const wikiHref = (url: string): string => {
  try {
    return new URL(url, window.location.origin).pathname;
  }
  catch {
    return url;
  }
};

/** Typing `[[` opens a picker of wiki articles; the choice becomes a link carrying the article id. */
export function createWikiLinkExtension(load: () => Promise<WikiIndexEntry[]> = loadWikiLinkTargets) {
  return Extension.create({
    name: 'wikiLink',
    addProseMirrorPlugins() {
      return [Suggestion<WikiIndexEntry, WikiIndexEntry>({
        editor: this.editor,
        char: '[[',
        allowSpaces: true,
        startOfLine: false,
        pluginKey: wikiLinkPluginKey,
        items: async ({ query }) => searchWiki(await load(), query.replace(/\]+$/, '')).slice(0, 8),
        command: ({ editor, range, props }) => {
          editor.chain().focus().insertContentAt(range, [
            { type: 'text', text: props.title, marks: [{ type: 'link', attrs: { href: wikiHref(props.url), knowledgeId: props.id } }] },
            { type: 'text', text: ' ' },
          ]).run();
        },
        render: createSuggestionRenderer<WikiIndexEntry, WikiIndexEntry>(WikiLinkList, wikiLinkPluginKey),
      })];
    },
  });
}
