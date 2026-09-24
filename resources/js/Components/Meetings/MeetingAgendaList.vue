<template>
  <div data-slot="meeting-agenda-list" class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="ml-auto flex flex-wrap items-center gap-2">
        <template v-if="ordering">
          <Button variant="ghost" size="sm" voice="sentence" @click="cancelOrder">
            {{ $t('Atšaukti') }}
          </Button>
          <Button size="sm" @click="persistOrder">
            {{ $t('Išsaugoti tvarką') }}
          </Button>
        </template>
        <!-- Quiet ghost controls: the list is read far more often than it is rearranged. -->
        <template v-else>
          <Button
            v-if="canReorder && localItems.length > 1"
            variant="ghost"
            size="sm"
            voice="sentence"
            class="text-muted-foreground"
            @click="ordering = true"
          >
            <ArrowUpDown class="size-4" />
            {{ $t('Keisti tvarką') }}
          </Button>
          <Button
            v-if="canAdd && localItems.length > 0"
            variant="ghost"
            size="sm"
            voice="sentence"
            class="text-muted-foreground"
            @click="$emit('add', 'lines')"
          >
            <Plus class="size-4" />
            {{ $t('meetings.agenda.add_items') }}
          </Button>
        </template>
      </div>
    </div>

    <EmptyState
      v-if="localItems.length === 0"
      :icon="ListOrdered"
      :title="$t('Darbotvarkės punktų nėra')"
      :description="canAdd ? $t('meetings.agenda.empty_description') : $t('meetings.agenda.empty_readonly')"
      class="border-y border-border"
    >
      <template v-if="canAdd" #default>
        <Button variant="brand" @click="$emit('add', 'paste')">
          <ClipboardPaste class="size-4" />
          {{ $t('meetings.agenda.paste_agenda') }}
        </Button>
        <Button variant="outline" voice="sentence" @click="$emit('add', 'lines')">
          <Plus class="size-4" />
          {{ $t('meetings.agenda.add_one_by_one') }}
        </Button>
      </template>
    </EmptyState>

    <ol v-else ref="listContainer" class="divide-y divide-border border-y border-border">
      <li
        v-for="(item, index) in localItems"
        :key="item.id"
        :data-id="item.id"
        class="flex items-stretch"
      >
        <button
          v-if="ordering"
          type="button"
          class="drag-handle hidden shrink-0 cursor-grab items-center px-2 text-muted-foreground hover:text-foreground pointer-fine:flex"
          :aria-label="$t('Tempti')"
        >
          <GripVertical class="size-4" />
        </button>

        <component
          :is="ordering ? 'div' : Link"
          :href="ordering ? undefined : route('agendaItems.show', item.id)"
          :class="[
            'flex min-w-0 flex-1 items-start gap-3 py-3 pr-2 sm:gap-4',
            ordering ? undefined : 'transition-colors hover:bg-accent/60 focus-visible:bg-accent/60 focus-visible:outline-none',
          ]"
        >
          <span
            :class="[
              'flex size-7 shrink-0 items-center justify-center text-xs font-semibold tabular-nums',
              getNumberBadgeClass(item as AgendaStatusItem, requiresStudentPerspective),
            ]"
          >
            {{ index + 1 }}
          </span>

          <span class="min-w-0 flex-1">
            <span class="block text-sm font-medium leading-snug text-foreground">
              {{ item.title }}
            </span>

            <span class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
              <span :class="['inline-flex items-center gap-1 font-medium', statusRoleParts[statusOf(item).role].text]" data-slot="agenda-status">
                <component :is="statusOf(item).icon" class="size-3.5" aria-hidden="true" />
                {{ $t(statusOf(item).label) }}
              </span>
              <span v-if="item.brought_by_students" class="inline-flex items-center gap-1">
                <Users class="size-3.5" aria-hidden="true" />
                {{ $t('Studentų klausimas') }}
              </span>
              <span v-if="timeRange(item)" class="tabular-nums">{{ timeRange(item) }}</span>
              <span v-if="voteCount(item) > 1">{{ voteCount(item) }} {{ $t('balsavimai') }}</span>
              <span v-if="commentsCount(item)" class="inline-flex items-center gap-1" :title="$t('Komentarai')">
                <MessageSquare class="size-3.5" />{{ commentsCount(item) }}
              </span>
              <NotebookPen v-if="hasNotes(item)" class="size-3.5" :aria-label="$t('Yra pastabų')" />
            </span>

          </span>

          <ChevronRight v-if="!ordering" class="mt-1.5 size-4 shrink-0 text-muted-foreground" />
        </component>

        <div v-if="ordering" class="flex shrink-0 items-center gap-1 pr-1">
          <Button
            variant="ghost"
            size="icon"
            class="u-touch"
            :disabled="index === 0"
            :aria-label="$t('Perkelti aukštyn')"
            @click="move(index, -1)"
          >
            <ChevronUp class="size-4" />
          </Button>
          <Button
            variant="ghost"
            size="icon"
            class="u-touch"
            :disabled="index === localItems.length - 1"
            :aria-label="$t('Perkelti žemyn')"
            @click="move(index, 1)"
          >
            <ChevronDown class="size-4" />
          </Button>
        </div>
      </li>
    </ol>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useSortable } from '@vueuse/integrations/useSortable';
