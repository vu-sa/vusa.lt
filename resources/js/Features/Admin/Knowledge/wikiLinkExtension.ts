import { Extension } from '@tiptap/core';
import { PluginKey } from '@tiptap/pm/state';
import Suggestion from '@tiptap/suggestion';

import { createSuggestionRenderer } from '@/Components/TipTap/composables/createSuggestionRenderer';
import type { LinkTarget, LinkTargetProvider } from '@/Components/TipTap/linkTargets';
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

const toTarget = (entry: WikiIndexEntry): LinkTarget => ({ id: entry.id, title: entry.title, hint: entry.abbreviation || null, href: wikiHref(entry.url) });

/** Typing `[[` opens a picker of wiki articles; the choice becomes a link carrying the article id. */
export function createWikiLinkExtension(load: () => Promise<WikiIndexEntry[]> = loadWikiLinkTargets) {
  // The link dialog reads this to offer a "Wiki" tab and to edit existing wiki links.
  let entries: WikiIndexEntry[] = [];
  const targets: LinkTargetProvider = {
    load: async () => {
      entries = await load();
      return entries.map(toTarget);
    },
    search: query => searchWiki(entries, query).map(toTarget),
  };

  return Extension.create({
    name: 'wikiLink',
    addStorage() {
      return { targets };
    },
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
