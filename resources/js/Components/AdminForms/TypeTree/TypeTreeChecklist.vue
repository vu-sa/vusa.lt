<template>
  <fieldset :disabled class="divide-y divide-border">
    <label
      v-for="node in nodes"
      :key="String(node.item.id)"
      class="flex min-h-11 cursor-pointer items-center gap-3 py-2 text-sm"
      :style="{ paddingLeft: `${node.depth * 1.5}rem` }"
      :data-testid="`type-tree-check-${node.item.id}`"
    >
      <Checkbox :model-value="isChecked(node.item.id)" @update:model-value="toggle(node.item.id, $event === true)" />
      <span class="min-w-0 truncate">{{ node.label }}</span>
    </label>
  </fieldset>
</template>

<script setup lang="ts" generic="T extends TreeType">
import { useTypeTreeNodes } from './useTypeTreeNodes';

import { Checkbox } from '@/Components/ui/checkbox';
import type { TreeType, TypeId } from '@/Utils/typeTree';

const props = withDefaults(defineProps<{
  types: T[];
  labelField?: string;
  disabled?: boolean;
}>(), {
  labelField: 'title',
});

const model = defineModel<TypeId[]>({ default: () => [] });

const nodes = useTypeTreeNodes(() => props.types, () => props.labelField);

const isChecked = (id: TypeId) => model.value.some(value => String(value) === String(id));

function toggle(id: TypeId, checked: boolean): void {
  const rest = model.value.filter(value => String(value) !== String(id));
  model.value = checked ? [...rest, id] : rest;
}
</script>
