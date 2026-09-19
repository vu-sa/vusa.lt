<template>
  <div
    v-if="views.length > 1"
    role="radiogroup"
    :aria-label="$t('Rodinys')"
    class="inline-flex h-9 border border-border bg-background p-0.5 pointer-coarse:h-11"
    data-slot="collection-view-toggle"
  >
    <button
      v-for="option in options"
      :key="option.value"
      type="button"
      role="radio"
      :aria-checked="modelValue === option.value"
      :aria-label="$t(option.label)"
      :class="[
        'inline-flex items-center gap-1.5 px-2.5 text-sm font-medium transition-colors',
        'focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-ring',
        modelValue === option.value
          ? 'bg-brand-fill text-brand-foreground'
          : 'text-muted-foreground hover:text-foreground',
      ]"
      @click="emit('update:modelValue', option.value)"
    >
      <component :is="option.icon" class="size-4" aria-hidden="true" />
      <span class="hidden lg:inline">{{ $t(option.label) }}</span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { List, PanelRight, Table } from 'lucide-vue-next';
import { computed, type Component } from 'vue';

import type { CollectionViewMode } from '@/Composables/useCollectionView';

const props = defineProps<{
  modelValue: CollectionViewMode;
  /** Views this viewport can show; a single one hides the control. */
  views: CollectionViewMode[];
}>();

const emit = defineEmits<{
  'update:modelValue': [view: CollectionViewMode];
}>();

const ALL: { value: CollectionViewMode; label: string; icon: Component }[] = [
  { value: 'rows', label: 'Eilutės', icon: List },
  { value: 'table', label: 'Lentelė', icon: Table },
  { value: 'preview', label: 'Peržiūra', icon: PanelRight },
];

const options = computed(() => ALL.filter(option => props.views.includes(option.value)));
</script>
