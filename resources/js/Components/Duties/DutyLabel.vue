<template>
  <span class="inline-flex min-w-0 flex-wrap items-baseline gap-x-1.5 gap-y-0.5">
    <span v-if="holder" class="font-medium">{{ inflectedName }}</span>
    <InflectedDutyName v-else :name="duty.name" class="font-medium" />
    <span v-if="duty.institution?.name" class="truncate text-xs text-muted-foreground">
      {{ duty.institution.name }}
    </span>
    <Badge v-if="duty.institution?.tenant?.shortname" variant="secondary" class="shrink-0 text-[10px]">
      {{ duty.institution.tenant.shortname }}
    </Badge>
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import InflectedDutyName from './InflectedDutyName.vue';

import { Badge } from '@/Components/ui/badge';
import { changeDutyNameEndings } from '@/Utils/String';

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
 *
 * Without a `holder`, the name renders through `InflectedDutyName` — the
 * animated gender-flip that signals a duty is not yet tied to a person. Pass a
 * `holder` when the duty belongs to someone and the ending should settle to the
 * form that matches their pronouns/name (mirrors `changeDutyNameEndings` as used
 * by ContactWithPhoto.vue), e.g. "Koordinatorius" → "Koordinatorė".
 */
export interface DutyLabelDuty {
  name: string;
  institution?: {
    name: string;
    tenant?: { shortname?: string | null } | null;
  } | null;
}

export interface DutyLabelHolder {
  name?: string | null;
  /** Locale-resolved pronoun string (e.g. "jis/jo"); drives the ending inflection. */
  pronouns?: string | null;
}

const props = defineProps<{
  duty: DutyLabelDuty;
  /**
   * The person this duty is assigned to. When provided, the duty name is inflected
   * to match them instead of showing the animated gender-flip.
   */
  holder?: DutyLabelHolder | null;
  /** Per-assignment override: keep the stored duty name uninflected. */
  useOriginalDutyName?: boolean;
}>();

const inflectedName = computed(() => {
  const { locale } = usePage().props.app;
  // changeDutyNameEndings only reads contact.name; the holder carries just that.
  const contact = props.holder ? { name: props.holder.name ?? '' } as App.Entities.User : null;
  return changeDutyNameEndings(
    contact,
    props.duty.name,
    locale,
    props.holder?.pronouns ?? '',
    props.useOriginalDutyName ?? false,
  );
});
</script>
