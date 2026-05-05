<template>
  <div role="tree" :class="cn('text-sm', props.class)">
    <div
      v-for="item in visibleItems"
      :key="item.key"
      role="treeitem"
      tabindex="0"
      class="flex cursor-default items-center gap-1.5 rounded-sm px-2 py-1 outline-none hover:bg-accent/50 focus-visible:bg-accent/50"
      :style="{ paddingInlineStart: `${(item.level - 1) * 1}rem` }"
      :aria-expanded="item.hasChildren ? effectiveExpanded.includes(item.key) : undefined"
      :aria-level="item.level"
      :aria-selected="multiple ? isChecked(item.key) : undefined"
      @click="handleItemClick(item)"
    >
      <!-- Expand/collapse chevron -->
      <button
        v-if="item.hasChildren"
        type="button"
        tabindex="-1"
        class="inline-flex size-5 shrink-0 items-center justify-center rounded-sm hover:bg-accent"
        @click.stop="toggleExpand(item.key)"
      >
        <ChevronRight
          class="size-3.5 text-muted-foreground transition-transform duration-150"
          :class="{ 'rotate-90': effectiveExpanded.includes(item.key) }"
        />
      </button>
      <div v-else class="size-5 shrink-0" />

      <!-- Checkbox (multi-select mode) -->
      <Checkbox
        v-if="multiple && !(isItemDisabled?.(item.value) ?? false)"
        :model-value="isChecked(item.key)"
        class="shrink-0"
        @click.stop
        @update:model-value="toggleKey(item.key)"
      />

      <!-- Item content -->
      <div class="min-w-0 flex-1">
        <slot
          name="item"
          :item="item.value"
          :level="item.level"
          :is-expanded="effectiveExpanded.includes(item.key)"
          :has-children="item.hasChildren"
        >
          <span class="truncate">{{ getLabel(item.value) }}</span>
        </slot>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, type HTMLAttributes } from 'vue';
import { ChevronRight } from 'lucide-vue-next';

import { Checkbox } from '@/Components/ui/checkbox';
import { cn } from '@/Utils/Shadcn/utils';

export interface FlatTreeItem {
  key: string;
  value: Record<string, any>;
  level: number;
  hasChildren: boolean;
}

const props = withDefaults(defineProps<{
  /** Hierarchical data items */
  items: Record<string, any>[];
  /** Function to extract a unique key from an item */
  getKey: (item: Record<string, any>) => string;
  /** Function to extract children from an item */
  getChildren?: (item: Record<string, any>) => Record<string, any>[] | undefined;
  /** Function to extract label text from an item (used for default rendering and filtering) */
  getLabel?: (item: Record<string, any>) => string;
  /** Selected/checked keys (v-model) */
  modelValue?: (string | number)[];
  /** Controlled expanded keys */
  expanded?: string[];
  /** Initial expanded keys when uncontrolled */
  defaultExpanded?: string[];
  /** Expand all branches initially */
  defaultExpandAll?: boolean;
  /** Enable checkboxes for multi-selection */
  multiple?: boolean;
  /** Search filter string — shows matching branches, auto-expands ancestors */
  filter?: string;
  /** Per-item function to disable checkbox */
  isItemDisabled?: (item: Record<string, any>) => boolean;
  class?: HTMLAttributes['class'];
}>(), {
  getChildren: (item: any) => item.children,
  getLabel: (item: any) => item.label ?? item.name ?? '',
});

const emit = defineEmits<{
  'update:modelValue': [(string | number)[]];
  'update:expanded': [string[]];
  /** Emitted when a non-disabled leaf item is clicked (useful for single-select mode) */
  'select': [item: Record<string, any>, key: string];
}>();

defineSlots<{
  item?: (props: {
    item: Record<string, any>;
    level: number;
    isExpanded: boolean;
    hasChildren: boolean;
  }) => any;
}>();

// --- Expanded state ---

