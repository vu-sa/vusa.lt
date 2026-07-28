/**
 * Normalize inbound editor content for `@tiptap/core`'s `content` option.
 *
 * A fresh rich-content text/card block stores its `json_content` as `{}` (see
 * `contentTypeRegistry.defaultContent`). Tiptap treats any non-nullish object as a
 * JSON doc and reads `{}.type` as `undefined`, which warns
 * "Unknown node type: undefined" on every newly-created block. Nullish values and
 * the empty string are already safe; only the empty-object case needs coercing.
 *
 * Mirrors the emptiness check in `RichContentTiptapHTML.vue`.
 */
export function normalizeContent(
  value: string | Record<string, unknown> | null,
): string | Record<string, unknown> {
  if (!value || (typeof value === 'object' && Object.keys(value).length === 0)) {
    return '';
  }

  return value;
}
