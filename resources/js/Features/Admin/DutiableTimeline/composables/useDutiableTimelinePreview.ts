import { computed, ref } from 'vue';

import { useApiMutation } from '@/Composables/useApi';

import type { TimelineOperation, TimelinePlanPayload } from '../types';

/**
 * Server-side dry run of a staged batch.
 *
 * The plan is computed by the same action the write uses, so what the user confirms and
 * what gets saved cannot diverge. Read-only, hence the JSON API — only the commit needs
 * the Inertia flash contract that guardSelfLockout depends on.
 */
export function useDutiableTimelinePreview() {
  const operations = ref<TimelineOperation[]>([]);
  const isOpen = ref(false);

  const body = computed(() => ({ operations: operations.value }));

  const { data, error, isFetching, execute } = useApiMutation<TimelinePlanPayload>(
    route('api.v1.admin.dutiableTimeline.preview'),
    'POST',
    body,
    { showSuccessToast: false },
  );

  async function open(next: TimelineOperation[]): Promise<void> {
    operations.value = next;
    isOpen.value = true;

    await execute();
  }

  function close(): void {
    isOpen.value = false;
  }

  return { plan: data, error, isFetching, isOpen, open, close, refresh: execute };
}
