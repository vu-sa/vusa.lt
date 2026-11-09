<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

import { Button } from '@/Components/ui/button';
import type { LearningStepData } from '@/Types/learning';

const props = defineProps<{ step: LearningStepData; readonly?: boolean }>();
const saving = ref(false);
const failed = ref(false);

function save(status: 'done' | 'skipped' | null) {
  if (saving.value || props.readonly) return;
  saving.value = true;
  failed.value = false;
  router.post(route('learningSteps.progress', props.step.id), { status }, {
    preserveScroll: true,
    preserveState: true,
    onError: () => { failed.value = true; },
    onFinish: () => { saving.value = false; },
  });
}
</script>

<template>
  <div v-if="!readonly && step.kind !== 'meeting_prep'" class="space-y-2" :aria-busy="saving">
    <p v-if="step.autoCompleted" class="text-sm text-muted-foreground">{{ $t('learning.path.auto_help') }}</p>
    <div v-else class="flex flex-wrap gap-2">
      <template v-if="step.state === 'pending' || step.state === 'updated'">
        <Button variant="outline" voice="sentence" class="min-h-11" :disabled="saving" data-testid="complete-step" @click="save('done')">
          {{ $t(step.kind === 'knowledge' ? 'learning.path.read' : 'learning.path.complete') }}
        </Button>
        <Button v-if="step.skippable" variant="ghost" voice="sentence" class="min-h-11" :disabled="saving" data-testid="skip-step" @click="save('skipped')">
          {{ $t('learning.path.skip') }}
        </Button>
      </template>
      <Button v-if="step.state !== 'pending'" variant="ghost" voice="sentence" class="min-h-11" :disabled="saving" data-testid="reopen-step" @click="save(null)">
        {{ $t('learning.path.reopen') }}
      </Button>
    </div>
    <p v-if="failed" role="alert" class="text-sm text-destructive">{{ $t('learning.path.save_failed') }}</p>
  </div>
</template>
