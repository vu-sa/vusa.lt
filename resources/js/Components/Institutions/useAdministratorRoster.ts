import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * The roster is replaced wholesale per term — one idempotent PUT rather than
 * add/remove endpoints, matching how the server stores it as a set.
 */
export function useAdministratorRoster(institutionId: string) {
  const processingCadenceId = ref<string | null>(null);

  function save(cadenceId: string, userIds: string[]): void {
    router.put(route('institutions.administrators.update', institutionId), {
      cadence_id: cadenceId,
      user_ids: userIds,
    }, {
      preserveScroll: true,
      onStart: () => { processingCadenceId.value = cadenceId; },
      onFinish: () => { processingCadenceId.value = null; },
    });
  }

  return { processingCadenceId, save };
}
