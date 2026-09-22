<template>
  <div class="space-y-2">
    <div class="flex items-center justify-between">
      <Label :for="id" class="flex items-center gap-1.5">
        {{ label }}
        <span v-if="required" class="text-destructive">*</span>
        <TooltipProvider v-if="hint">
          <Tooltip>
            <TooltipTrigger as-child>
              <button type="button" class="inline-flex">
                <Info class="size-3.5 cursor-help text-muted-foreground" />
              </button>
            </TooltipTrigger>
            <TooltipContent side="top" class="max-w-xs">
              {{ hint }}
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
        <!-- Validation status indicators -->
        <span
          v-if="validating"
          class="ml-1 inline-flex items-center"
          role="status"
          aria-live="polite"
          :aria-label="$t('validation.validating')"
        >
          <Spinner class="h-3 w-3 text-muted-foreground" aria-hidden="true" />
        </span>
        <span
          v-else-if="valid"
          class="ml-1 text-[var(--status-success)]"
          role="status"
          aria-live="polite"
          :aria-label="$t('validation.valid')"
        >
          <CheckCircle2 class="size-3.5" aria-hidden="true" />
        </span>
        <span
          v-else-if="invalid"
          class="ml-1 text-destructive"
          role="status"
          aria-live="polite"
          :aria-label="$t('validation.invalid')"
        >
          <AlertCircle class="size-3.5" aria-hidden="true" />
        </span>
      </Label>
      <span v-if="charCount !== undefined" class="text-xs" :class="charCountClass">
        {{ charCount }}<span v-if="maxLength">/{{ maxLength }}</span>
      </span>
    </div>

    <slot />

    <p v-if="helperText" class="text-xs text-muted-foreground">
      {{ helperText }}
    </p>
    <p v-if="error" class="text-xs text-destructive">
      {{ error }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { AlertCircle, CheckCircle2, Info } from 'lucide-vue-next';
import { trans as $t } from 'laravel-vue-i18n';

import { Label } from '@/Components/ui/label';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import Spinner from '@/Components/ui/spinner/Spinner.vue';

const props = defineProps<{
  id: string;
  label: string;
  required?: boolean;
  hint?: string;
  helperText?: string;
  error?: string;
  charCount?: number;
  maxLength?: number;
  // Precognition validation states
  validating?: boolean;
  valid?: boolean;
  invalid?: boolean;
}>();

const charCountClass = computed(() => {
  if (props.charCount === undefined || props.maxLength === undefined) {
    return 'text-muted-foreground';
  }

  const ratio = props.charCount / props.maxLength;

  if (ratio > 1) {
    return 'text-destructive font-medium';
  }
  if (ratio >= 0.8) {
    return 'text-[var(--status-attention)]';
  }
  if (ratio >= 0.5) {
    return 'text-[var(--status-success)]';
  }
  return 'text-muted-foreground';
});
</script>
