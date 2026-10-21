<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button
        type="button"
        size="xs"
        variant="ghost"
        voice="sentence"
        class="h-6 gap-1 px-1.5 pointer-coarse:h-11"
        data-slot="gantt-sort-by"
        data-tour="gantt-sort-by"
        :aria-label="$t('visak.gantt.sort_by_current', { option: label })"
      >
        <ArrowUpDown class="size-3.5" aria-hidden="true" />
        {{ label }}
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" class="w-56">
      <DropdownMenuLabel>{{ $t('visak.gantt.sort_by') }}</DropdownMenuLabel>
      <DropdownMenuSeparator />
      <DropdownMenuRadioGroup :model-value @update:model-value="value => emit('update:modelValue', value as GanttSortBy)">
        <DropdownMenuRadioItem v-for="option in GANTT_SORT_OPTIONS" :key="option" :value="option">
          {{ $t(`visak.gantt.sort_by_options.${option}`) }}
        </DropdownMenuRadioItem>
      </DropdownMenuRadioGroup>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowUpDown } from '@lucide/vue';

import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuRadioGroup,
  DropdownMenuRadioItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { GANTT_SORT_OPTIONS, type GanttSortBy } from '@/Pages/Admin/Dashboard/Composables/useGanttSettings';

const props = defineProps<{
  modelValue: GanttSortBy;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: GanttSortBy];
}>();

const label = computed(() => $t(`visak.gantt.sort_by_short.${props.modelValue}`));
</script>
