/**
 * Client for the admin content-part preview endpoint
 * (`api.v1.admin.contentParts.preview`) — resolves unsaved `link-list`/`event-list`
 * blocks through the exact same resolver public rendering uses, so the editor
 * preview and the published page can't diverge into two implementations.
 *
 * Debounced, batched (one request for every resolvable block on the page rather
 * than one per block), memoised per request shape, and superseded requests are
 * aborted rather than raced.
 *
 * `debouncedFetchPreview` resolves a *superseded* call's promise to `undefined`,
 * not `{}` — a plain `useDebounceFn` wrap (`rejectOnCancel` defaults to false), so a
 * call cancelled by a newer one within the 400ms window never reaches `fetchPreview`
 * at all. Every caller must guard `if (resolved) { ... }` before using the result
 * rather than assign it unconditionally — skipping the assignment preserves
 * already-shown data until the call that actually wins the debounce resolves, instead
 * of crashing on `undefined` or flashing an incorrect empty state. See
 * `RCFullscreenEditor.vue`/`useLiveBlockPreview.ts` for the reference guard.
 */
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';

export interface PreviewPartInput {
  key: string;
  type: string;
  json_content: unknown;
  options?: Record<string, unknown> | null;
}

export type PreviewResolvedMap = Record<string, unknown | null>;

export function useContentPartPreview(tenantId: () => number | null | undefined) {
  const page = usePage();
  const pending = ref(false);
  const error = ref<string | null>(null);

  const cache = new Map<string, PreviewResolvedMap>();
  let controller: AbortController | null = null;

  async function fetchPreview(parts: PreviewPartInput[]): Promise<PreviewResolvedMap> {
    if (parts.length === 0) {
      return {};
    }

    const id = tenantId();
    if (!id) {
      // A missing tenant id here means whichever page embeds `RichContentFormElement`/
      // `RichContentEditor` forgot to pass `:tenant-id` — every server-resolved block
      // (link-list, event-list, news, calendar) then silently gets no preview data at
      // all, every time, indistinguishable from "the server resolved to genuinely
      // nothing" unless flagged. Cost real debugging time once already (EditHomePage.vue
      // was missing it) — surfaced loudly so the next missing caller is obvious.
      console.warn('[useContentPartPreview] fetchPreview called with no tenantId — the caller is missing `:tenant-id`. Every server-resolved block will show no preview data until this is fixed.');

      return {};
    }

    const cacheKey = JSON.stringify({ id, parts });
    const cached = cache.get(cacheKey);
    if (cached) {
      return cached;
    }

    controller?.abort();
    const ownController = new AbortController();
    controller = ownController;
    pending.value = true;
    error.value = null;

    try {
      const csrf = (page.props.csrf_token as string | undefined) ?? '';

      const response = await fetch(route('api.v1.admin.contentParts.preview'), {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf,
        },
        credentials: 'same-origin',
        signal: ownController.signal,
        body: JSON.stringify({ tenant_id: id, locale: page.props.app?.locale, parts }),
      });

      const json = await response.json().catch(() => null);

      if (!response.ok || !json?.success) {
        throw new Error(json?.message ?? 'Content part preview request failed');
      }

      const resolved = (json.data?.resolved ?? {}) as PreviewResolvedMap;
      cache.set(cacheKey, resolved);

      return resolved;
    }
    catch (e) {
      // Aborted by a newer call — not a real error, the newer call's result wins.
      if ((e as Error).name === 'AbortError') {
        return {};
      }
      error.value = (e as Error).message;

      return {};
    }
    finally {
      if (controller === ownController) {
        pending.value = false;
      }
    }
  }

  const debouncedFetchPreview = useDebounceFn(fetchPreview, 400);

  return { pending, error, fetchPreview, debouncedFetchPreview };
}
