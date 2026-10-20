<template>
  <MultiSelect
    :id
    v-model="selectedNodes"
    :options="nodes"
    label-field="path"
    value-field="key"
    :placeholder
    :empty-text
    :disabled
    :class="props.class"
  >
    <template #option="{ item }">
      <TypeTreeOptionLabel :node="item" />
    </template>
    <template #selected-item="{ item }">
      <span class="max-w-[220px] truncate" :title="item.path">{{ item.label }}</span>
    </template>
  </MultiSelect>
</template>

<script setup lang="ts" generic="T extends TreeType">
import { computed, type HTMLAttributes } from 'vue';

import TypeTreeOptionLabel from './TypeTreeOptionLabel.vue';
import { useTypeTreeNodes } from './useTypeTreeNodes';

import { MultiSelect } from '@/Components/ui/multi-select';
import { type TreeType, type TypeId, typePath } from '@/Utils/typeTree';

const props = withDefaults(defineProps<{
  types: T[];
  id?: string;
  labelField?: string;
  exclude?: TypeId[];
  /** Types hidden from the options; their subtypes stay. */
  omit?: TypeId[];
  placeholder?: string;
  emptyText?: string;
  disabled?: boolean;
  class?: HTMLAttributes['class'];
}>(), {
  id: undefined,
  labelField: 'title',
  exclude: undefined,
  omit: undefined,
  placeholder: undefined,
  emptyText: undefined,
  class: undefined,
});

/** Selected type ids, in the id type the types carry. */
const model = defineModel<TypeId[]>({ default: () => [] });

const tree = useTypeTreeNodes(() => props.types, () => props.labelField, () => props.exclude, () => props.omit);
const nodes = computed(() => tree.value.map(node => ({ ...node, key: String(node.item.id), path: typePath(node) })));

const selectedNodes = computed({
  get: () => {
    const selected = new Set(model.value.map(String));

    return nodes.value.filter(node => selected.has(node.key));
  },
  set: (picked) => {
    model.value = picked.map(node => node.item.id);
  },
});
</script>
