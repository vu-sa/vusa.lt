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
      content-class="w-[var(--reka-popper-anchor-width)]"
      item-class="h-8 overflow-hidden"
      :estimate-size="32"
    >
      <template #prefix="{ selected }">
        <Icon v-if="selected" :icon="`fluent:${selected.value}`" class="size-4 shrink-0" />
      </template>
      <template #option="{ item }">
        <div class="flex min-w-0 flex-1 items-center gap-2">
          <Icon :icon="`fluent:${item.value}`" class="size-4 shrink-0" />
          <span class="truncate">{{ item.label }}</span>
        </div>
      </template>
    </SingleSelect>
    <Button v-if="props.icon" type="button" variant="ghost" size="icon-xs" :aria-label="$t('forms.remove')" @click="emit('update:icon', null)">
      <X class="size-3.5" />
    </Button>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { X } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { SingleSelect } from '@/Components/ui/single-select';

interface IconOption {
  value: string;
  label: string;
}

const props = defineProps<{
  icon: string | null;
}>();

const emit = defineEmits<(e: 'update:icon', value: string | null) => void>();

const iconOptions = ref<IconOption[]>([]);

// Module-level cache — every mount of this component (there are 3+ call sites)
// previously re-fetched the same ~19.7k-entry Iconify collection with no error
// handling. Cached in-flight/settled promise so the network round-trip happens once
// per page load, and a failed fetch leaves the select merely empty rather than
// throwing an unhandled rejection.
let iconCollectionPromise: Promise<IconOption[]> | null = null;

const getIconOptions = (): Promise<IconOption[]> => {
  if (!iconCollectionPromise) {
    iconCollectionPromise = fetch('https://api.iconify.design/collection?prefix=fluent')
      .then(response => response.json())
      .then(iconData => (iconData.uncategorized?.map((icon: string) => ({ value: icon, label: icon })) ?? []) as IconOption[])
      .catch(() => []);
  }
  return iconCollectionPromise;
};

getIconOptions().then((icons) => {
  iconOptions.value = icons;
});

const selectedIcon = computed({
  get: () => {
    if (!props.icon) return null;
    return iconOptions.value.find(opt => opt.value === props.icon) ?? { value: props.icon, label: props.icon };
  },
  set: (val: IconOption | null) => {
    emit('update:icon', val?.value ?? null);
  },
});
</script>
