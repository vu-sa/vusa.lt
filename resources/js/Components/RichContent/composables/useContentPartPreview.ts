/**
 * Client for the admin content-part preview endpoint
 * (`api.v1.admin.contentParts.preview`) — resolves unsaved `link-list`/`event-list`
 * blocks through the exact same resolver public rendering uses, so the editor
 * preview and the published page can't diverge into two implementations.
 *
 * Debounced, batched (one request for every resolvable block on the page rather
 * than one per block), memoised per request shape, and superseded requests are
 * aborted rather than raced.
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
    const id = tenantId();
    if (parts.length === 0 || !id) {
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
