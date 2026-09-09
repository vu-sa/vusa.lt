import { computed, ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';

import { useApi } from '@/Composables/useApi';

interface PermalinkPreview {
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
  const url = ref('');

  const { data, isFetching, execute } = useApi<PermalinkPreview>(url, {
    immediate: false,
    showErrorToast: false,
  });

  const preview = computed<PermalinkPreview | null>(() => (url.value ? data.value ?? null : null));

  const run = useDebounceFn(() => {
    const currentTitle = (title() ?? '').trim();

    if (currentTitle.length < 2) {
      url.value = '';
      return;
    }

    const params = new URLSearchParams({ title: currentTitle, lang: lang() });
    const routeName = type === 'news' ? 'api.v1.admin.news.permalinkPreview' : 'api.v1.admin.pages.permalinkPreview';

    url.value = `${route(routeName)}?${params.toString()}`;
    execute();
  }, 500);

  watch([() => title(), () => lang()], run);

  return { preview, isChecking: isFetching, check: run };
}
