<script setup lang="ts">
import { computed } from 'vue';

import SectionCard from '@/Components/Patterns/SectionCard.vue';
import { handoverFields, type HandoverNoteData, type WritableHandoverDuty } from '@/Types/learning';
import { formatDateTime } from '@/Utils/dateTime';
import HandoverEditor from './HandoverEditor.vue';

const props = withDefaults(defineProps<{ notes: HandoverNoteData[]; writable?: WritableHandoverDuty[] }>(), { writable: () => [] });

type TimelineEntry = { key: string; note: HandoverNoteData | null; editor: WritableHandoverDuty | null; termStart: string; label: string };
type SeatTimeline = { id: string; title: string; entries: TimelineEntry[] };

const seats = computed(() => {
  const result = new Map<string, SeatTimeline>();
  for (const duty of props.writable) {
    result.set(duty.dutyId, { id: duty.dutyId, title: duty.title, entries: [{
      key: duty.note?.id ?? `new-${duty.dutyId}`, note: duty.note, editor: duty,
      termStart: duty.termStart, label: duty.cadenceLabel,
    }] });
  }
  for (const note of [...props.writable.flatMap(duty => duty.notes), ...props.notes]) {
    let seat = result.get(note.seatDutyId);
    if (!seat) {
      seat = { id: note.seatDutyId, title: note.seatTitle, entries: [] };
      result.set(seat.id, seat);
    }
    if (!seat.entries.some(entry => entry.key === note.id)) {
      seat.entries.push({ key: note.id, note, editor: null, termStart: note.termStart ?? '', label: note.cadenceLabel });
    }
  }
  for (const seat of result.values()) {
    seat.entries.sort((a, b) => b.termStart.localeCompare(a.termStart)
      || (b.note?.createdAt ?? '').localeCompare(a.note?.createdAt ?? ''));
  }
  return [...result.values()];
});
</script>

<template>
  <div class="space-y-6" data-slot="handover-notes">
    <SectionCard v-for="seat in seats" :key="seat.id" :title="seat.title">
      <ol class="relative space-y-8 border-l border-border pl-5" data-slot="handover-timeline">
        <li v-for="entry in seat.entries" :key="entry.key" class="relative space-y-4" data-slot="handover-entry">
          <span class="absolute -left-[1.4rem] top-1.5 size-2 bg-muted-foreground/60" aria-hidden="true" />
          <header class="space-y-1">
            <h3 class="text-sm font-semibold tabular-nums">{{ entry.label }}</h3>
            <p class="text-xs text-muted-foreground">
              {{ entry.editor?.author.name ?? entry.note?.author.name }}
              <template v-if="entry.note?.updatedAt || entry.note?.createdAt">
                · <time :datetime="entry.note.updatedAt ?? entry.note.createdAt ?? undefined">{{ formatDateTime(entry.note.updatedAt ?? entry.note.createdAt) }}</time>
              </template>
            </p>
            <p v-if="entry.note && entry.note.dutyId !== seat.id" class="text-xs text-muted-foreground">{{ entry.note.duty }}</p>
          </header>
          <HandoverEditor v-if="entry.editor" :key="entry.editor.note?.id ?? entry.key" :duty="entry.editor" />
          <dl v-else-if="entry.note" class="max-w-2xl space-y-4" data-slot="handover-sections">
            <template v-for="field in handoverFields" :key="field">
              <div v-if="entry.note[field]" class="space-y-1">
                <dt class="text-xs font-bold uppercase tracking-wide text-muted-foreground">{{ $t(`learning_people.fields.${field}.label`) }}</dt>
                <dd class="whitespace-pre-wrap break-words text-sm text-foreground">{{ entry.note[field] }}</dd>
              </div>
            </template>
          </dl>
        </li>
      </ol>
    </SectionCard>
  </div>
</template>
