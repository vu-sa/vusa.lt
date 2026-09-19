<template>
  <div class="space-y-3">
    <!-- Header: title + summary + lightweight edit toggle -->
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-baseline gap-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
          {{ $t('Darbotvarkė') }}
        </h2>
        <span v-if="agendaSummary" class="text-sm text-zinc-400 dark:text-zinc-500">
          {{ agendaSummary }}
        </span>
      </div>

      <div class="flex items-center gap-2">
        <AdminVotingHelpButton />
        <SpotlightPopover
          v-if="canManage"
          :title="$t('Tvarkykite darbotvarkę')"
          :description="$t('Įjunkite redagavimą, kad pridėtumėte, pertvarkytumėte ar pašalintumėte darbotvarkės punktus.')"
          position="bottom"
          :show-badge="spotlight.isVisible.value"
          :is-dismissed="spotlight.isDismissed.value"
          @dismiss="spotlight.dismiss"
        >
          <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400 select-none">
            <Switch :model-value="editing" @update:model-value="onToggleEditing" />
            <span>{{ $t('Redaguoti') }}</span>
          </label>
        </SpotlightPopover>
      </div>
    </div>

    <!-- Empty state -->
    <div
      v-if="localItems.length === 0"
      class="flex flex-col items-center justify-center py-14 text-center border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-xl bg-zinc-50/50 dark:bg-zinc-800/30"
    >
      <div class="w-14 h-14 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-4">
        <FileText class="h-7 w-7 text-zinc-400 dark:text-zinc-500" />
      </div>
      <h3 class="text-base font-medium text-zinc-900 dark:text-zinc-100 mb-1">
        {{ $t('Darbotvarkės punktų nėra') }}
      </h3>
      <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-5 max-w-sm">
        {{ editing ? $t('Sukurkite darbotvarkės punktus, kad galėtumėte pradėti posėdžio valdymą.') : $t('Įjunkite redagavimą, kad pridėtumėte darbotvarkės punktų.') }}
      </p>
      <!-- Both routes in from an empty agenda: the bulk editor is how a copied
           timetable gets in, and it was previously unreachable until one item existed. -->
      <div v-if="editing && canAdd" class="flex flex-wrap items-center justify-center gap-2">
        <Button @click="$emit('add')">
          <Plus class="h-4 w-4 mr-2" />
          {{ $t('Pridėti pirmą klausimą') }}
        </Button>
        <Button variant="outline" @click="$emit('add-bulk')">
          <ListPlus class="h-4 w-4 mr-2" />
          {{ $t('Pridėti kelis punktus') }}
        </Button>
      </div>
    </div>

    <!-- Agenda list -->
    <div v-else ref="listContainer" class="divide-y divide-zinc-100 dark:divide-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
      <div
        v-for="(item, index) in localItems"
        :key="item.id"
        :data-id="item.id"
        class="group flex items-center gap-3 bg-white dark:bg-zinc-900"
      >
        <!-- Drag handle (edit mode) -->
        <button
          v-if="editing && ordering && canReorder"
          type="button"
          class="drag-handle shrink-0 pl-3 cursor-grab text-zinc-300 hover:text-zinc-500 dark:text-zinc-600 dark:hover:text-zinc-400"
          :aria-label="$t('Tempti')"
        >
          <GripVertical class="h-4 w-4" />
        </button>

        <!-- Row body: links to per-item edit page -->
        <Link
          :href="route('agendaItems.edit', item.id)"
          class="flex flex-1 items-start gap-3 px-4 py-3 min-w-0 transition-colors hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40 cursor-pointer"
        >
          <!-- Number badge -->
          <span
            :class="[
              'flex items-center justify-center w-6 h-6 rounded-md text-xs font-semibold shrink-0 mt-0.5',
              getNumberBadgeClass(item),
            ]"
          >
            {{ index + 1 }}
          </span>

          <!-- Title + status -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <p class="text-sm text-zinc-900 dark:text-zinc-100 leading-snug">
                {{ item.title }}
              </p>
              <span
                v-if="item.brought_by_students"
                class="shrink-0 inline-flex items-center gap-1 rounded-full bg-red-50 dark:bg-red-900/20 px-2 py-0.5 text-xs font-medium text-red-600 dark:text-red-400 ring-1 ring-red-200/70 dark:ring-red-900/50"
              >
                <Users class="h-3 w-3" />
                {{ $t('Studentų klausimas') }}
              </span>
            </div>
            <span :class="['mt-1 inline-block text-xs font-medium', getStatusTextClass(item)]">
              {{ getStatusText(item) }}
            </span>
          </div>

          <!-- Simplified read-only vote indicator -->
          <div class="flex items-center gap-2 shrink-0 self-center">
            <!-- Discussion + notes presence -->
            <span
              v-if="commentsCount(item) || hasNotes(item)"
              class="flex items-center gap-1.5 text-xs text-zinc-400 dark:text-zinc-500"
            >
              <span v-if="commentsCount(item)" class="inline-flex items-center gap-0.5" :title="$t('Komentarai')">
                <MessageSquare class="h-3.5 w-3.5" />{{ commentsCount(item) }}
              </span>
              <NotebookPen v-if="hasNotes(item)" class="h-3.5 w-3.5" :aria-label="$t('Yra pastabų')" />
            </span>
            <span v-if="voteCount(item) > 1" class="text-xs text-zinc-400 dark:text-zinc-500">
              {{ voteCount(item) }} {{ $t('balsavimai') }}
            </span>
            <template v-if="mainVoteOf(item)?.decision">
              <VoteStatusIndicator :vote="mainVoteOf(item)?.decision" type="vote" compact />
              <VoteStatusIndicator
                v-if="mainVoteOf(item)?.student_benefit"
                :vote="mainVoteOf(item)?.student_benefit"
                type="benefit"
                compact
              />
            </template>
          </div>
        </Link>

        <div v-if="editing && ordering && canReorder" class="flex shrink-0 items-center gap-1 pr-2">
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

        <!-- Remove button (edit mode) -->
        <button
          v-if="editing && !ordering && canDelete(item)"
          type="button"
          class="shrink-0 pr-3 text-zinc-300 hover:text-destructive dark:text-zinc-600 dark:hover:text-destructive transition-colors"
          :aria-label="$t('Šalinti')"
          @click="$emit('delete', item)"
        >
          <Trash2 class="h-4 w-4" />
        </button>
      </div>
    </div>

    <!-- Add affordance (edit mode, non-empty) -->
    <div v-if="editing && localItems.length > 0" class="flex flex-wrap justify-center gap-2">
      <template v-if="ordering">
        <Button variant="outline" size="sm" @click="cancelOrder">
          {{ $t('Atšaukti') }}
        </Button>
        <Button size="sm" @click="persistOrder">
          {{ $t('Išsaugoti tvarką') }}
        </Button>
      </template>
      <Button v-else-if="canReorder" variant="outline" size="sm" @click="ordering = true">
        <GripVertical class="size-4" />
        {{ $t('Keisti tvarką') }}
      </Button>
      <DropdownMenu v-if="!ordering && canAdd">
        <DropdownMenuTrigger as-child>
          <Button variant="outline" size="sm">
            <Plus class="h-4 w-4 mr-1" />
            {{ $t('Pridėti punktą') }}
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="center">
          <DropdownMenuItem @click="$emit('add')">
            <Plus class="h-4 w-4 mr-2" />
            {{ $t('Pridėti vieną punktą') }}
          </DropdownMenuItem>
          <DropdownMenuItem @click="$emit('add-bulk')">
            <ListPlus class="h-4 w-4 mr-2" />
            {{ $t('Pridėti kelis punktus') }}
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useSortable } from '@vueuse/integrations/useSortable';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronDown, ChevronUp, FileText, GripVertical, ListPlus, MessageSquare, NotebookPen, Plus, Trash2, Users } from 'lucide-vue-next';

