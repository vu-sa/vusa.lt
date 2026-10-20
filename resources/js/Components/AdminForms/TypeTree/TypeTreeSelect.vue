<template>
  <SingleSelect
    v-if="nodes.length > NATIVE_LIMIT"
    v-model="selectedNode"
    :options="nodes"
    label-field="path"
    value-field="key"
    :placeholder
    :disabled
    :class="props.class"
  >
    <template #option="{ item }">
      <TypeTreeOptionLabel :node="item" />
    </template>
  </SingleSelect>
  <NativeSelect
    v-else
    :id
    v-model="nativeValue"
    :options="nativeOptions"
    :placeholder
    :placeholder-value="null"
    :placeholder-selectable
    :disabled
    :error
    :class="props.class"
  />
</template>

<script setup lang="ts" generic="T extends TreeType">
import { computed, type HTMLAttributes } from 'vue';

import TypeTreeOptionLabel from './TypeTreeOptionLabel.vue';
import { useTypeTreeNodes } from './useTypeTreeNodes';

import { NativeSelect } from '@/Components/ui/native-select';
import { SingleSelect } from '@/Components/ui/single-select';
import { type TreeType, type TypeId, typePath } from '@/Utils/typeTree';

/** Past this many options the picker becomes searchable (pickers.md). */
const NATIVE_LIMIT = 16;
/** Native options cannot be styled, so depth is shown with non-breaking spaces. */
const INDENT = '    ';

const props = withDefaults(defineProps<{
  types: T[];
  id?: string;
  labelField?: string;
  /** Types left out together with their subtypes. */
  exclude?: TypeId[];
  /** Types hidden from the options; their subtypes stay. */
  omit?: TypeId[];
  /** Emit ids as strings, for forms that keep ids as text. */
  asString?: boolean;
  placeholder?: string;
  /** Lets the user pick the placeholder to clear an optional field. */
  placeholderSelectable?: boolean;
  disabled?: boolean;
  error?: boolean | string;
  class?: HTMLAttributes['class'];
}>(), {
  id: undefined,
  labelField: 'title',
  exclude: undefined,
  omit: undefined,
  placeholder: undefined,
  error: undefined,
  class: undefined,
});

const model = defineModel<TypeId | null | undefined>();

const tree = useTypeTreeNodes(() => props.types, () => props.labelField, () => props.exclude, () => props.omit);
const nodes = computed(() => tree.value.map(node => ({ ...node, key: String(node.item.id), path: typePath(node) })));

const emitValue = (id: TypeId | null) => {
  model.value = id === null ? null : (props.asString ? String(id) : id);
};

const nativeOptions = computed(() => nodes.value.map(node => ({
  value: node.key,
  label: `${INDENT.repeat(node.depth)}${node.label}`,
})));

const nativeValue = computed({
  get: () => (model.value === null || model.value === undefined || model.value === '' ? null : String(model.value)),
  set: (key: string | number | null | undefined) => emitValue(nodes.value.find(node => node.key === String(key))?.item.id ?? null),
});

const selectedNode = computed({
  get: () => nodes.value.find(node => node.key === String(model.value)) ?? null,
  set: node => emitValue(node?.item.id ?? null),
});
</script>
