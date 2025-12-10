<template>
  <div class="mt-4 flex flex-col gap-4">
    <NFormItem label="Flow Graph" :show-feedback="false">
      <NSelect v-model:value="modelValue.preset" :options="[
        { 'label': 'VusaStructure', 'value': 'VusaStructure' }
      ]" />
    </NFormItem>
  </div>
</template>

<script setup lang="ts">
import type { FlowGraph } from '@/Types/contentParts';

const modelValue = defineModel<FlowGraph['json_content']>();
</script>

