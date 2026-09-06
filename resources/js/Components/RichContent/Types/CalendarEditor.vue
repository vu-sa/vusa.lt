<template>
  <div class="flex flex-col gap-4">
    <Field>
      <FieldLabel>{{ $t('rich-content.eyebrow') }}</FieldLabel>
      <Input v-model="modelValue!.eyebrow" type="text" :placeholder="$t('rich-content.eyebrow')" />
    </Field>

    <Field>
      <FieldLabel>{{ $t('rich-content.title') }}</FieldLabel>
      <Input v-model="modelValue!.title" type="text" :placeholder="$t('rich-content.enter_title')" />
    </Field>

    <CalendarOptionsFields v-model="options" />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';

import type { Calendar } from '@/Types/contentParts';
import CalendarOptionsFields from './CalendarOptionsFields.vue';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';

const modelValue = defineModel<Calendar['json_content']>();
const options = defineModel<Calendar['options']>('options', { required: true });

// Initialize options on mount if they're null/undefined
onMounted(() => {
  if (!options.value) {
    options.value = { tenantScope: 'all' };
  }
});

</script>
