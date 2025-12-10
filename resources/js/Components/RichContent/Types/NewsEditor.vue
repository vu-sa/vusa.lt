<template>
  <div class="mt-4 flex flex-col gap-4">
    <NFormItem label="Pavadinimas" :show-feedback="false">
      <NInput v-model:value="modelValue.title" type="text" />
    </NFormItem>
  </div>
</template>

<script setup lang="ts">
import type { News } from '@/Types/contentParts';

const modelValue = defineModel<News['json_content']>();
</script>

