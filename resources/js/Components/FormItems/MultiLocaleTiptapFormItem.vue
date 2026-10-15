<template>
  <div class="space-y-2">
    <div class="flex items-center justify-between gap-2">
      <Label v-if="label">{{ label }}</Label>
      <SimpleLocaleButton v-model:locale="inputLang" class="ml-auto" />
    </div>
    <TiptapEditor :key="inputLang" v-model="input[inputLang]" tools="description" html />
    <p v-if="hint" class="text-xs text-muted-foreground">
      {{ hint }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

import TiptapEditor from '../TipTap/TiptapEditor.vue';
import SimpleLocaleButton from '../Buttons/SimpleLocaleButton.vue';

import { Label } from '@/Components/ui/label';

defineProps<{
  /** Omit when a FormFieldWrapper already labels the field. */
  label?: string;
  hint?: string;
}>();

const inputLang = ref<'lt' | 'en'>(usePage().props.app.locale === 'en' ? 'en' : 'lt');

const input = defineModel<{ lt: string; en: string }>('input', {
  default: () => ({ lt: '', en: '' }),
});
</script>
