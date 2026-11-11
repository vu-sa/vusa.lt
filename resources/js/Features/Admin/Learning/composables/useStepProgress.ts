import { router } from '@inertiajs/vue3';
import { computed, ref, type Ref } from 'vue';

import type { LearningStepData, KnowledgeItemData, SelfLearnedItemData } from '@/Types/learning';

export function useStepProgress(step: Ref<LearningStepData | KnowledgeItemData | SelfLearnedItemData>, readonly: Ref<boolean | undefined>) {
  const saving = ref(false);
  const failed = ref(false);
  // The tick lands on click, not after the round trip; an error puts it back.
  const optimistic = ref<boolean | null>(null);
  const locked = computed(() => Boolean(readonly.value) || step.value.autoCompleted || ('kind' in step.value && step.value.kind === 'meeting_prep'));
  const canSkip = computed(() => !locked.value && step.value.skippable && (step.value.state === 'pending' || step.value.state === 'updated'));

  function save(status: 'done' | 'skipped' | null): void {
    if (saving.value || locked.value) return;
    saving.value = true;
    failed.value = false;
    router.post(route('source' in step.value ? 'knowledgeItems.progress' : 'learningSteps.progress', step.value.id), { status }, {
      preserveScroll: true,
      preserveState: true,
      onError: () => { failed.value = true; },
      onHttpException: () => { failed.value = true; },
      onNetworkError: () => { failed.value = true; },
      onFinish: () => {
        saving.value = false;
        optimistic.value = null;
      },
    });
  }

  const checked = computed({
    get: () => optimistic.value ?? (step.value.state === 'done' || step.value.state === 'skipped'),
    set: (value: boolean | 'indeterminate') => {
      if (saving.value || locked.value) return;
      optimistic.value = value === true;
      save(value === true ? 'done' : null);
    },
  });

  return { saving, failed, locked, canSkip, checked, save };
}
