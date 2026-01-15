<template>
  <div class="flex flex-col gap-4">
    <Field>
      <FieldLabel>{{ $t('rich-content.title') }}</FieldLabel>
      <Input v-model="modelValue.title" type="text" :placeholder="$t('rich-content.enter_title')" />
    </Field>
  </div>
</template>

<script setup lang="ts">
import type { News } from '@/Types/contentParts';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';

const modelValue = defineModel<News['json_content']>();
</script>

