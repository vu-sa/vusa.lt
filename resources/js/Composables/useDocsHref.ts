import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * A link into the VitePress docs, e.g. `useDocsHref('/rezervacijos/rezervacijos')`. The guide is
 * Lithuanian-only, so pages always resolve to `/gidas`; only the bare base follows the user's
 * locale, for the changelog that exists in both languages.
 */
export function useDocsHref(path = '') {
  const page = usePage();

  return computed(() => (path === '' && page.props.app?.locale === 'en' ? '/gidas/en' : `/gidas${path}`));
}
