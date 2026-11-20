import type { WikiIndexEntry } from '@/Types/learning';

let cached: Promise<WikiIndexEntry[]> | null = null;

/** Every active article, loaded once per page visit for the `[[` picker. */
export function loadWikiLinkTargets(): Promise<WikiIndexEntry[]> {
  cached ??= fetch(route('knowledgeItems.linkOptions'), { headers: { Accept: 'application/json' } })
    .then(response => response.ok ? response.json() as Promise<WikiIndexEntry[]> : [])
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
