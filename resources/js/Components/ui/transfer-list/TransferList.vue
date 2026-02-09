<template>
  <div :class="cn('flex gap-2', props.class)">
    <!-- Source Panel -->
    <div class="flex flex-1 flex-col rounded-lg border">
      <div v-if="sourceFilterable" class="border-b p-2">
        <Input v-model="sourceFilter" placeholder="Ieškoti..." class="h-8" />
      </div>
      <ScrollArea class="h-72">
        <slot
          name="source"
          :options="filteredSourceOptions"
          :selected-values="modelValue"
          :toggle
          :filter="sourceFilter"
        >
          <div class="p-1">
            <div
              v-for="option in filteredSourceOptions"
              :key="option.value"
              class="flex cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 hover:bg-accent/50"
              @click="toggle(option.value)"
            >
              <Checkbox
                :model-value="isSelected(option.value)"
                class="shrink-0"
                @click.stop
                @update:model-value="toggle(option.value)"
              />
              <div class="min-w-0 flex-1 text-sm">
                <slot name="source-label" :option>
                  {{ option.label }}
                </slot>
              </div>
            </div>
          </div>
        </slot>
      </ScrollArea>
      <div class="border-t px-3 py-1.5 text-xs text-muted-foreground">
        {{ modelValue?.length ?? 0 }} / {{ options.length }} pasirinkta
      </div>
    </div>

    <!-- Target Panel -->
    <div class="flex flex-1 flex-col rounded-lg border">
      <div v-if="targetFilterable" class="border-b p-2">
        <Input v-model="targetFilter" placeholder="Ieškoti..." class="h-8" />
      </div>
      <div v-else class="border-b px-3 py-2 text-sm font-medium">
        Pasirinkta
      </div>
      <ScrollArea class="h-72">
        <div class="p-1">
          <div
            v-for="option in selectedOptions"
            :key="option.value"
            class="flex items-center gap-2 rounded-sm px-2 py-1.5 hover:bg-accent/50"
          >
            <div class="min-w-0 flex-1 text-sm">
              <slot name="target-label" :option>
                {{ option.label }}
              </slot>
            </div>
            <button
              type="button"
              class="shrink-0 rounded-sm p-0.5 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
              @click="remove(option.value)"
            >
              <X class="size-3.5" />
            </button>
          </div>
          <div v-if="selectedOptions.length === 0" class="px-2 py-8 text-center text-sm text-muted-foreground">
            Nėra pasirinktų elementų
          </div>
        </div>
      </ScrollArea>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, type HTMLAttributes } from 'vue';
import { X } from 'lucide-vue-next';

import { Input } from '@/Components/ui/input';
import { Checkbox } from '@/Components/ui/checkbox';
import { ScrollArea } from '@/Components/ui/scroll-area';
import { cn } from '@/Utils/Shadcn/utils';

export interface TransferListOption {
  value: string | number;
  label: string;
  [key: string]: any;
}

const props = withDefaults(defineProps<{
  /** Selected values (v-model) */
  modelValue: (string | number)[];
  /** Flat option list — used for default source rendering and target panel */
  options: TransferListOption[];
  /** Show search input in source panel */
  sourceFilterable?: boolean;
  /** Show search input in target panel */
  targetFilterable?: boolean;
  class?: HTMLAttributes['class'];
}>(), {
  sourceFilterable: true,
  targetFilterable: false,
});

const emit = defineEmits<{
  'update:modelValue': [(string | number)[]];
}>();

defineSlots<{
  /** Override the entire source panel content. Receives filter string, selected values, and toggle function. */
  'source'?: (props: {
    options: TransferListOption[];
    selectedValues: (string | number)[];
    toggle: (value: string | number) => void;
    filter: string;
  }) => any;
  /** Customize individual source item label */
  'source-label'?: (props: { option: TransferListOption }) => any;
  /** Customize individual target item label */
  'target-label'?: (props: { option: TransferListOption }) => any;
}>();

const sourceFilter = ref('');
const targetFilter = ref('');

const selectedSet = computed(() => new Set((props.modelValue ?? []).map(String)));

const filteredSourceOptions = computed(() => {
  if (!sourceFilter.value) {
    return props.options;
  }
  const f = sourceFilter.value.toLowerCase();
  return props.options.filter(o => o.label.toLowerCase().includes(f));
});

const selectedOptions = computed(() => {
  const selected = props.options.filter(o => selectedSet.value.has(String(o.value)));
  if (!targetFilter.value) {
    return selected;
  }
  const f = targetFilter.value.toLowerCase();
  return selected.filter(o => o.label.toLowerCase().includes(f));
});

function isSelected(value: string | number): boolean {
  return selectedSet.value.has(String(value));
}

function toggle(value: string | number) {
  const strValue = String(value);
  if (selectedSet.value.has(strValue)) {
    emit('update:modelValue', props.modelValue.filter(v => String(v) !== strValue));
  }
  else {
    emit('update:modelValue', [...props.modelValue, value]);
  }
}

function remove(value: string | number) {
  emit('update:modelValue', props.modelValue.filter(v => String(v) !== String(value)));
}
</script>
