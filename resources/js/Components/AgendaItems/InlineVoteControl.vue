<template>
  <div class="flex items-center gap-3">
    <!-- Label with tooltip - fixed width for alignment -->
    <div class="flex items-center gap-1 w-48 sm:w-56 shrink-0">
      <span class="text-xs text-zinc-600 dark:text-zinc-400 truncate">{{ label }}</span>
      <FieldTooltip v-if="tooltip" :text="tooltip" />
    </div>
    
    <!-- Button group using ShadcnVue -->
    <ButtonGroup orientation="horizontal" class="shrink-0">
      <Button
        v-for="option in options"
        :key="option.value"
        type="button"
        size="sm"
        :variant="value === option.value ? 'default' : 'outline'"
        :class="[
          'h-7 px-2 text-xs gap-1',
          value === option.value && getActiveClass(option.color)
        ]"
        @click="handleClick(option.value)"
      >
        <component :is="option.icon" class="h-3 w-3 shrink-0" />
        <span class="hidden sm:inline">{{ option.label }}</span>
      </Button>
    </ButtonGroup>
    
    <!-- Clear button (only shown when value is set) -->
    <Button 
      v-if="value && showClear"
      variant="ghost"
      size="icon"
      class="h-7 w-7 shrink-0 text-zinc-400 hover:text-zinc-600"
      :title="$t('Išvalyti pasirinkimą')"
      @click="$emit('update', null)"
    >
      <X class="h-3 w-3" />
    </Button>
    <!-- Spacer when no clear button to maintain alignment -->
    <div v-else class="w-7 shrink-0" />
  </div>
</template>

<script setup lang="ts">
import { type Component } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { X } from 'lucide-vue-next';
import FieldTooltip from '@/Components/ui/FieldTooltip.vue';
import { Button } from '@/Components/ui/button';
import { ButtonGroup } from '@/Components/ui/button-group';

interface VoteOption {
  value: string;
  icon: Component;
  label: string;
  color: 'green' | 'red' | 'zinc';
}

const props = withDefaults(defineProps<{
  label: string;
  tooltip?: string;
  value?: string | null;
  options: VoteOption[];
  showClear?: boolean;
}>(), {
  showClear: true
});

const emit = defineEmits<{
  update: [value: string | null];
}>();

const handleClick = (optionValue: string) => {
  // Toggle off if clicking the same value
  if (props.value === optionValue) {
    emit('update', null);
  } else {
    emit('update', optionValue);
  }
};

const getActiveClass = (color: 'green' | 'red' | 'zinc'): string => {
  switch (color) {
    case 'green':
      return '!bg-green-100 !text-green-700 !border-green-300 hover:!bg-green-200 dark:!bg-green-900/40 dark:!text-green-400 dark:!border-green-700';
    case 'red':
      return '!bg-red-100 !text-red-700 !border-red-300 hover:!bg-red-200 dark:!bg-red-900/40 dark:!text-red-400 dark:!border-red-700';
    case 'zinc':
      return '!bg-zinc-100 !text-zinc-700 !border-zinc-300 hover:!bg-zinc-200 dark:!bg-zinc-800 dark:!text-zinc-300 dark:!border-zinc-600';
    default:
      return '';
  }
};
</script>
