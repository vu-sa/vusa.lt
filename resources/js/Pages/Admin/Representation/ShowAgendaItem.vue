<template>
  <RecordPage
    v-model:section="section"
    :history-subject="{ type: 'agendaItem', id: agendaItem.id }"
    :title="displayTitle"
    title-voice="sentence"
    :entity-type="ModelEnum.AGENDA_ITEM"
    :facts="recordFacts"
    :sections
    :primary-action
    :overflow-actions
    :navigation
    @action="handleRecordAction"
  >
    <template #identity>
      <span
        class="flex size-14 items-center justify-center border border-border text-2xl font-bold tabular-nums text-foreground sm:size-16 sm:text-3xl"
        :aria-label="`${$t('Punktas')} ${currentPosition}`"
      >
        {{ currentPosition }}
      </span>
    </template>

    <template v-if="hasSubtitle" #subtitle>
      <span v-if="canUpdate && saveStatus !== 'idle'" class="text-sm text-muted-foreground" aria-live="polite" data-slot="save-status">
        <span v-if="saveStatus === 'saving'" class="inline-flex items-center gap-1.5">
          <Loader2 class="size-3.5 animate-spin" />{{ $t('Saugoma…') }}
        </span>
        <span v-else-if="saveStatus === 'dirty'" class="inline-flex items-center gap-1.5 text-status-attention">
          <span class="size-1.5 bg-status-attention" />{{ $t('Neišsaugota') }}
        </span>
        <span v-else-if="saveStatus === 'saved'" class="inline-flex items-center gap-1.5 text-status-success">
          <Check class="size-3.5" />{{ $t('Įrašyta') }}
        </span>
      </span>
      <span v-else-if="!canUpdate" class="text-sm text-muted-foreground">{{ $t('meetings.item.view_only') }}</span>
      <Button
        v-if="canUpdate && !isDesktop"
        variant="outline"
        size="sm"
        voice="sentence"
        class="pointer-coarse:h-11"
        @click="notesOpen = true"
      >
        <NotebookPen class="size-4" />
        {{ $t('meetings.item.notes') }}
      </Button>
    </template>

    <template #navigation-label>
      <Popover>
        <PopoverTrigger as-child>
          <button
            type="button"
            class="min-w-20 px-2 text-center text-sm font-medium text-muted-foreground hover:text-foreground pointer-coarse:min-h-11"
            data-testid="agenda-item-position"
          >
            {{ navigation.position }} / {{ navigation.total }}
          </button>
        </PopoverTrigger>
        <PopoverContent align="end" class="w-80 p-0">
          <ol class="max-h-80 divide-y divide-border overflow-y-auto">
            <li v-for="(item, index) in siblingAgendaItems" :key="item.id">
              <button
                type="button"
                :class="[
                  'flex w-full items-start gap-3 px-3 py-2.5 text-left text-sm transition-colors hover:bg-accent pointer-coarse:min-h-11',
                  item.id === agendaItem.id ? 'bg-accent font-semibold' : undefined,
                ]"
                @click="goTo(item.id)"
              >
                <span class="w-5 shrink-0 text-right tabular-nums text-muted-foreground">{{ index + 1 }}</span>
                <span class="min-w-0 flex-1 truncate">{{ item.title }}</span>
                <CircleDashed v-if="item.missing" class="size-4 shrink-0 text-status-attention" :aria-label="missingFieldsLabel(item.missing)" />
              </button>
            </li>
          </ol>
        </PopoverContent>
      </Popover>
    </template>

    <template #fact-meeting>
      <Link :href="route('meetings.show', agendaItem.meeting_id)" :class="LINK_CLASS">
        {{ meetingLabel || $t('Posėdis') }}
      </Link>
    </template>

    <template #fact-institution>
      <span v-if="!institutions.length">—</span>
      <span v-else class="flex flex-col gap-1">
        <Link
          v-for="institution in institutions"
          :key="institution.id"
          :href="route('institutions.show', institution.id)"
          :class="LINK_CLASS"
        >
          {{ institution.name }}
        </Link>
      </span>
    </template>

    <template #fact-visibility>
      <a
        v-if="publicUrl"
        :href="publicUrl"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex items-center gap-1 text-status-success underline underline-offset-4"
      >
        {{ $t('meetings.record.visible_public') }}
        <ExternalLink class="size-3.5 shrink-0" aria-hidden="true" />
      </a>
      <span v-else>{{ $t('meetings.record.internal_only') }}</span>
    </template>

    <template v-if="canUpdate && !typeOpen" #alert>
      <Button
        variant="ghost"
        size="sm"
        voice="sentence"
        class="text-muted-foreground pointer-coarse:h-11"
        data-testid="agenda-item-change-type"
        @click="typeOpen = true"
      >
        <Shapes class="size-4" />
        {{ $t('meetings.item.change_type') }}
      </Button>
    </template>

    <template #item>
      <div class="space-y-10">
        <AgendaItemBody
          v-model:type-open="typeOpen"
          :form
          :editable="canUpdate"
          :requires-student-perspective
          @manage-votes="votesSheetOpen = true" />

        <section aria-labelledby="agenda-item-description-title" class="space-y-2">
          <h3 id="agenda-item-description-title" :class="LABEL_CLASS">
            {{ $t('meetings.item.description') }}
          </h3>
          <p v-if="descriptionText" class="max-w-prose whitespace-pre-line text-sm leading-relaxed text-foreground">
            {{ descriptionText }}
          </p>
          <p v-else class="text-sm text-muted-foreground">
            {{ $t('meetings.item.description_empty') }}
          </p>
        </section>

        <section v-if="requiresStudentPerspective" aria-labelledby="agenda-item-position-title" class="space-y-2">
          <h3 id="agenda-item-position-title" :class="LABEL_CLASS">
            {{ $t('meetings.item.student_position') }}
          </h3>
          <p v-if="studentPositionText" class="max-w-prose whitespace-pre-line text-sm leading-relaxed text-foreground">
            {{ studentPositionText }}
          </p>
          <p v-else class="text-sm text-muted-foreground">
            {{ $t('meetings.item.student_position_empty') }}
          </p>
        </section>
      </div>
    </template>

    <template v-if="canUpdate && isDesktop" #aside>
      <AgendaItemNotesSidebar :agenda-item-id="agendaItem.id" />
    </template>

    <template #activity>
      <RecordActivity commentable-type="agendaItem" :commentable-id="agendaItem.id" />
    </template>

    <AgendaItemSheetForm
      v-if="canUpdate"
      v-model:open="sheetOpen"
      :form
      :save-then
      :default-start-time="defaultStartTimeFromPreviousItem()"
      :requires-student-perspective
      :is-public="meetingIsPublic"
      :can-delete="abilities.delete"
      @delete="deleteOpen = true"
    />

    <AgendaItemVotesSheetForm
      v-if="canUpdate"
      v-model:open="votesSheetOpen"
      :form
      :save-then
    />

    <Sheet v-if="canUpdate && !isDesktop" v-model:open="notesOpen">
      <SheetContent side="bottom" class="h-[92dvh] max-h-[92dvh] overflow-y-auto p-0">
        <SheetHeader class="border-b border-border px-6 py-4">
          <SheetTitle>{{ $t('meetings.item.notes') }}</SheetTitle>
        </SheetHeader>
        <div class="px-4 py-4">
          <AgendaItemNotesSidebar :agenda-item-id="agendaItem.id" />
        </div>
      </SheetContent>
    </Sheet>

    <ConfirmDialog
      v-model:open="deleteOpen"
      :title="$t('Šalinti darbotvarkės punktą?')"
      :description="$t('meetings.item.delete_description')"
      :confirm-label="$t('meetings.item.delete')"
      destructive
      @confirm="deleteItem"
    />
  </RecordPage>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { useMediaQuery } from '@vueuse/core';
