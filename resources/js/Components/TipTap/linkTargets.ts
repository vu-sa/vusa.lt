import type { Editor } from '@tiptap/core';

/** An internal page a link can point at, carried on the link mark as `knowledgeId`. */
export interface LinkTarget {
  id: string;
  title: string;
  hint?: string | null;
  href: string;
}

/**
 * Supplied through an extension's storage (`wikiLink.targets`), so the link dialog can
 * offer internal pages without the editor importing a feature.
 */
export interface LinkTargetProvider {
  load: () => Promise<LinkTarget[]>;
  /** Searches what `load()` returned. */
  search: (query: string) => LinkTarget[];
}

export function linkTargetProvider(editor: Editor | null | undefined): LinkTargetProvider | null {
  const storage = (editor?.storage as unknown as Record<string, { targets?: LinkTargetProvider } | undefined> | undefined)?.wikiLink;
  return storage?.targets ?? null;
}
