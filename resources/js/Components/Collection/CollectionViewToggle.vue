<template>
  <div
    v-if="views.length > 1"
    role="radiogroup"
    :aria-label="$t('Rodinys')"
    :class="segmentGroupClass"
    data-slot="collection-view-toggle"
  >
    <button
      v-for="option in options"
      :key="option.value"
      type="button"
      role="radio"
      :aria-checked="modelValue === option.value"
      :aria-label="$t(option.label)"
      :title="$t(option.label)"
      :class="segmentVariants({ active: modelValue === option.value })"
      @click="emit('update:modelValue', option.value)"
    >
      <component :is="option.icon" aria-hidden="true" />
    </button>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { List, PanelRight, Table } from 'lucide-vue-next';
import { computed, type Component } from 'vue';

import { segmentGroupClass, segmentVariants } from '@/Components/ui/control';
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
