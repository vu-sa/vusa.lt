import type { BlockWidth, ContentPart } from '../Types';

/**
 * Writes `options.width`, preserving every other option — the one write-back
 * `RCBlockCard`'s header chip, `RCSideBySideDialog`'s preview pane, and the full-screen
 * editor's `RCFullscreenBlock`/`HeroBlockToolbar` all shared as near-identical
 * copy-pasted inline functions before this.
 */
export function withWidth(content: ContentPart, width: BlockWidth): ContentPart {
  return { ...content, options: { ...(content.options ?? {}), width } };
}
