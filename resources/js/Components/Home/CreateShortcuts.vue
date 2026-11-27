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

import { flowForScreen } from '@/Components/CommandPalette/useCommandActions';
import CollectionRowList from '@/Components/Collection/CollectionRowList.vue';
import { ROW_LINK_CLASS } from '@/Components/Collection/rowList';
import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import { formatReservationPeriod } from '@/Components/Reservations/reservationPeriod';
import type { ReservationCartSummary } from '@/Components/Reservations/types';
import { useActionWindow } from '@/Composables/useActionWindow';
import { useAdminNavigation, type AdminWorkspace } from '@/Composables/useAdminNavigation';
import { getEntityTypeDefinition } from '@/Constants/entityTypes';
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

type WorkspaceCreateAction = AdminWorkspace['createActions'][number];

interface CatalogShortcut {
  id: string;
  workspaceKey: string;
  action: WorkspaceCreateAction;
}

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

const { workspaces } = useAdminNavigation();
const actionWindow = useActionWindow();

/** What we want people to do most often comes first; the two meeting answers share one row. */
const PRIORITY = [RECORD_MEETING, NO_MEETING, 'create-new_problem', NEW_RESERVATION, NEW_SUPPORT_REQUEST];

const toShortcut = (entry: CatalogShortcut, leading = false): Shortcut => {
  const { id, action } = entry;
  const labelKey = HOME_LABEL_KEYS[id];
  const entityIcon = action.entityType ? getEntityTypeDefinition(action.entityType)?.icon : undefined;
  const target = action.target as WorkspaceCreateAction['target'] & { routeParams?: Record<string, string> };

  return {
    id,
    label: labelKey ? $t(labelKey) : $t(action.label),
    icon: HOME_ICONS[id] ?? entityIcon ?? Plus,
    href: target.kind === 'route' ? route(target.routeName, target.routeParams) : undefined,
    action: target.kind === 'screen' ? () => actionWindow.open({ flow: flowForScreen[target.screen] }) : undefined,
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
  // Read workspace shortcuts rather than commandActions: the palette marks every create action with `+`
  // and moves non-record actions (`no_meeting`, `duty_update`) out of `create`, while Home keeps all
  // workspace shortcuts with their entity icons and excludes palette-only entries.
  const permitted: CatalogShortcut[] = workspaces.value
    .flatMap(workspace => workspace.createActions.map(action => ({
      id: `create-${action.key}`,
      workspaceKey: workspace.key,
      action,
    })))
    .filter(entry => !(finishReservation.value && entry.id === NEW_RESERVATION));

  const recordMeeting = permitted.find(entry => entry.id === RECORD_MEETING);
  const noMeeting = permitted.find(entry => entry.id === NO_MEETING);
  const pairMeetings = Boolean(recordMeeting && noMeeting);

  const prioritized = PRIORITY.flatMap(id => permitted.filter(entry => entry.id === id));
  const rest = permitted.filter(entry => !PRIORITY.includes(entry.id));
  const coveredWorkspaces = new Set(prioritized.map(entry => entry.workspaceKey));
  const firstPerWorkspace = rest.filter((entry, index) =>
    !coveredWorkspaces.has(entry.workspaceKey)
    && rest.findIndex(candidate => candidate.workspaceKey === entry.workspaceKey) === index);
  const selectedIds = new Set([...prioritized, ...firstPerWorkspace].map(entry => entry.id));

  const orderedEntries = [
    ...prioritized,
    ...firstPerWorkspace,
    ...rest.filter(entry => !selectedIds.has(entry.id)),
  ];

  const rows: ShortcutRow[] = [];

  for (const entry of orderedEntries) {
    if (pairMeetings && entry.id === NO_MEETING) {
      continue;
    }

    if (pairMeetings && entry.id === RECORD_MEETING && recordMeeting && noMeeting) {
      rows.push({
        kind: 'split',
        id: 'meeting-activity',
        items: [toShortcut(recordMeeting, true), toShortcut(noMeeting, false)],
      });
      continue;
    }

    rows.push({
      kind: 'single',
      id: entry.id,
      shortcut: toShortcut(entry, entry.id === RECORD_MEETING),
    });
  }

  // Unfinished work comes before anything new.
  const leadingRows: ShortcutRow[] = finishReservation.value
    ? [{ kind: 'single', id: finishReservation.value.id, shortcut: finishReservation.value }]
    : [];

  return [...leadingRows, ...rows].slice(0, props.limit);
});
</script>
