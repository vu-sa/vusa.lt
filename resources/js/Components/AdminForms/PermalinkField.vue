<template>
  <div class="space-y-2">
    <Label class="flex items-center gap-1.5">
      <IFluentLink24Regular class="h-4 w-4" />
      {{ label ?? $t('Nuoroda') }}
    </Label>

    <div class="flex items-stretch gap-2">
      <div class="flex flex-1 items-center gap-0 overflow-hidden rounded-md border bg-muted/50">
        <span class="shrink-0 rounded-l-md border-r bg-muted px-3 py-2 text-sm text-muted-foreground">
          {{ baseUrl }}/
        </span>
        <Input
          :model-value="permalink"
          :disabled
          class="rounded-l-none border-0 bg-transparent focus-visible:ring-0"
          :class="inputValidationClass"
          :placeholder="$t('nuorodos-fragmentas')"
          @update:model-value="$emit('update:permalink', $event)"
          @change="$emit('change', $event)"
        />
      </div>

      <TooltipProvider>
        <Tooltip>
          <TooltipTrigger as-child>
            <Button variant="outline" size="icon" @click="copyUrl">
              <IFluentCopy24Regular v-if="!copied" class="h-4 w-4" />
              <IFluentCheckmark24Regular v-else class="h-4 w-4 text-green-600" />
            </Button>
          </TooltipTrigger>
          <TooltipContent>{{ $t('Kopijuoti nuorodą') }}</TooltipContent>
        </Tooltip>
      </TooltipProvider>

      <TooltipProvider v-if="viewUrl">
        <Tooltip>
          <TooltipTrigger as-child>
            <Button variant="outline" size="icon" as="a" :href="viewUrl" target="_blank">
              <IFluentOpen24Regular class="h-4 w-4" />
            </Button>
          </TooltipTrigger>
          <TooltipContent>{{ $t('Atidaryti puslapį') }}</TooltipContent>
        </Tooltip>
      </TooltipProvider>
    </div>

    <p v-if="disabled && explanation" class="flex items-center gap-1 text-xs text-muted-foreground">
      <IFluentInfo16Regular class="h-3.5 w-3.5 shrink-0" />
      {{ explanation }}
    </p>

    <Alert v-if="warning" class="border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
      <IFluentWarning24Regular />
      <AlertTitle>{{ $t('Dėmesio') }}</AlertTitle>
      <AlertDescription>
        {{ warning }}
      </AlertDescription>
    </Alert>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useClipboard } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';

import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import IFluentWarning24Regular from '~icons/fluent/warning24-regular';

const props = defineProps<{
  permalink?: string;
  baseUrl: string;
  disabled?: boolean;
  viewUrl?: string;
  explanation?: string;
  /** Overrides the default "Nuoroda" label — useful when several fields sit side by side. */
  label?: string;
  /** A serious warning shown below the field, e.g. when editing the permalink breaks the old URL. */
  warning?: string;
  /** Mirrors FormFieldWrapper validation wiring. */
  validating?: boolean;
  valid?: boolean;
  invalid?: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:permalink', value: string): void;
  (e: 'change', value: unknown): void;
}>();

const copied = ref(false);
const { copy } = useClipboard();

const inputValidationClass = computed(() => {
  if (props.validating) {
    return '';
  }
  if (props.valid) {
    return 'border-green-300 focus:border-green-500 dark:border-green-700';
  }
  if (props.invalid) {
    return 'border-red-300 focus:border-red-500 dark:border-red-700';
  }
  return '';
});

const fullUrl = computed(() => {
  const slug = props.permalink || '';
  return `${props.baseUrl}/${slug}`;
});

const copyUrl = async () => {
  await copy(fullUrl.value);
  copied.value = true;
  setTimeout(() => {
    copied.value = false;
  }, 2000);
};
</script>
