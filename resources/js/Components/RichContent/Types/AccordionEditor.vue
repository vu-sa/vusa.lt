<template>
  <NDynamicInput v-model:value="modelValue" @create="onCreate">
    <template #create-button-default>
      Sukurti
    </template>
    <template #default="{ value }">
      <div
        class="flex w-full flex-col gap-3 rounded-lg border border-zinc-200/60 bg-zinc-50/30 p-4 dark:border-zinc-800/50 dark:bg-zinc-800/20">
        <NFormItem label="Pavadinimas" :show-feedback="false">
          <NInput v-model:value="value.label" type="text" placeholder="Įrašyti pavadinimą..." />
        </NFormItem>
        <OriginalTipTap v-model="value.content" />
      </div>
    </template>
  </NDynamicInput>
</template>

<script setup lang="ts">
import type { ShadcnAccordion } from '@/Types/contentParts';
import OriginalTipTap from '@/Components/TipTap/OriginalTipTap.vue';

const modelValue = defineModel<ShadcnAccordion['json_content']>();

function onCreate(): ShadcnAccordion['json_content'][number] {
  return {
    label: "",
    content: {} as ShadcnAccordion['json_content'][number]['content'],
  };
}
</script>
