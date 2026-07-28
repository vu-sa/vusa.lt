<template>
  <div class="flex flex-col gap-4">
    <Field>
      <FieldLabel>{{ $t('rich-content.spacer_size_label') }}</FieldLabel>
      <FieldDescription>{{ $t('rich-content.spacer_size_hint') }}</FieldDescription>
      <RadioGroup v-model="size" class="grid grid-cols-2 gap-2 sm:grid-cols-3">
        <label
          v-for="option in sizeOptions"
          :key="option.value"
          class="flex cursor-pointer flex-col gap-2 rounded-lg border p-3 transition-colors"
          :class="size === option.value
            ? 'border-primary bg-primary/5 dark:bg-primary/10'
            : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600'"
        >
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $t(`rich-content.${option.labelKey}`) }}</span>
            <RadioGroupItem :value="option.value" />
          </div>
          <!-- Visual ruler: a fixed-height track with a bar at the option's real height,
               so the author sees the actual gap they're picking, not just a label. -->
          <div class="flex h-20 items-end" aria-hidden="true">
            <div :class="[option.class, 'w-full rounded-sm bg-zinc-300 dark:bg-zinc-600']" />
          </div>
          <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ option.rem }}rem</span>
        </label>
      </RadioGroup>
    </Field>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { SPACER_SIZES, DEFAULT_SPACER_SIZE, type SpacerSize } from './spacerSizes';

import type { Spacer } from '@/Types/contentParts';
import { Field, FieldLabel, FieldDescription } from '@/Components/ui/field';
import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group';

/**
 * `spacer` editor. The block has no `json_content`; everything lives on `options.size`.
 * `modelValue` (the empty `json_content`) is declared only so the empty object doesn't
 * fall through as an attribute on the root — ContentEditorFactory always binds both.
 */
defineModel<Spacer['json_content']>();
const options = defineModel<Spacer['options']>('options');

const sizeOptions = SPACER_SIZES;

const size = computed<SpacerSize>({
  get: () => options.value?.size ?? DEFAULT_SPACER_SIZE,
  set: (value) => {
    options.value = { ...(options.value ?? {}), size: value };
  },
});
</script>
