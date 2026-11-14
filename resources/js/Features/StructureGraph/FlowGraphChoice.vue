<template>
  <RadioGroup v-model="model" class="grid gap-2" data-slot="flow-graph-choice">
    <label
      v-for="graph in graphRegistry"
      :key="graph.key"
      class="flex cursor-pointer items-start gap-3 border p-3 transition-colors"
      :class="model === graph.key ? 'border-brand bg-brand/5' : 'border-border hover:border-brand'"
      :data-testid="`flow-graph-option-${graph.key}`"
    >
      <RadioGroupItem :value="graph.key" class="mt-0.5" />
      <span class="flex-1">
        <span class="block text-sm font-medium text-foreground">{{ graph.title[locale] }}</span>
        <span class="mt-0.5 block text-xs text-muted-foreground">{{ graph.description[locale] }}</span>
      </span>
    </label>
  </RadioGroup>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { getActiveLanguage } from 'laravel-vue-i18n';

import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group';

import { graphRegistry } from './registry';

const model = defineModel<string>({ required: true });
const locale = computed(() => (getActiveLanguage() === 'en' ? 'en' : 'lt'));
</script>