import { getActiveLanguage, trans as $t } from 'laravel-vue-i18n';
import { Check, CircleDashed, Copy, ExternalLink, Globe, Loader2, NotebookPen, PenLine, Shapes, Trash2 } from 'lucide-vue-next';

import AgendaItemBody from '@/Components/AgendaItems/AgendaItemBody.vue';
import AgendaItemNotesSidebar from '@/Components/AgendaItems/AgendaItemNotesSidebar.vue';
import AgendaItemSheetForm from '@/Components/AgendaItems/AgendaItemSheetForm.vue';
import AgendaItemVotesSheetForm from '@/Components/AgendaItems/AgendaItemVotesSheetForm.vue';
import RecordPage, { type RecordAction, type RecordFact, type RecordNavigationContext } from '@/Components/Layouts/RecordPage.vue';
import { missingFieldsLabel, type AgendaItemMissingAction } from '@/Components/Meetings/meetingCompletion';
import { ConfirmDialog } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/Components/ui/sheet';
import { agendaItemStatuses } from '@/Constants/statuses';
import { getAgendaItemStatus } from '@/Composables/useAgendaItemStyling';
import { useAgendaItemAutosave, toTranslatedField, type AgendaItemFormData, type EditableVote, type VoteValue } from '@/Composables/useAgendaItemAutosave';
import RecordActivity from '@/Features/Admin/ActivityLogViewer/RecordActivity.vue';
import { ModelEnum } from '@/Types/enums';
import { formatDate } from '@/Utils/dateTime';

