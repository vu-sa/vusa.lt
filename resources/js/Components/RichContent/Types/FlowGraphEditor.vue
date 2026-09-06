<template>
  <div class="flex flex-col gap-4">
    <Field>
      <FieldLabel>{{ $t('rich-content.flow_graph_preset') }}</FieldLabel>
      <RadioGroup v-model="modelValue.preset" class="grid gap-3">
        <label
          v-for="preset in presets"
          :key="preset.value"
          class="flex cursor-pointer items-center gap-3 rounded-lg border p-4 transition-colors"
          :class="modelValue.preset === preset.value
            ? 'border-brand bg-brand/5'
            : 'border-border hover:border-brand'">
          <RadioGroupItem :value="preset.value" />
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <component :is="preset.icon" class="h-5 w-5 text-brand" />
              <span class="font-medium text-foreground">{{ preset.label }}</span>
            </div>
            <p class="mt-1 text-xs text-muted-foreground">{{ preset.description }}</p>
          </div>
        </label>
      </RadioGroup>
    </Field>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import type { FlowGraph } from '@/Types/contentParts';
import { Field, FieldLabel } from '@/Components/ui/field';
import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group';
import IFluentOrganization24Regular from '~icons/fluent/organization24-regular';

const modelValue = defineModel<FlowGraph['json_content']>();

const presets = computed(() => [
  {
    value: 'VusaStructure',
    label: $t('rich-content.presets.vusa_structure'),
    description: $t('rich-content.presets.vusa_structure_description'),
    icon: IFluentOrganization24Regular,
  },
]);
</script>
