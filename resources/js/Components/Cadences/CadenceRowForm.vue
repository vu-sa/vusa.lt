<template>
  <div data-slot="cadence-row-form" class="grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
    <div v-for="side in SIDES" :key="side" class="space-y-1">
      <Label :for="`${uid}-${side}`" class="text-xs text-muted-foreground">
        {{ $t(`cadences.fields.${side}_date`) }}
      </Label>

      <!-- An anchored boundary is derived from the sitting, so the field reports it rather
           than accepting one: the server takes the date from the meeting either way. -->
      <Input
        :id="`${uid}-${side}`"
        v-model="draft[`${side}_date`]"
        type="date"
        :disabled="Boolean(anchorFor(side))"
      />

      <div v-if="anchorable" class="flex items-center gap-1">
        <template v-if="anchorFor(side)">
          <CalendarClock class="size-3 shrink-0 text-muted-foreground" />
          <span class="min-w-0 truncate text-[11px] text-muted-foreground" :title="anchorTitle(anchorFor(side)!)">
            {{ anchorFor(side)!.title ?? $t('cadences.fields.anchor_untitled') }}
          </span>
          <!-- Only when the sitting belongs to another body: for its own, the name is noise. -->
          <Badge v-if="foreignInstitution(anchorFor(side)!)" variant="outline" class="shrink-0 text-[10px] font-normal">
            {{ foreignInstitution(anchorFor(side)!) }}
          </Badge>
          <Button
            type="button"
            size="icon-xs"
            variant="ghost"
            :aria-label="$t('cadences.actions.unlink_meeting')"
            @click="clearAnchor(side)"
          >
            <X class="size-3" />
          </Button>
        </template>

        <Button
          v-else
          type="button"
          size="xs"
          variant="ghost"
          class="h-6 px-1 text-[11px] text-muted-foreground"
          @click="picking = side"
        >
          <CalendarClock class="size-3" />
          {{ $t('cadences.actions.link_meeting') }}
        </Button>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <Button type="button" size="sm" :disabled="!isComplete || processing" @click="emit('save', { ...draft })">
        {{ $t('cadences.actions.save') }}
      </Button>
      <Button type="button" size="sm" variant="ghost" :disabled="processing" @click="emit('cancel')">
        {{ $t('cadences.actions.cancel') }}
      </Button>
    </div>

    <CollectionSelectDialog
      v-if="anchorable && picking"
      :open="true"
      collection="meetings"
      :title="$t('cadences.actions.link_meeting')"
      :description="$t('cadences.fields.anchor_hint')"
      @update:open="open => { if (!open) picking = null; }"
      @confirm="onPicked"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { useId } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { CalendarClock, X } from 'lucide-vue-next';

import { CollectionSelectDialog } from '@/Features/Admin/AdminSearch/Components/Select';
import type { NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

type Side = 'start' | 'end';

/** The fields of a Typesense meeting document this form reads. */
interface MeetingHit {
  start_time?: number | string;
  institution_id?: string | null;
  institution_name_lt?: string | null;
  institution_name_en?: string | null;
}

const SIDES: Side[] = ['start', 'end'];

/** Enough to name the sitting a boundary came from; the date itself lives on the draft. */
export interface CadenceAnchor {
  id: string;
  title: string | null;
  start_time: string;
  /** The sitting's own institution — a boundary may be taken from another body's meeting. */
  institution_id?: string | null;
  institution_name?: string | null;
}

export interface CadenceDraft {
  start_date: string;
  end_date: string;
  start_meeting_id?: string | null;
  end_meeting_id?: string | null;
}

const props = withDefaults(defineProps<{
  modelValue?: CadenceDraft | null;
  /** The institution whose sittings may be anchored to. Null is the global ladder, which has none. */
  institutionId?: string | null;
  anchors?: { start: CadenceAnchor | null; end: CadenceAnchor | null };
  processing?: boolean;
}>(), {
  modelValue: null,
  institutionId: null,
  anchors: () => ({ start: null, end: null }),
  processing: false,
});

const emit = defineEmits<{
  save: [value: CadenceDraft];
  cancel: [];
}>();

const uid = useId();
const picking = ref<Side | null>(null);

const draft = reactive<CadenceDraft>({
  start_date: props.modelValue?.start_date ?? '',
  end_date: props.modelValue?.end_date ?? '',
  start_meeting_id: props.modelValue?.start_meeting_id ?? null,
  end_meeting_id: props.modelValue?.end_meeting_id ?? null,
});

/** Anchors picked in this session, so a fresh choice names itself before the page reloads. */
const picked = reactive<{ start: CadenceAnchor | null; end: CadenceAnchor | null }>({ start: null, end: null });

const anchorable = computed(() => Boolean(props.institutionId));

function anchorFor(side: Side): CadenceAnchor | null {
  return draft[`${side}_meeting_id`] ? (picked[side] ?? props.anchors[side]) : null;
}

/** The owning body's own sittings need no label; anybody else's is worth naming. */
function foreignInstitution(anchor: CadenceAnchor): string | null {
  return anchor.institution_id && anchor.institution_id !== props.institutionId
    ? (anchor.institution_name ?? null)
    : null;
}

function anchorTitle(anchor: CadenceAnchor): string {
  const institution = foreignInstitution(anchor);

  return [anchor.title ?? $t('cadences.fields.anchor_untitled'), institution].filter(Boolean).join(' — ');
}

// The list reuses one form instance per section, so switching which row is being edited
// changes `modelValue` without remounting — without this the previous row's dates stay.
watch(() => props.modelValue, (next) => {
  draft.start_date = next?.start_date ?? '';
  draft.end_date = next?.end_date ?? '';
  draft.start_meeting_id = next?.start_meeting_id ?? null;
  draft.end_meeting_id = next?.end_meeting_id ?? null;
  picked.start = null;
  picked.end = null;
});

function clearAnchor(side: Side): void {
  draft[`${side}_meeting_id`] = null;
  picked[side] = null;
}

/**
 * The date is filled in optimistically so the row reads right straight away; the server
 * re-derives it from the meeting regardless, and that value is the one that is stored.
 */
function onPicked(hits: NormalizedSearchHit[]): void {
  const side = picking.value;
  const hit = hits[0];
  picking.value = null;

  if (!side || !hit) return;

  const raw = hit.raw as MeetingHit;
  const startTime = toDateString(raw.start_time);

  draft[`${side}_meeting_id`] = hit.recordId;
  picked[side] = {
    id: hit.recordId,
    title: hit.title ?? null,
    start_time: String(raw.start_time ?? ''),
    institution_id: raw.institution_id ?? null,
    institution_name: raw.institution_name_lt || raw.institution_name_en || null,
  };

  if (startTime) draft[`${side}_date`] = startTime;
}

/** Typesense stores the sitting time as a unix timestamp in seconds. */
function toDateString(value: number | string | undefined): string | null {
  if (value === undefined || value === '') return null;

  const date = new Date(Number(value) * 1000);

  return Number.isNaN(date.getTime()) ? null : date.toISOString().slice(0, 10);
}

// The server also enforces `end_date` after `start_date`; this only keeps the button
// from submitting an obviously empty or inverted row.
const isComplete = computed(() => Boolean(draft.start_date && draft.end_date)
  && draft.end_date > draft.start_date);
</script>
