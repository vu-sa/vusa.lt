import { useDebouncedQuery } from '@/Composables/useDebouncedQuery';

export interface PermalinkPreview {
  permalink: string;
  url: string;
}

const ROUTES = {
  news: 'api.v1.admin.news.permalinkPreview',
  page: 'api.v1.admin.pages.permalinkPreview',
  institution: 'api.v1.admin.institutions.aliasPreview',
} as const;

/**
 * Previews the permalink/URL a form would end up with for the title (and lang) as currently
 * typed — nothing is written server-side. Mirrors useDuplicateDutyCheck.ts: same debounce, same
 * "gate on url so clearing the field clears the last result" trick.
 */
export function usePermalinkPreview(
  type: keyof typeof ROUTES,
  title: () => string,
  lang: () => string,
  extraParams: () => Record<string, string | number | null | undefined> = () => ({}),
) {
  const { data: preview, isChecking, check } = useDebouncedQuery<PermalinkPreview | null>({
    url: () => {
      const currentTitle = (title() ?? '').trim();

      if (currentTitle.length < 2) {
        return null;
      }

      const params = new URLSearchParams({ title: currentTitle, lang: lang() });

      for (const [key, value] of Object.entries(extraParams())) {
        if (value !== null && value !== undefined && value !== '') {
          params.set(key, String(value));
        }
      }

      return `${route(ROUTES[type])}?${params.toString()}`;
    },
    watchSources: [() => title(), () => lang(), () => JSON.stringify(extraParams())],
    debounceMs: 500,
    initialValue: null,
  });

  return { preview, isChecking, check };
}
