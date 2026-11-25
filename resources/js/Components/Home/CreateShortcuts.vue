<template>
  <OverviewSection
    v-if="shortcuts.length > 0"
    :title="$t('home.create_title')"
    :icon="Plus"
    variant="home" content-ruled
    data-slot="create-shortcuts"
    data-tour="quick-actions"
  >
    <!-- Rows, not buttons: the shell's Veiksmai is the one brand fill. Leading rows speak in brand ink. -->
    <CollectionRowList :items="shortcuts" frame="box" :item-key="shortcut => shortcut.id">
      <template #default="{ item: shortcut }">
        <component
          :is="shortcut.href ? Link : 'button'"
          :href="shortcut.href"
          :type="shortcut.href ? undefined : 'button'"
          :class="cn(ROW_LINK_CLASS, 'group items-center')"
          :data-shortcut="shortcut.id"
          :data-leading="shortcut.leading || undefined"
          @click="shortcut.action?.()"
        >
          <component
            :is="shortcut.icon"
            :class="['size-4 shrink-0 transition-colors', shortcut.leading ? 'text-brand' : 'text-muted-foreground group-hover:text-brand']"
            aria-hidden="true"
          />
          <span class="min-w-0 flex-1">
            <span :class="['block font-semibold', shortcut.leading ? 'text-brand' : 'text-foreground']">{{ shortcut.label }}</span>
            <span v-if="shortcut.detail" class="mt-0.5 block text-xs text-muted-foreground tabular-nums">{{ shortcut.detail }}</span>
          </span>
          <ChevronRight
            :class="['size-4 shrink-0 transition-transform group-hover:translate-x-0.5', shortcut.leading ? 'text-brand' : 'text-muted-foreground']"
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
import { ChevronRight, Plus, ShoppingBasket } from '@lucide/vue';
import { computed, type Component } from 'vue';

import { useCommandActions } from '@/Components/CommandPalette/useCommandActions';
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

const NEW_RESERVATION = 'create-new_reservation';
const RECORD_MEETING = 'create-new_meeting';

const { actions } = useCommandActions();

/** What we want people to do more often comes first; "no meeting" sits beside "record a meeting" as its equal. */
const PRIORITY = ['create-new_meeting', 'create-no_meeting', 'create-new_problem'];

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

const shortcuts = computed<Shortcut[]>(() => {
  const permitted = actions.value.filter(action => action.category === 'create'
    && !(finishReservation.value && action.id === NEW_RESERVATION));
  const prioritized = PRIORITY.flatMap(id => permitted.filter(action => action.id === id));
  const rest = permitted.filter(action => !PRIORITY.includes(action.id));
  const coveredWorkspaces = new Set(prioritized.map(action => action.workspaceKey));
  const firstPerWorkspace = rest.filter((action, index) =>
    action.workspaceKey && !coveredWorkspaces.has(action.workspaceKey)
    && rest.findIndex(candidate => candidate.workspaceKey === action.workspaceKey) === index);
  const selectedIds = new Set([...prioritized, ...firstPerWorkspace].map(action => action.id));

  const ordered: Shortcut[] = [
    ...prioritized,
    ...firstPerWorkspace,
    ...rest.filter(action => !selectedIds.has(action.id)),
  ].map(action => ({ id: action.id, label: action.label, icon: action.icon, action: action.action, leading: action.id === RECORD_MEETING }));

  // Unfinished work comes before anything new.
  return [...(finishReservation.value ? [finishReservation.value] : []), ...ordered].slice(0, props.limit);
});
</script>