import AdminVotingHelpButton from '@/Components/AgendaItems/AdminVotingHelpButton.vue';
import VoteStatusIndicator from '@/Components/Public/VoteStatusIndicator.vue';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { Button } from '@/Components/ui/button';
import { Switch } from '@/Components/ui/switch';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
  type AgendaItem as AgendaStatusItem,
  getMainVote,
  getNumberBadgeClass,
  getStatusText,
  getStatusTextClass,
  getMeetingStatusSummary,
} from '@/Composables/useAgendaItemStyling';

const props = withDefaults(defineProps<{
  agendaItems: App.Entities.AgendaItem[];
  meetingId: string;
  editing?: boolean;
  canAdd?: boolean;
  canReorder?: boolean;
  /** False for VU SA's own bodies — a recorded decision alone means discussed. */
  requiresStudentPerspective?: boolean;
}>(), {
  editing: false,
  canAdd: false,
  canReorder: false,
  requiresStudentPerspective: true,
});

const emit = defineEmits<{
  'update:editing': [value: boolean];
  'add': [];
  'add-bulk': [];
  'delete': [item: App.Entities.AgendaItem];
}>();

type AgendaItemWithAbilities = App.Entities.AgendaItem & {
  can?: { update?: boolean; delete?: boolean };
};

