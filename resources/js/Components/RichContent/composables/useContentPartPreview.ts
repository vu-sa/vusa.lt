import { inject, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';

import { apiRequest } from '@/Composables/apiRequest';

import { CONTENT_EDITOR_CONTEXT } from '../contentEditorContext';

export interface PreviewPartInput {
  key: string;
  type: string;
  json_content: unknown;
  options?: Record<string, unknown> | null;
}

export type PreviewResolvedMap = Record<string, unknown | null>;

export function useContentPartPreview(tenantId: () => number | null | undefined, explicitContext?: () => { kind: string; record_id?: number; locale?: string } | undefined) {
  const editorContext = inject(CONTENT_EDITOR_CONTEXT, null);
  const getContext = () => explicitContext?.() ?? (editorContext ? { kind: editorContext.kind, record_id: editorContext.form.id, locale: editorContext.form.lang } : undefined);
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

    const cacheKey = JSON.stringify({ id, parts, context: getContext() });
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
      const result = await apiRequest<{ resolved?: PreviewResolvedMap }>(route('api.v1.admin.contentParts.preview'), {
        method: 'POST',
        signal: ownController.signal,
        body: { tenant_id: id, locale: getContext()?.locale ?? page.props.app?.locale, ...getContext(), parts },
      });
      if (!result.ok || result.body === null) {
        throw new Error(result.message ?? 'Content part preview request failed');
      }

      const resolved = result.data?.resolved ?? {};
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

  // A superseded call resolves to undefined so the caller can keep the current preview.
  const debouncedFetchPreview = useDebounceFn(fetchPreview, 400);

  return { pending, error, fetchPreview, debouncedFetchPreview };
}
