<template>
  <OverviewSection
    v-if="shortcutRows.length > 0"
    :title="$t('home.create_title')"
    :icon="Plus"
    variant="home"
    content-ruled
    data-slot="create-shortcuts"
    data-tour="quick-actions"
  >
    <!-- Rows, not buttons: the shell's Veiksmai is the one brand fill. Leading rows speak in brand ink. -->
    <CollectionRowList
      :items="shortcutRows"
      frame="box"
      class="overflow-hidden"
      :item-key="row => row.id"
      :row-hover="row => row.kind !== 'split'"
    >
      <template #default="{ item: row }">
        <div
          v-if="row.kind === 'split'"
          class="grid grid-cols-2 divide-x divide-border/60"
          :data-shortcut-group="row.id"
        >
          <button
            v-for="shortcut in row.items"
            :key="shortcut.id"
            type="button"
            :class="cn(ROW_LINK_CLASS, 'group items-center gap-2 px-3 transition-colors hover:bg-secondary/40 sm:px-3.5')"
            :data-shortcut="shortcut.id"
            :data-leading="shortcut.leading || undefined"
            @click="shortcut.action?.()"
          >
            <component
              :is="shortcut.icon"
              :class="['size-4 shrink-0 transition-colors', shortcut.leading ? 'text-brand' : 'text-muted-foreground group-hover:text-brand']"
              aria-hidden="true"
            />
            <span :class="['min-w-0 flex-1 text-pretty font-semibold leading-snug', shortcut.leading ? 'text-brand' : 'text-foreground']">
              {{ shortcut.label }}
            </span>
          </button>
        </div>

        <component
          :is="row.shortcut.href ? Link : 'button'"
          v-else
          :href="row.shortcut.href"
          :type="row.shortcut.href ? undefined : 'button'"
          :class="cn(ROW_LINK_CLASS, 'group items-center')"
          :data-shortcut="row.shortcut.id"
          :data-leading="row.shortcut.leading || undefined"
          @click="row.shortcut.action?.()"
        >
          <component
            :is="row.shortcut.icon"
            :class="['size-4 shrink-0 transition-colors', row.shortcut.leading ? 'text-brand' : 'text-muted-foreground group-hover:text-brand']"
            aria-hidden="true"
          />
          <span class="min-w-0 flex-1">
            <span :class="['block font-semibold', row.shortcut.leading ? 'text-brand' : 'text-foreground']">{{ row.shortcut.label }}</span>
            <span v-if="row.shortcut.detail" class="mt-0.5 block text-xs text-muted-foreground tabular-nums">{{ row.shortcut.detail }}</span>
          </span>
          <ChevronRight
            :class="['size-4 shrink-0 transition-transform group-hover:translate-x-0.5', row.shortcut.leading ? 'text-brand' : 'text-muted-foreground']"
            aria-hidden="true"
          />
        </component>
      </template>
    </CollectionRowList>
  </OverviewSection>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { CalendarOff, CalendarPlus, ChevronRight, LifeBuoy, Plus, ShoppingBasket } from '@lucide/vue';
import { computed, type Component } from 'vue';

import { useCommandActions, type CommandAction } from '@/Components/CommandPalette/useCommandActions';
import CollectionRowList from '@/Components/Collection/CollectionRowList.vue';
import { ROW_LINK_CLASS } from '@/Components/Collection/rowList';
import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import { formatReservationPeriod } from '@/Components/Reservations/reservationPeriod';
import type { ReservationCartSummary } from '@/Components/Reservations/types';
import { cn } from '@/Utils/Shadcn/utils';

const props = withDefaults(defineProps<{
  limit?: number;
  /** A started reservation: it replaces "Nauja rezervacija" and leads the list. */
  reservationDraft?: ReservationCartSummary | null;
}>(), {
  limit: 4,
  reservationDraft: null,
});

interface Shortcut {
  id: string;
  label: string;
  icon: Component;
  action?: () => void;
  href?: string;
  detail?: string;
  leading: boolean;
}

type ShortcutRow
  = | { kind: 'single'; id: string; shortcut: Shortcut }
    | { kind: 'split'; id: string; items: [Shortcut, Shortcut] };