/**
 * `AgendaItemController::show()` sends `toFullArray()`, so the translatable fields arrive as
 * `{lt, en}` maps rather than the localized strings every other surface receives.
 */
type EditableAgendaItem = Omit<App.Entities.AgendaItem, 'title' | 'description' | 'student_position' | 'votes'> & {
  title: unknown;
  description: unknown;
  student_position: unknown;
  votes?: Array<Omit<App.Entities.Vote, 'title' | 'note'> & { title: unknown; note: unknown }>;
};

interface SiblingAgendaItem {
  id: string;
  title: string;
  type?: string | null;
  order: number;
  brought_by_students: boolean;
  main_vote?: unknown;
  comments_count?: number;
  has_notes?: boolean;
  start_time?: string | null;
  end_time?: string | null;
  /** What the item still lacks (MeetingCompletionService), or null when complete. */
  missing?: AgendaItemMissingAction | null;
}

const props = withDefaults(defineProps<{
  agendaItem: EditableAgendaItem;
  siblingAgendaItems: SiblingAgendaItem[];
  publicUrl?: string | null;
  abilities?: { update: boolean; delete: boolean };
  requiresStudentPerspective?: boolean;
}>(), {
  abilities: () => ({ update: false, delete: false }),
  publicUrl: null,
  requiresStudentPerspective: true,
});

const LABEL_CLASS = 'text-[11px] font-bold uppercase tracking-[0.18em] text-muted-foreground';
const LINK_CLASS = 'text-brand underline decoration-brand/40 underline-offset-4 hover:decoration-brand';

const isDesktop = useMediaQuery('(min-width: 1024px)');
const canUpdate = computed(() => props.abilities.update);

const section = ref('item');
const sections = computed(() => [{ value: 'item', label: $t('Punktas') }]);

const sheetOpen = ref(false);
const votesSheetOpen = ref(false);
const notesOpen = ref(false);
const deleteOpen = ref(false);

/**
 * Offered in the sheet while this item has no start time: the nearest preceding item's end
 * time. Only a suggestion — the facts keep showing what is saved.
 */
const defaultStartTimeFromPreviousItem = (): string | null => {
  const previous = props.siblingAgendaItems
    .filter(item => item.order < props.agendaItem.order && item.end_time)
    .sort((a, b) => b.order - a.order)[0];

  return previous?.end_time ? String(previous.end_time).slice(0, 5) : null;
};

const form = useForm<AgendaItemFormData>({
  title: toTranslatedField(props.agendaItem.title),
  type: (props.agendaItem.type ?? null) as AgendaItemFormData['type'],
  brought_by_students: props.agendaItem.brought_by_students ?? false,
  student_position: toTranslatedField(props.agendaItem.student_position),
  description: toTranslatedField(props.agendaItem.description),
  // The columns are TIME (`HH:MM:SS`); the inputs and the validator both speak `HH:MM`.
  start_time: props.agendaItem.start_time ? String(props.agendaItem.start_time).slice(0, 5) : null,
  end_time: props.agendaItem.end_time ? String(props.agendaItem.end_time).slice(0, 5) : null,
  votes: (props.agendaItem.votes ?? []).map((vote): EditableVote => ({
    id: vote.id,
    is_main: vote.is_main ?? false,
    is_consensus: vote.is_consensus ?? false,
    title: toTranslatedField(vote.title),
    note: toTranslatedField(vote.note),
    student_vote: (vote.student_vote ?? null) as VoteValue,
    decision: (vote.decision ?? null) as VoteValue,
    student_benefit: (vote.student_benefit ?? null) as VoteValue,
    order: vote.order ?? 0,
  })),
});

// A tap is a whole answer, so it saves quickly; the sheet saves on its own button.
const { saveStatus, saveThen } = useAgendaItemAutosave(form, props.agendaItem.id, 700);

// RecordPage renders the subtitle row whenever the slot exists, so it is only passed with something in it.
const hasSubtitle = computed(() => !canUpdate.value || saveStatus.value !== 'idle' || !isDesktop.value);

/** The read view is Lithuanian, falling back to English for the odd English-only item. */
const displayTitle = computed(() => form.title.lt || form.title.en);
const descriptionText = computed(() => form.description.lt || form.description.en);
const studentPositionText = computed(() => form.student_position.lt || form.student_position.en);

