<template>
  <div class="space-y-2">
    <Label>
      Ikona. <span class="text-zinc-400"> Ikonų ieškokite <a target="_blank" class="font-bold underline"
          href="https://icon-sets.iconify.design/fluent/">čia</a>. Suradę reikiamą ikoną pasirinkite iš
        sąrašo. </span>
    </Label>
    <div class="flex items-center gap-2">
      <Icon v-if="iconRef" :icon="`fluent:${iconRef}`" class="size-5 shrink-0" />
      <Combobox v-model="iconRef" @update:model-value="(val) => $emit('update:icon', val)">
        <ComboboxAnchor class="w-full">
          <ComboboxInput v-model="searchTerm" placeholder="Pasirinkti..." />
          <ComboboxTrigger />
        </ComboboxAnchor>
        <ComboboxList>
          <ComboboxViewport class="max-h-60">
            <ComboboxEmpty>Nerasta</ComboboxEmpty>
            <ComboboxVirtualizer
              v-slot="{ option }"
              :options="filteredOptions"
              :estimate-size="32"
              :text-content="(opt: IconOption) => opt.label"
            >
              <ComboboxItem :value="option.value" class="flex items-center gap-2">
                <Icon :icon="`fluent:${option.value}`" class="size-4 shrink-0" />
                <span class="truncate">{{ option.label }}</span>
              </ComboboxItem>
            </ComboboxVirtualizer>
          </ComboboxViewport>
        </ComboboxList>
      </Combobox>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { computed, ref } from 'vue';
import { ComboboxVirtualizer } from 'reka-ui';

import { Label } from '@/Components/ui/label';
import {
  Combobox,
  ComboboxAnchor,
  ComboboxEmpty,
  ComboboxInput,
  ComboboxItem,
  ComboboxList,
  ComboboxViewport,
  ComboboxTrigger,
} from '@/Components/ui/combobox';

interface IconOption {
  value: string;
  label: string;
}

const props = defineProps<{
  icon: string | null;
}>();

defineEmits<{
  (e: 'update:icon', value: string): void
}>()

const iconRef = ref(props.icon);
const searchTerm = ref('');

const getIconOptions = async () => {
  const response = await fetch("https://api.iconify.design/collection?prefix=fluent");
  const iconData = await response.json();
  return iconData;
};

const icons = await getIconOptions();

const iconOptions = computed<IconOption[]>(() => {
  return icons.uncategorized?.map((icon: string) => ({
    value: icon,
    label: icon,
  })) ?? [];
});

const filteredOptions = computed(() => {
  if (!searchTerm.value) {
    return iconOptions.value;
  }
  const term = searchTerm.value.toLowerCase();
  return iconOptions.value.filter(opt => opt.label.toLowerCase().includes(term));
});
</script>