const RECORD_MEETING = 'create-new_meeting';
const NO_MEETING = 'create-no_meeting';
const NEW_RESERVATION = 'create-new_reservation';
const NEW_SUPPORT_REQUEST = 'create-new_support_request';

/** Home-only wording overrides; Command Palette and ActionWindow keep the catalog labels. */
const HOME_LABEL_KEYS: Record<string, string> = {
  [RECORD_MEETING]: 'home.shortcuts.new_meeting',
  [NO_MEETING]: 'home.shortcuts.no_meeting',
  'create-new_problem': 'home.shortcuts.new_problem',
  [NEW_RESERVATION]: 'home.shortcuts.new_reservation',
  [NEW_SUPPORT_REQUEST]: 'home.shortcuts.new_support_request',
};

const HOME_ICONS: Record<string, Component> = {
  [RECORD_MEETING]: CalendarPlus,
  [NO_MEETING]: CalendarOff,
  [NEW_SUPPORT_REQUEST]: LifeBuoy,
};

const { actions } = useCommandActions();

/** What we want people to do most often comes first; the two meeting answers share one row. */
const PRIORITY = [RECORD_MEETING, NO_MEETING, 'create-new_problem', NEW_RESERVATION, NEW_SUPPORT_REQUEST];

const toShortcut = (action: CommandAction, leading = false): Shortcut => {
  const labelKey = HOME_LABEL_KEYS[action.id];

  return {
    id: action.id,
    label: labelKey ? $t(labelKey) : action.label,
    icon: HOME_ICONS[action.id] ?? action.icon,
    action: action.action,
    leading,
  };
};

const finishReservation = computed<Shortcut | null>(() => {
  const draft = props.reservationDraft;

  if (!draft) {
    return null;
  }

  const items = draft.count > 0 ? $tChoice('reservations.cart.items', draft.count, { count: String(draft.count) }) : null;
  const period = draft.start_time && draft.end_time ? formatReservationPeriod(draft.start_time, draft.end_time) : null;

  return {
    id: 'finish-reservation',
    label: $t('reservations.cart.finish'),
    icon: ShoppingBasket,
    href: route('reservations.create'),
    detail: [draft.name, items, period].filter(Boolean).join(' · '),
    leading: true,
  };
});

const shortcutRows = computed<ShortcutRow[]>(() => {
  const permitted = actions.value.filter(action => action.category === 'create'
    && !(finishReservation.value && action.id === NEW_RESERVATION));
  const recordMeeting = permitted.find(action => action.id === RECORD_MEETING);
  const noMeeting = permitted.find(action => action.id === NO_MEETING);
  const pairMeetings = Boolean(recordMeeting && noMeeting);

  const prioritized = PRIORITY.flatMap(id => permitted.filter(action => action.id === id));
  const rest = permitted.filter(action => !PRIORITY.includes(action.id));
  const coveredWorkspaces = new Set(prioritized.map(action => action.workspaceKey));
  const firstPerWorkspace = rest.filter((action, index) =>
    action.workspaceKey && !coveredWorkspaces.has(action.workspaceKey)
    && rest.findIndex(candidate => candidate.workspaceKey === action.workspaceKey) === index);
  const selectedIds = new Set([...prioritized, ...firstPerWorkspace].map(action => action.id));

  const orderedActions = [
    ...prioritized,
    ...firstPerWorkspace,
    ...rest.filter(action => !selectedIds.has(action.id)),
  ];

  const rows: ShortcutRow[] = [];

  for (const action of orderedActions) {
    if (pairMeetings && action.id === NO_MEETING) {
      continue;
    }

    if (pairMeetings && action.id === RECORD_MEETING && recordMeeting && noMeeting) {
      rows.push({
        kind: 'split',
        id: 'meeting-activity',
        items: [toShortcut(recordMeeting, true), toShortcut(noMeeting, false)],
      });
      continue;
    }

    rows.push({
      kind: 'single',
      id: action.id,
      shortcut: toShortcut(action, action.id === RECORD_MEETING),
    });
  }

  // Unfinished work comes before anything new.
  const leadingRows: ShortcutRow[] = finishReservation.value
    ? [{ kind: 'single', id: finishReservation.value.id, shortcut: finishReservation.value }]
    : [];

  return [...leadingRows, ...rows].slice(0, props.limit);
});
</script>
