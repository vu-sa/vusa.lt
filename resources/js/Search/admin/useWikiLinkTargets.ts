import { apiRequest } from '@/Composables/apiRequest';
import type { WikiIndexEntry } from '@/Types/learning';

let cached: Promise<WikiIndexEntry[]> | null = null;

/** Every active article, loaded once per page visit for the `[[` picker. */
export function loadWikiLinkTargets(): Promise<WikiIndexEntry[]> {
  cached ??= apiRequest<WikiIndexEntry[]>(route('knowledgeItems.linkOptions'))
    .then(({ ok, data }) => ok && Array.isArray(data) ? data : [])
    .catch(() => [])
    .then((targets) => {
      if (!targets.length) cached = null;
      return targets;
    });

  return cached;
}

export function forgetWikiLinkTargets(): void {
  cached = null;
}