const status = computed(() => agendaItemStatuses[getAgendaItemStatus({
  id: props.agendaItem.id,
  type: form.type,
  votes: form.votes.map(vote => ({ ...vote, id: vote.id ?? undefined })),
}, props.requiresStudentPerspective)]);

const meeting = computed(() => props.agendaItem.meeting as (App.Entities.Meeting & { is_public?: boolean }) | undefined);
const meetingIsPublic = computed(() => Boolean(meeting.value?.is_public));
const institutions = computed(() => meeting.value?.institutions ?? []);
const mainInstitution = computed(() => institutions.value[0] ?? null);

const meetingLabel = computed(() => (meeting.value?.start_time
  ? formatDate(meeting.value.start_time, { format: 'full', locale: getActiveLanguage() })
  : meeting.value?.title ?? ''));

const currentIndex = computed(() => props.siblingAgendaItems.findIndex(item => item.id === props.agendaItem.id));
const currentPosition = computed(() => (currentIndex.value >= 0 ? currentIndex.value + 1 : props.agendaItem.order));

const timeRange = computed(() => [form.start_time, form.end_time].filter(Boolean).join('–'));

const recordFacts = computed<RecordFact[]>(() => [
  // The status leads, toned in its colour, as the one answer to "is this item done?".
  { key: 'status', label: $t('Būsena'), status: status.value },
  {
    key: 'visibility',
    label: $t('Matomumas'),
    labelIcon: Globe,
    surfaceClass: props.publicUrl ? 'bg-status-success-surface' : 'bg-status-neutral-surface',
  },
  { key: 'institution', label: $t('meetings.item.institution') },
  { key: 'meeting', label: $t('meetings.item.meeting') },
  { key: 'time', label: $t('meetings.item.time'), value: timeRange.value || '—' },
  // Only a question the reps raised says something; "—" on every other item was noise.
  ...(form.brought_by_students
    ? [{ key: 'raised-by', label: $t('meetings.item.raised_by'), value: $t('meetings.item.raised_by_students') }]
    : []),
]);

const requestedFocus = typeof window !== 'undefined'
  ? new URLSearchParams(window.location.search).get('focus')
  : null;

// The type is asked for until it is set, then folds into the status fact to leave room for the outcome.
const typeOpen = ref(!props.agendaItem.type || (requestedFocus !== null && requestedFocus !== 'votes'));

const itemHref = (id: string) => route('agendaItems.show', { agendaItem: id });

const navigation = computed<RecordNavigationContext>(() => {
  const all = props.siblingAgendaItems;
  const index = currentIndex.value;

  return {
    position: index + 1,
    total: all.length,
    previousHref: index > 0 ? itemHref(all[index - 1]!.id) : null,
    nextHref: index >= 0 && index < all.length - 1 ? itemHref(all[index + 1]!.id) : null,
  };
});

const goTo = (id: string) => router.visit(itemHref(id));

const primaryAction = computed<RecordAction | undefined>(() => (canUpdate.value
  ? { key: 'edit', label: $t('meetings.item.edit'), icon: PenLine }
  : undefined));

const overflowActions = computed<RecordAction[]>(() => [
  { key: 'copy-link', label: $t('Kopijuoti nuorodą'), icon: Copy },
  ...(props.abilities.delete ? [{ key: 'delete', label: $t('meetings.item.delete'), icon: Trash2, destructive: true }] : []),
]);

const handleRecordAction = (action: string) => {
  if (action === 'edit') {
    sheetOpen.value = true;
  }
  else if (action === 'copy-link') {
    void navigator.clipboard?.writeText(window.location.href);
  }
  else if (action === 'delete') {
    deleteOpen.value = true;
  }
};

const deleteItem = () => {
  router.delete(route('agendaItems.destroy', props.agendaItem.id));
};

/**
 * Every way off the page — ‹ ›, breadcrumbs, the shell — waits for a pending tap to be saved,
 * so an answer given just before leaving is never lost.
 */
let leaving = false;
const stopGuard = router.on('before', (event) => {
  const { visit } = event.detail;
  if (leaving || !canUpdate.value || visit.method !== 'get' || visit.prefetch) {
    return;
  }
  if (!form.isDirty && !form.processing) {
    return;
  }

  event.preventDefault();
  saveThen(() => {
    leaving = true;
    router.visit(visit.url.href);
  });
});
onUnmounted(stopGuard);

onMounted(() => {
  if (!requestedFocus) {
    return;
  }

  void nextTick(() => {
    document.getElementById(requestedFocus === 'votes' ? 'agenda-item-votes' : 'agenda-item-type')
      ?.scrollIntoView({ block: 'center' });
  });
});
</script>
