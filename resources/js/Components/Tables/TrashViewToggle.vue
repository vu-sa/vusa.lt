<template>
  <!--
    Deliberately asymmetric: the active view is the normal state, so its selected
    segment stays neutral and quiet, while the trash view selects into the same
    amber the trash banner uses. An evenly weighted control would suggest the two
    views are equal peers, which they are not.
  -->
  <ToggleGroup
    type="single"
    size="sm"
    :model-value="showDeleted ? 'deleted' : 'active'"
    :class="[
      'gap-0 rounded-md border p-0.5 transition-colors',
      showDeleted
        ? 'border-amber-200 bg-amber-50 dark:border-amber-900/50 dark:bg-amber-950/30'
        : 'border-transparent bg-zinc-100/70 dark:bg-zinc-800/50',
    ]"
    @update:model-value="handleChange"
  >
    <ToggleGroupItem
      value="active"
      class="h-7 gap-1.5 px-2.5 text-xs font-normal text-muted-foreground data-[state=on]:bg-white data-[state=on]:font-medium data-[state=on]:text-foreground data-[state=on]:shadow-xs dark:data-[state=on]:bg-zinc-900"
      data-testid="show-active-toggle"
    >
      {{ $t('trash.active_records') }}
    </ToggleGroupItem>
    <ToggleGroupItem
      value="deleted"
      class="h-7 gap-1.5 px-2.5 text-xs font-normal text-muted-foreground hover:text-amber-900 data-[state=on]:bg-amber-100 data-[state=on]:font-medium data-[state=on]:text-amber-900 dark:hover:text-amber-100 dark:data-[state=on]:bg-amber-900/40 dark:data-[state=on]:text-amber-100"
      data-testid="show-deleted-toggle"
    >
      <Trash2Icon class="size-3.5" />
      {{ $t('trash.deleted_records') }}
      <span
        v-if="hasDeletedRecords"
        class="rounded-full bg-black/5 px-1.5 py-px text-[10px] leading-4 font-medium tabular-nums dark:bg-white/10"
      >
        {{ deletedCount }}
      </span>
    </ToggleGroupItem>
  </ToggleGroup>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Trash2Icon } from 'lucide-vue-next';

import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';

const props = defineProps<{
  showDeleted?: boolean;
  deletedCount?: number;
}>();

const emit = defineEmits<{
  (e: 'update:showDeleted', showDeleted: boolean): void;
}>();

/** An empty trash needs no count — the zero adds noise without informing. */
const hasDeletedRecords = computed(() => (props.deletedCount ?? 0) > 0);

/**
 * A single-select toggle group emits an empty value when the active segment is
 * clicked again. The table is always in one of the two views, so treat that as a
 * no-op rather than letting it fall through to "active".
 */
const handleChange = (value: string | string[] | undefined) => {
  if (typeof value !== 'string' || value === '') {
    return;
  }

  emit('update:showDeleted', value === 'deleted');
};
</script>
