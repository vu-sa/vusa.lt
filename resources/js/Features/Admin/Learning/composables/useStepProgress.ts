import { router } from '@inertiajs/vue3';
import { computed, ref, type Ref } from 'vue';

import type { LearningStepData, KnowledgeItemData } from '@/Types/learning';

export function useStepProgress(step: Ref<LearningStepData | KnowledgeItemData>, readonly: Ref<boolean | undefined>) {
  const saving = ref(false);
  const failed = ref(false);
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
      onFinish: () => { saving.value = false; },
    });
  }

  const checked = computed({
    get: () => step.value.state === 'done' || step.value.state === 'skipped',
    set: (value: boolean | 'indeterminate') => save(value === true ? 'done' : null),
  });

  return { saving, failed, locked, canSkip, checked, save };
}
