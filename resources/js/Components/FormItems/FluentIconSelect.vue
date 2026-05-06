<template>
  <div class="flex items-center gap-2">
    <Icon v-if="selectedIcon" :icon="`fluent:${selectedIcon.value}`" class="size-5 shrink-0" />
    <SingleSelect
      v-model="selectedIcon"
      :options="iconOptions"
      label-field="label"
      value-field="value"
      placeholder="Pasirinkti ikoną..."
      empty-text="Nerasta"
    >
      <template #prefix="{ selected }">
        <Icon v-if="selected" :icon="`fluent:${selected.value}`" class="size-4 shrink-0" />
      </template>
      <template #option="{ item }">
        <div class="flex items-center gap-2">
          <Icon :icon="`fluent:${item.value}`" class="size-4 shrink-0" />
          <span class="truncate">{{ item.label }}</span>
        </div>
      </template>
    </SingleSelect>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { computed, ref, watch } from 'vue';

import { SingleSelect } from '@/Components/ui/single-select';

interface IconOption {
  value: string;
  label: string;
}

const props = defineProps<{
  icon: string | null;
}>();

const emit = defineEmits<(e: 'update:icon', value: string) => void>();

const iconOptions = ref<IconOption[]>([]);

const getIconOptions = async () => {
  const response = await fetch('https://api.iconify.design/collection?prefix=fluent');
  const iconData = await response.json();
  return iconData;
};

getIconOptions().then((icons) => {
  iconOptions.value = icons.uncategorized?.map((icon: string) => ({
    value: icon,
    label: icon,
  })) ?? [];
});

const selectedIcon = computed({
  get: () => {
    if (!props.icon) return null;
    return iconOptions.value.find(opt => opt.value === props.icon) ?? { value: props.icon, label: props.icon };
  },
  set: (val: IconOption | null) => {
    if (val?.value) {
      emit('update:icon', val.value);
    }
  },
});
</script>
