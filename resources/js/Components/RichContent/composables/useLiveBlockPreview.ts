/**
 * Keeps a single block's server-resolved preview data (link-list, event-list, …) in
 * sync with live edits — one debounced request per keystroke-settle, fetching
 * nothing for types that don't declare `serverResolved` in the registry. Shared by
 * `ContentEditorFactory`'s inline preview and `RCSideBySideDialog`'s live pane so the
 * fetch/debounce logic exists in exactly one place.
 */
import { ref, watch, type Ref } from 'vue';

import { getContentType, type ContentPart } from '../Types';
import { useContentPartPreview } from './useContentPartPreview';

export function useLiveBlockPreview(
  content: Ref<ContentPart | undefined>,
  tenantId: () => number | null | undefined,
  /** Skip fetching while the preview isn't actually visible (e.g. the block's inline
   *  preview toggle is off) — defaults to always-on for callers that show the preview
   *  unconditionally (e.g. a side-by-side editor/preview dialog). */
  isActive: () => boolean = () => true,
) {
  const { debouncedFetchPreview } = useContentPartPreview(tenantId);
  const previewResolved = ref<unknown>(undefined);

  watch(
    [isActive, () => content.value?.type, () => content.value?.json_content, () => content.value?.options],
    async ([active, type]) => {
      if (!active || !type || !getContentType(type).serverResolved) {
        return;
      }
      const resolved = await debouncedFetchPreview([{
        key: 'preview',
        type,
        json_content: content.value?.json_content,
        options: content.value?.options ?? null,
      }]);
      // A *superseded* call (a newer edit re-fired this watcher before the debounce
      // settled) resolves to `undefined`, not the newer call's result — vueuse's
      // `useDebounceFn` default (`rejectOnCancel: false`). Skip the assignment rather
      // than crashing on `undefined.preview` / clobbering already-shown data; the call
      // that actually wins the debounce still resolves normally and updates this ref.
      if (resolved) {
        previewResolved.value = resolved.preview;
      }
    },
    { deep: true, immediate: true },
  );

  return { previewResolved };
}
