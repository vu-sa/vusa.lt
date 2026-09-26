<template>
  <FormFieldWrapper id="tags" :label="label ?? $t('Žymos')" :hint="hint ?? $t('Pasirinkite temas')">
    <MultiSelect v-model="selectedTags" :options="tagOptions" value-field="value" :disabled
      :placeholder="$t('Pasirinkite žymas...')" />
  </FormFieldWrapper>
</template>

<script setup lang="ts">
/**
 * Tag picker shared by News, Page and Calendar forms — tags are polymorphic
 * (taggables), so the same id-array binding works for all three. Lifted out of
 * NewsForm.vue rather than copied a third time.
 */
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import FormFieldWrapper from './FormFieldWrapper.vue';

import { MultiSelect } from '@/Components/ui/multi-select';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';

const { availableTags = [], label = undefined, hint = undefined, disabled } = defineProps<{
  availableTags?: App.Entities.Tag[];
  label?: string;
  hint?: string;
  disabled?: boolean;
}>();

const modelValue = defineModel<number[]>({ default: () => [] });

const tagOptions = computed(() => availableTags.map(tag => ({
  label: getTranslatedValue(tag.name, undefined, 'Unknown'),
  value: tag.id,
})));

const tagOptionsMap = computed(() => {
  const map = new Map<number, { label: string; value: number }>();

  for (const option of tagOptions.value) {
    map.set(option.value, option);
  }

  return map;
});

const selectedTags = computed({
  get: () => {
    const map = tagOptionsMap.value;

    return (modelValue.value ?? [])
      .map(id => map.get(id))
      .filter((option): option is { label: string; value: number } => Boolean(option));
  },
  set: (items: { label: string; value: number }[]) => {
    modelValue.value = items.map(item => item.value);
  },
});
</script>
