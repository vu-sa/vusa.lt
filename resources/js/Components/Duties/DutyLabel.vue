<template>
  <span class="inline-flex min-w-0 flex-wrap items-baseline gap-x-1.5 gap-y-0.5">
    <InflectedDutyName :name="duty.name" :holder :use-original-duty-name="useOriginalDutyName" class="font-medium" />
    <span v-if="duty.institution?.name" class="truncate text-xs text-muted-foreground">
      {{ duty.institution.name }}
    </span>
    <Badge v-if="duty.institution?.tenant?.shortname" variant="secondary" class="shrink-0 text-[10px]">
      {{ duty.institution.tenant.shortname }}
    </Badge>
  </span>
</template>

<script setup lang="ts">
import InflectedDutyName from './InflectedDutyName.vue';

import { Badge } from '@/Components/ui/badge';
import type { DutyNameHolder } from '@/Utils/String';

/** Disambiguate a duty with its institution and tenant. */
export interface DutyLabelDuty {
  name: string;
  institution?: {
    name: string;
    tenant?: { shortname?: string | null } | null;
  } | null;
}

export type DutyLabelHolder = DutyNameHolder;

defineProps<{
  duty: DutyLabelDuty;
  /**
   * The person this duty is assigned to. When provided, the duty name is inflected
   * to match them instead of showing the animated gender-flip.
   */
  holder?: DutyLabelHolder | null;
  /** Per-assignment override: keep the stored duty name uninflected. */
  useOriginalDutyName?: boolean;
}>();

</script>
