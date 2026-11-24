import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useIsAdminContext() {
  const page = usePage();

  return computed(() => {
    const path = page.props.app?.path;
    return typeof path === 'string' && /^\/?mano(?:\/|$)/.test(path);
  });
}