function getAllParentKeys(items: Record<string, any>[]): string[] {
  const keys: string[] = [];
  function walk(item: Record<string, any>) {
    const children = props.getChildren(item);
    if (children?.length) {
      keys.push(props.getKey(item));
      children.forEach(walk);
    }
  }
  items.forEach(walk);
  return keys;
}

const userExpanded = ref<string[]>(
  props.defaultExpandAll ? getAllParentKeys(props.items) : (props.defaultExpanded ?? []),
);
const isControlled = computed(() => props.expanded !== undefined);
const currentExpanded = computed({
  get: () => isControlled.value ? props.expanded! : userExpanded.value,
  set: (val) => {
    userExpanded.value = val;
    emit('update:expanded', val);
  },
});

// --- Filter logic ---

function computeFilterState(items: Record<string, any>[], filter: string) {
  const visible = new Set<string>();
  const autoExpanded = new Set<string>();

  function walk(item: Record<string, any>, ancestorKeys: string[]): boolean {
    const key = props.getKey(item);
    const label = props.getLabel(item);
    const children = props.getChildren(item);
    let anyChildMatches = false;
    if (children) {
      for (const child of children) {
        if (walk(child, [...ancestorKeys, key])) {
          anyChildMatches = true;
        }
      }
    }
    if (label.toLowerCase().includes(filter.toLowerCase()) || anyChildMatches) {
      visible.add(key);
      ancestorKeys.forEach((k) => { visible.add(k); autoExpanded.add(k); });
      if (anyChildMatches) {
        autoExpanded.add(key);
      }
      return true;
    }
    return false;
  }

  items.forEach(item => walk(item, []));
  return { visible, autoExpanded };
}

const filterState = computed(() => {
  if (!props.filter?.trim()) {
    return null;
  }
  return computeFilterState(props.items, props.filter.trim());
});

const effectiveExpanded = computed(() => {
  if (filterState.value) {
    return Array.from(filterState.value.autoExpanded);
  }
  return currentExpanded.value;
});

// --- Flatten + filter items ---

const flatItems = computed(() => {
  const result: FlatTreeItem[] = [];
  function flattenLevel(items: Record<string, any>[], level: number) {
    for (const item of items) {
      const key = props.getKey(item);
      const children = props.getChildren(item);
      const hasChildren = !!children?.length;
      result.push({ key, value: item, level, hasChildren });
      if (hasChildren && effectiveExpanded.value.includes(key)) {
        flattenLevel(children!, level + 1);
      }
    }
  }
  flattenLevel(props.items, 1);
  return result;
});

const visibleItems = computed(() => {
  if (!filterState.value) {
    return flatItems.value;
  }
  return flatItems.value.filter(item => filterState.value!.visible.has(item.key));
});

// --- Interactions ---

function toggleExpand(key: string) {
  if (filterState.value) {
    return;
  }
  const current = currentExpanded.value;
  currentExpanded.value = current.includes(key)
    ? current.filter(k => k !== key)
    : [...current, key];
}

function toggleKey(key: string | number) {
  const current = props.modelValue ?? [];
  const strKey = String(key);
  const exists = current.some(k => String(k) === strKey);
  emit('update:modelValue', exists
    ? current.filter(k => String(k) !== strKey)
    : [...current, key]);
}

function isChecked(key: string): boolean {
  return (props.modelValue ?? []).some(k => String(k) === key);
}

function handleItemClick(item: FlatTreeItem) {
  const disabled = props.isItemDisabled?.(item.value) ?? false;

  if (item.hasChildren) {
    toggleExpand(item.key);
    // In single-select mode, also select parent items if not disabled
    if (!props.multiple && !disabled) {
      emit('select', item.value, item.key);
    }
  }
  else if (props.multiple && !disabled) {
    toggleKey(item.key);
  }
  else if (!props.multiple && !disabled) {
    emit('select', item.value, item.key);
  }
}
</script>