const canManage = computed(() => props.canAdd || props.canReorder
  || props.agendaItems.some(item => Boolean((item as AgendaItemWithAbilities).can?.delete)));
const canDelete = (item: App.Entities.AgendaItem) => Boolean((item as AgendaItemWithAbilities).can?.delete);

const spotlight = useFeatureSpotlight('meeting-record-completion-v1');
const ordering = ref(false);

const onToggleEditing = (value: boolean) => {
  if (value && spotlight.isVisible.value) {
    spotlight.dismiss();
  }
  if (!value) {
    ordering.value = false;
  }
  emit('update:editing', value);
};

// Local reactive copy for drag-and-drop reordering
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

const mainVoteOf = (item: App.Entities.AgendaItem) => getMainVote(item as AgendaStatusItem);
const voteCount = (item: App.Entities.AgendaItem) => item.votes?.length ?? 0;
const commentsCount = (item: App.Entities.AgendaItem) => (item as AgendaListItem).comments_count ?? 0;
const hasNotes = (item: App.Entities.AgendaItem) => Boolean((item as AgendaListItem).has_notes);

const agendaSummary = computed(() => {
  const items = localItems.value;
  if (items.length === 0) {
    return '';
  }

  const votingItems = items.filter(item => item.type === 'voting');
  if (votingItems.length === 0) {
    return `${items.length} ${items.length === 1 ? $t('punktas') : $t('punktai')}`;
  }

  const summary = getMeetingStatusSummary(items as AgendaStatusItem[], props.requiresStudentPerspective);
  const completed = summary.consensus + summary.aligned + summary.misaligned + summary.neutralDecided
    + summary.decisionPositive + summary.decisionNegative;
  return `${completed} ${$t('iš')} ${votingItems.length} ${$t('balsavimų aptarta')}`;
});

// Sortable wiring — only active in edit mode
const listContainer = ref<HTMLElement | null>(null);

const sortable = useSortable(listContainer, localItems, {
  handle: '.drag-handle',
  animation: 200,
  disabled: !props.editing,
  onEnd: async () => { await nextTick(); },
});

// Toggle drag enablement reactively with the edit switch
watch([() => props.editing, ordering], ([editing, isOrdering]) => {
  sortable.option('disabled', !editing || !isOrdering);
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

const persistOrder = async () => {
  const reordered = localItems.value.map((item, index) => ({
    id: item.id,
    order: index + 1,
  }));

  router.post(route('agendaItems.reorder'), {
    meeting_id: props.meetingId,
    agenda_items: reordered,
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
