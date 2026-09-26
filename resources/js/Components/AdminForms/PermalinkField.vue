<template>
  <div class="flex flex-col gap-2" data-slot="form-field">
    <Label :for="id" :class="cn('text-sm font-bold text-foreground', labelClass)">
      {{ label ?? $t('Nuoroda') }}
    </Label>

    <div
      :class="[
        'flex min-h-11 items-stretch border border-border bg-secondary/50 text-sm transition-colors',
        'focus-within:bg-background focus-within:border-brand focus-within:ring-2 focus-within:ring-brand/20',
        inputValidationClass,
      ]"
    >
      <span class="flex min-w-0 shrink items-center gap-2 pl-3 font-mono text-muted-foreground">
        <Link2 class="size-4 shrink-0" aria-hidden="true" />
        <span class="truncate">{{ baseUrl }}/</span>
      </span>
      <Input
        :id
        :model-value="permalink"
        :disabled
        class="min-w-24 flex-1 border-0 bg-transparent px-1 font-mono focus-visible:ring-0"
        :placeholder="$t('nuorodos-fragmentas')"
        @update:model-value="$emit('update:permalink', $event)"
        @change="$emit('change', $event)"
      />
      <Button
        variant="ghost"
        size="icon"
        type="button"
        class="h-auto w-11 shrink-0 border-l border-border"
        :aria-label="$t('Kopijuoti nuorodą')"
        :title="$t('Kopijuoti nuorodą')"
        @click="copyUrl"
      >
        <Copy v-if="!copied" class="size-4" />
        <Check v-else class="size-4 text-[var(--status-success)]" />
      </Button>
      <Button
        v-if="viewUrl"
        variant="ghost"
        size="icon"
        as="a"
        :href="viewUrl"
        target="_blank"
        rel="noopener noreferrer"
        class="h-auto w-11 shrink-0 border-l border-border"
        :aria-label="$t('Atidaryti puslapį')"
        :title="$t('Atidaryti puslapį')"
      >
        <ExternalLink class="size-4" />
      </Button>
    </div>

    <p v-if="hint" class="text-xs leading-relaxed text-muted-foreground">
      {{ hint }}
    </p>

    <p v-if="disabled && explanation" class="flex items-center gap-1 text-xs text-muted-foreground">
      <Info class="h-3.5 w-3.5 shrink-0" />
      {{ explanation }}
    </p>

    <Alert v-if="warning" class="border-[var(--status-attention-border)] bg-[var(--status-attention-surface)] text-[var(--status-attention)]">
      <AlertTriangle class="h-4 w-4" />
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
import { AlertTriangle, Check, Copy, ExternalLink, Info, Link2 } from 'lucide-vue-next';

import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { cn } from '@/Utils/Shadcn/utils';

const props = withDefaults(defineProps<{
  id?: string;
  permalink?: string;
  baseUrl: string;
  disabled?: boolean;
  viewUrl?: string;
  explanation?: string;
  /** Overrides the default "Nuoroda" label — useful when several fields sit side by side. */
  label?: string;
  labelClass?: string;
  /** A serious warning shown below the field, e.g. when editing the permalink breaks the old URL. */
  warning?: string;
  /** Mirrors FormFieldWrapper validation wiring. */
  validating?: boolean;
  valid?: boolean;
  invalid?: boolean;
  hint?: string;
}>(), {
  id: 'permalink',
  permalink: undefined,
  viewUrl: undefined,
  explanation: undefined,
  label: undefined,
  labelClass: undefined,
  warning: undefined,
  hint: undefined,
});

const emit = defineEmits<{
  (e: 'update:permalink', value: string): void;
  (e: 'change', value: unknown): void;
}>();

const copied = ref(false);
const { copy } = useClipboard();

const inputValidationClass = computed(() => {
  if (!props.validating && props.invalid) {
    return 'border-destructive focus-within:border-destructive';
  }
  if (!props.validating && props.valid) {
    return 'border-[var(--status-success-border)]';
  }
  return 'border-border';
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
