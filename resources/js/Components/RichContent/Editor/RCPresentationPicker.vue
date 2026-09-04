<template>
  <div class="flex flex-col gap-3">
    <Field>
      <FieldLabel>{{ $t('rich-content.section_presentation') }}</FieldLabel>
      <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
        <button v-for="option in presentationOptions" :key="option.value" type="button"
          class="rounded-lg border-2 p-2.5 text-left transition-colors"
          :class="(modelValue ?? 'auto') === option.value
            ? 'border-vusa-red bg-red-50/50 dark:bg-red-950/20'
            : 'border-border hover:border-zinc-300 dark:hover:border-zinc-600'"
          @click="$emit('update:modelValue', option.value)">
          <span class="block text-sm font-medium">{{ option.label }}</span>
          <span class="mt-0.5 block text-xs text-muted-foreground">{{ option.help }}</span>
        </button>
      </div>
    </Field>

    <Field v-if="modelValue === 'plain'">
      <FieldLabel>{{ $t('rich-content.plain_padding') }}</FieldLabel>
      <div class="grid grid-cols-3 gap-2">
        <button v-for="option in paddingOptions" :key="option.value" type="button"
          class="rounded-lg border px-2 py-1.5 text-center text-xs transition-colors"
          :class="(plainPadding ?? 'default') === option.value
            ? 'border-vusa-red bg-red-50/50 font-medium dark:bg-red-950/20'
            : 'border-border hover:border-zinc-300 dark:hover:border-zinc-600'"
          @click="$emit('update:plainPadding', option.value)">
          {{ option.label }}
        </button>
      </div>
    </Field>
  </div>
</template>

<script setup lang="ts">
/**
 * The one author-facing chrome control left after the band/flow presentation model
 * (see bandLayout.ts): `auto` alternates this block's ground with its neighbours,
 * `plain` opts out of the ground and reveals its own padding control. Reused by every
 * band-capable editor (RCSectionOptionsFields, HeroForm, SpotifyEmbedEditor) so the choices
 * read identically everywhere they appear.
 */
import { trans as $t } from 'laravel-vue-i18n';

import { Field, FieldLabel } from '@/Components/ui/field';
import type { BlockPresentation } from '../bandLayout';
import type { PlainPadding } from '../sectionClasses';

defineProps<{
  modelValue?: BlockPresentation;
  plainPadding?: PlainPadding;
}>();

defineEmits<{
  (e: 'update:modelValue', value: BlockPresentation): void;
  (e: 'update:plainPadding', value: PlainPadding): void;
}>();

const presentationOptions: { value: BlockPresentation; label: string; help: string }[] = [
  { value: 'auto', label: $t('rich-content.presentation_auto'), help: $t('rich-content.presentation_auto_help') },
  { value: 'plain', label: $t('rich-content.presentation_plain'), help: $t('rich-content.presentation_plain_help') },
];

const paddingOptions: { value: PlainPadding; label: string }[] = [
  { value: 'none', label: $t('rich-content.plain_padding_none') },
  { value: 'compact', label: $t('rich-content.plain_padding_compact') },
  { value: 'default', label: $t('rich-content.plain_padding_default') },
];
</script>
