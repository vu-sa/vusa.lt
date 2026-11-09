<script setup lang="ts">
import { toRef } from 'vue';
import { MoreHorizontal, Zap } from '@lucide/vue';

import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import type { LearningStepData } from '@/Types/learning';

import { useStepProgress } from './composables/useStepProgress';

const props = defineProps<{ step: LearningStepData; readonly?: boolean }>();
const { saving, failed, canSkip, checked, save } = useStepProgress(toRef(props, 'step'), toRef(props, 'readonly'));
</script>

<template>
  <div class="space-y-2" :aria-busy="saving">
    <div class="flex items-start gap-2">
      <div v-if="step.kind !== 'meeting_prep'" class="flex shrink-0 items-center">
        <label class="flex size-11 shrink-0 cursor-pointer items-center justify-center">
          <Checkbox v-model="checked" :disabled="saving || readonly || step.autoCompleted" :aria-label="step.title" data-testid="step-checkbox" />
        </label>
        <Zap v-if="step.autoCompleted" class="size-3 text-muted-foreground" role="img" :aria-label="$t('learning.path.auto_help')" />
      </div>
      <div class="min-w-0 flex-1"><slot /></div>
      <DropdownMenu v-if="canSkip">
        <DropdownMenuTrigger as-child>
          <Button variant="ghost" size="icon" voice="sentence" class="size-11 shrink-0" :disabled="saving" :aria-label="$t('learning.path.actions')">
            <MoreHorizontal class="size-4" aria-hidden="true" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem data-testid="skip-step" @select="save('skipped')">{{ $t('learning.path.skip') }}</DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
    <p v-if="failed" role="alert" class="text-sm text-destructive">{{ $t('learning.path.save_failed') }}</p>
  </div>
</template>
