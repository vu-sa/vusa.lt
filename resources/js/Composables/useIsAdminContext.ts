import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Detect whether the current page is rendered inside the admin console.
 *
 * The admin console is served under the `/mano/*` path prefix.
 */
export function useIsAdminContext() {
  return computed(() => {
    const path = usePage().props.app?.path;

    return typeof path === 'string' && path.startsWith('/mano');
  });
}