import { trans as $t } from 'laravel-vue-i18n';
import {
  ArrowUpDown,
  ChevronDown,
  ChevronRight,
  ChevronUp,
  ClipboardPaste,
  GripVertical,
  ListOrdered,
  MessageSquare,
  NotebookPen,
  Plus,
  Users,
} from 'lucide-vue-next';

import { EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { agendaItemStatuses, statusRoleParts } from '@/Constants/statuses';
import {
  type AgendaItem as AgendaStatusItem,
  getAgendaItemStatus,
  getNumberBadgeClass,
} from '@/Composables/useAgendaItemStyling';

const props = withDefaults(defineProps<{
  agendaItems: App.Entities.AgendaItem[];
  meetingId: string;
  canAdd?: boolean;
  canReorder?: boolean;
  /** False for VU SA's own bodies — a recorded decision alone means discussed. */
  requiresStudentPerspective?: boolean;
}>(), {
  canAdd: false,
  canReorder: false,
  requiresStudentPerspective: true,
});

defineEmits<{
  add: [mode: 'lines' | 'paste'];
}>();

const ordering = ref(false);

const localItems = ref<App.Entities.AgendaItem[]>([]);

watch(
  () => props.agendaItems,
  (items) => {
    localItems.value = [...(items ?? [])].sort((a, b) => a.order - b.order);
  },
  { immediate: true, deep: true },
);

type AgendaListItem = App.Entities.AgendaItem & {
  comments_count?: number;
  has_notes?: boolean;
};

const statusOf = (item: App.Entities.AgendaItem) =>
  agendaItemStatuses[getAgendaItemStatus(item as AgendaStatusItem, props.requiresStudentPerspective)];
const voteCount = (item: App.Entities.AgendaItem) => item.votes?.length ?? 0;
const commentsCount = (item: App.Entities.AgendaItem) => (item as AgendaListItem).comments_count ?? 0;
const hasNotes = (item: App.Entities.AgendaItem) => Boolean((item as AgendaListItem).has_notes);

const timeRange = (item: App.Entities.AgendaItem) => {
  const start = item.start_time ? String(item.start_time).slice(0, 5) : null;
  const end = item.end_time ? String(item.end_time).slice(0, 5) : null;

  return start && end ? `${start}–${end}` : (start ?? '');
};

const listContainer = ref<HTMLElement | null>(null);

const sortable = useSortable(listContainer, localItems, {
  handle: '.drag-handle',
  animation: 200,
  disabled: true,
  onEnd: async () => { await nextTick(); },
});

watch(ordering, (isOrdering) => {
  sortable.option('disabled', !isOrdering);
});

const move = (index: number, direction: -1 | 1) => {
  const destination = index + direction;
  if (destination < 0 || destination >= localItems.value.length) {
    return;
  }
  const next = [...localItems.value];
  const [item] = next.splice(index, 1);
  if (item) {
    next.splice(destination, 0, item);
    localItems.value = next;
  }
};

const cancelOrder = () => {
  localItems.value = [...props.agendaItems].sort((a, b) => a.order - b.order);
  ordering.value = false;
};

const persistOrder = () => {
  router.post(route('agendaItems.reorder'), {
    meeting_id: props.meetingId,
    agenda_items: localItems.value.map((item, index) => ({ id: item.id, order: index + 1 })),
  }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      ordering.value = false;
    },
    onError: () => {
      localItems.value = [...props.agendaItems].sort((a, b) => a.order - b.order);
    },
  });
};
</script>
