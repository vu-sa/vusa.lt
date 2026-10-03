import { useDebouncedQuery } from '@/Composables/useDebouncedQuery';

export interface PermalinkPreview {
  permalink: string;
  url: string;
}

/**
 * Previews the permalink/URL a News/Page create form would end up with for the title (and
 * lang) as currently typed — nothing is written server-side. Mirrors
 * useDuplicateDutyCheck.ts: same debounce, same "gate on url so clearing the field clears the
 * last result" trick.
 */
export function usePermalinkPreview(
  type: 'news' | 'page',
  title: () => string,
  lang: () => string,
) {
  const { data: preview, isChecking, check } = useDebouncedQuery<PermalinkPreview | null>({
    url: () => {
      const currentTitle = (title() ?? '').trim();

      if (currentTitle.length < 2) {
        return null;
      }

      const params = new URLSearchParams({ title: currentTitle, lang: lang() });
      const routeName = type === 'news' ? 'api.v1.admin.news.permalinkPreview' : 'api.v1.admin.pages.permalinkPreview';

      return `${route(routeName)}?${params.toString()}`;
    },
    watchSources: [() => title(), () => lang()],
    debounceMs: 500,
    initialValue: null,
  });

  return { preview, isChecking, check };
}
