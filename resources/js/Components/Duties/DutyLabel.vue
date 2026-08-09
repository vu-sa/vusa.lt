<template>
  <span class="inline-flex min-w-0 flex-wrap items-baseline gap-x-1.5 gap-y-0.5">
    <InflectedDutyName :name="duty.name" class="font-medium" />
    <span v-if="duty.institution?.name" class="truncate text-xs text-muted-foreground">
      {{ duty.institution.name }}
    </span>
    <Badge v-if="duty.institution?.tenant?.shortname" variant="secondary" class="shrink-0 text-[10px]">
      {{ duty.institution.tenant.shortname }}
    </Badge>
  </span>
</template>

<script setup lang="ts">
import { Badge } from '@/Components/ui/badge';
import InflectedDutyName from './InflectedDutyName.vue';

/**
 * The shared "which duty, in which institution" label — a bare duty name is
 * unattributable once the same name repeats across institutions (which it does
 * constantly: "Studentų atstovas" alone exists in 50+ institutions). Reused
 * anywhere a duty is picked, listed, or shown outside its own institution's
 * context, mirroring the name+institution+tenant pattern already used in
 * DutySummaryCard.vue and IndexDuty.vue's institution column.
 *
 * Deliberately not a link — callers that need one (e.g. DutySummaryCard) wrap
 * this or roll their own; this component only needs to work equally well as
 * plain text, a table cell, or a select-option label.
 */
export interface DutyLabelDuty {
  name: string;
  institution?: {
    name: string;
    tenant?: { shortname?: string | null } | null;
  } | null;
}

defineProps<{
  duty: DutyLabelDuty;
}>();
</script>
