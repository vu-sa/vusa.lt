<template>
  <div class="flex flex-col gap-4">
    <Field>
      <FieldLabel>{{ $t('rich-content.eyebrow') }}</FieldLabel>
      <Input v-model="modelValue!.eyebrow" type="text" :placeholder="$t('rich-content.enter_eyebrow')" />
    </Field>

    <Field>
      <FieldLabel>{{ $t('rich-content.title') }}</FieldLabel>
      <Input v-model="modelValue.title" type="text" :placeholder="$t('rich-content.enter_title')" />
    </Field>

    <NewsOptionsFields v-model="options" />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';

import NewsOptionsFields from './NewsOptionsFields.vue';

import type { News } from '@/Types/contentParts';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';

const modelValue = defineModel<News['json_content']>();
const options = defineModel<News['options']>('options', { required: true });

onMounted(() => {
  if (!options.value) {
    options.value = { tenantScope: 'current', limit: 4 };
  }
});
</script>
