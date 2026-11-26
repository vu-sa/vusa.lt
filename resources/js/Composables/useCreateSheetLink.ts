import { router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

export function useCreateSheetLink(canCreate: boolean) {
  const page = usePage();
  const initialUrl = page.url ?? '';
  const url = new URL(initialUrl, 'https://local.invalid');
  const linked = canCreate && url.searchParams.get('create') === '1';
  const open = ref(linked);

  if (linked) {
    watch(open, (visible) => {
      if (!visible && page.url === initialUrl) {
        url.searchParams.delete('create');
        router.replace({ url: url.pathname + url.search + url.hash, preserveState: true, preserveScroll: true });
      }
    }, { once: true });
  }

  return open;
}
