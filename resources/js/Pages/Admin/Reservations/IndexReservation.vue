<template>
  <CollectionPage
    v-model:selection="selectedIds"
    :source
    collection="reservations"
    entity-type="reservation"
    :eyebrow="$t('shell.workspaces.rezervacijos.title') + ' · ' + $t('shell.sections.rezervacijos')"
    :title="$t('Rezervacijos')"
    :lead="$t('Stebėk prašymus, patvirtink išdavimą ir pažymėk grąžinimą.')"
    default-view="table"
    :item-key="reservationKey"
    :trash="{ count: deletedCount ?? 0, active: isDeleted }"
    :columns
    :selectable="!isDeleted && managesResources"
    :can-select="isReservationSelectable"
    :quick-filters
    :search-placeholder="$t('Ieškoti rezervacijų')"
    @quick-filter="toggleQuickFilter"
  >
    <template #actions>
      <Button v-if="canCreate && !isDeleted" as-child variant="brand" size="lg">
        <Link v-if="cartItems.length > 0" :href="route('reservations.create')">
          <ArrowRight aria-hidden="true" />
          {{ $t('reservations.cart.continue_with_count', { count: String(cartItems.length) }) }}
        </Link>
        <Link v-else :href="route('reservations.create')">
          <Plus aria-hidden="true" />
          {{ $t('Nauja rezervacija') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex min-h-16 items-start gap-3 px-4 py-4">
        <div class="min-w-0 flex-1">
          <div class="flex items-start justify-between gap-3">
            <CollectionPrimaryCell :title="item.name" :href="isDeleted ? undefined : route('reservations.show', item.id)" />
            <ReservationStateSummary :states="statesOf(item)" :unresolved="isUnresolved(item)" class="shrink-0" />
          </div>
          <ReservationResourceChips class="mt-1.5" :resources="item.resources" :muted-foreign="isAdministrator(item)" />
          <p class="mt-1.5 text-sm tabular-nums text-muted-foreground">
            {{ reservationPeriod(item) }}
          </p>
          <ReservationRowActions v-if="!isDeleted" class="mt-2 flex-wrap" :reservation="item" @decide="openDecision" />
        </div>
      </article>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell
        v-if="column.key === 'name'"
        :title="item.name"
        :href="isDeleted ? undefined : route('reservations.show', item.id)"
        :sub="item.users?.length ? $tChoice('reservations.dashboard.managers', item.users.length, { count: item.users.length }) : undefined"
      />
      <div v-else-if="column.key === 'requester'" class="flex items-center gap-2">
        <template v-if="item.users?.length">
          <UsersAvatarGroup :users="item.users" :size="24" :max="2" />
          <span class="max-w-30 truncate text-sm">{{ item.users[0].name }}</span>
        </template>
        <span v-else class="text-sm text-muted-foreground">—</span>
      </div>
      <ReservationResourceChips
        v-else-if="column.key === 'resources'"
        :resources="item.resources"
        :muted-foreign="isAdministrator(item)"
      />
      <ReservationPeriod v-else-if="column.key === 'period'" :start-time="item.start_time" :end-time="item.end_time" />
      <ReservationStateSummary
        v-else-if="column.key === 'status'"
        :states="statesOf(item)"
        :unresolved="isUnresolved(item)"
      />
      <ReservationRowActions
        v-else-if="column.key === 'actions' && !isDeleted"
        :reservation="item"
        :spotlight="reservationKey(item) === spotlightKey"
        @decide="openDecision"
      />
    </template>

    <template #preview="{ item }">
      <section class="flex flex-col gap-5 p-5">
        <div>
          <p class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
            {{ $t('Rezervacija') }}
          </p>
          <Link :href="route('reservations.show', item.id)" class="mt-1 block text-lg font-semibold hover:text-brand">
            {{ item.name }}
          </Link>
          <p v-if="item.description" class="mt-2 text-sm text-muted-foreground">
            {{ item.description }}
          </p>
        </div>
        <dl class="grid gap-3 border-y border-border py-4 text-sm">
          <div>
            <dt class="text-xs text-muted-foreground">
              {{ $t('Būsena') }}
            </dt>
            <dd class="mt-1">
              <ReservationStateSummary :states="statesOf(item)" :unresolved="isUnresolved(item)" />
            </dd>
          </div>
          <div>
            <dt class="text-xs text-muted-foreground">
              {{ $t('Laikas') }}
            </dt>
            <dd class="mt-1 tabular-nums">
              {{ reservationPeriod(item) }}
            </dd>
          </div>
          <div>
            <dt class="text-xs text-muted-foreground">
              {{ $t('Ištekliai') }}
            </dt>
            <dd class="mt-1">
              <ReservationResourceChips :resources="item.resources" :muted-foreign="isAdministrator(item)" :max="12" />
            </dd>
          </div>
        </dl>
        <template v-if="isDeleted">
          <div class="flex flex-col gap-2 pt-2">
            <Button variant="outline" voice="sentence" @click="restoreReservation(item)">
              <RotateCcw aria-hidden="true" class="size-4" />
              {{ $t('Atkurti') }}
            </Button>
            <Button variant="ghost" voice="sentence" class="text-destructive hover:text-destructive" @click="targetReservationToForceDelete = item">
              <Trash2 aria-hidden="true" class="size-4" />
              {{ $t('Ištrinti visam laikui') }}
            </Button>
          </div>
        </template>
        <ReservationRowActions v-else :reservation="item" class="flex-wrap" @decide="openDecision" />
      </section>
    </template>

    <template #empty>
      <EmptyState
        :mode="isFiltered ? 'no-results' : 'empty'"
        :icon="ReservationIcon"
        :title="isDeleted ? $t('Ištrintų rezervacijų nėra') : $t('Rezervacijų dar nėra')"
        :description="isDeleted ? $t('Šiukšliadėžėje nėra pašalintų rezervacijų.') : $t('reservations.resource.reservations_empty')"
        :action-label="canCreate && !isDeleted ? $t('Nauja rezervacija') : undefined"
        @action="router.visit(route('reservations.create'))"
      />
    </template>
    <template #bulk-actions>
      <Button variant="brand" size="sm" voice="sentence" @click="openDecision('approved', selectedReservations)">
        <Check aria-hidden="true" />
        {{ $t('reservations.actions.approve') }}
      </Button>
      <Button
        v-if="selectedReservations.some(reservation => getRejectablePivotIds(reservation).length > 0)"
        variant="outline"
        size="sm"
        voice="sentence"
        @click="openDecision('rejected', selectedReservations)"
      >
        <X aria-hidden="true" />
        {{ $t('reservations.actions.reject') }}
      </Button>
      <Button variant="outline" size="sm" voice="sentence" @click="openDecision('resolved', selectedReservations)">
        <CheckCheck aria-hidden="true" />
        {{ $t('reservations.actions.resolve') }}
      </Button>
    </template>
  </CollectionPage>

  <ReservationDecisionDialog
    v-model:open="decisionOpen"
    :decision
    :targets="decisionTargets"
    @done="onDecided"
  />

  <ConfirmDialog
    :open="targetReservationToForceDelete !== null"
    :title="$t('Ištrinti rezervaciją visam laikui?')"
    :description="$t('Šis veiksmas negrįžtamas. Rezervacija bus visiškai pašalinta.')"
    :confirm-label="$t('Ištrinti visam laikui')"
    destructive
    @update:open="!$event && (targetReservationToForceDelete = null)"
    @confirm="forceDeleteReservation"
  />
</template>

<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { ArrowRight, Check, CheckCheck, Plus, RotateCcw, Trash2, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import type { CollectionColumn, CollectionQuickFilter } from '@/Components/Collection/types';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { ConfirmDialog, EmptyState } from '@/Components/Patterns';
import ReservationDecisionDialog from '@/Components/Reservations/ReservationDecisionDialog.vue';
import ReservationResourceChips from '@/Components/Reservations/ReservationResourceChips.vue';
import ReservationRowActions from '@/Components/Reservations/ReservationRowActions.vue';
import type { ReservationDecision } from '@/Components/Reservations/types';
import { useReservationCart } from '@/Components/Reservations/useReservationCart';
import ReservationPeriod from '@/Components/SmallElements/ReservationPeriod.vue';
import ReservationStateSummary from '@/Components/Tag/ReservationStateSummary.vue';
import { Button } from '@/Components/ui/button';
import { ReservationIcon } from '@/Components/icons';
import { useDatabaseCollectionSource, type DatabaseFacetDefinition } from '@/Composables/useCollectionSource';
import { formatDate } from '@/Utils/dateTime';
import { capitalize } from '@/Utils/String';
import {
  getBacktrackAction,
  getReservationStates,
  getRejectablePivotIds,
  isReservationSelectable,
  isReservationUnresolved,
  type DashboardReservation,
} from '@/Utils/ReservationStatus';

const entityName = 'reservation';

const props = defineProps<{
  reservations: { data: DashboardReservation[]; meta: { total: number; per_page: number; current_page: number; last_page: number } };
  deletedCount: number;
  managesResources?: boolean;
  showDeleted?: boolean;
  /** No list permission: every row is already the user's own, so "Mano rezervacijos" filters nothing. */
  onlyOwn?: boolean;
}>();

const isDeleted = computed(() => Boolean(props.showDeleted));
const canCreate = computed(() => Boolean(usePage().props.auth?.can?.create?.reservation));

const { items: cartItems } = useReservationCart();
const selectedIds = ref<string[]>([]);
const targetReservationToForceDelete = ref<DashboardReservation | null>(null);

const STATES = ['created', 'reserved', 'lent', 'returned', 'rejected', 'cancelled'] as const;

const facets = computed<DatabaseFacetDefinition[]>(() => [
  ...(props.onlyOwn
    ? []
    : [{
        field: 'scope',
        label: $t('Rodyti'),
        single: true,
        values: [
          { value: 'mine', label: $t('Mano rezervacijos') },
          ...(props.managesResources ? [{ value: 'administered', label: $t('Administruoju') }] : []),
        ],
      }]),
  {
    field: 'state',
    label: $t('Būsena'),
    values: STATES.map(state => ({ value: state, label: capitalize($t(`state.status.${state}`)) })),
  },
  { field: 'overdue', label: $t('Terminas'), single: true, values: [{ value: '1', label: $t('Vėluoja grąžinti') }] },
]);

const source = useDatabaseCollectionSource<DashboardReservation>({
  endpoint: route('api.v1.admin.reservations.index'),
  initial: {
    items: props.reservations.data,
    total: props.reservations.meta.total,
    perPage: props.reservations.meta.per_page,
    currentPage: props.reservations.meta.current_page,
    lastPage: props.reservations.meta.last_page,
  },
  defaultSort: 'start_time:desc',
  sortOptions: [
    { value: 'start_time:desc', label: $t('Naujausios pirmiausia') },
    { value: 'start_time:asc', label: $t('Anksčiausios pirmiausia') },
    { value: 'created_at:desc', label: $t('Naujausios sukurtos') },
  ],
  preserveUrlKeys: ['showDeleted'],
  facets: facets.value,
});

const columns = computed<CollectionColumn[]>(() => [
  { key: 'name', label: $t('Rezervacija') },
  { key: 'requester', label: $t('Prašytojas'), class: 'w-44' },
  { key: 'resources', label: $t('Ištekliai') },
  { key: 'period', label: $t('Laikas'), class: 'w-36' },
  { key: 'status', label: $t('Būsena'), class: 'w-36' },
  ...(!isDeleted.value ? [{ key: 'actions', label: $t('Veiksmai'), class: 'w-56' }] : []),
]);

// --- Quick filters: the URL state an overview number links to ---------------------------------

const asList = (value: unknown): string[] => (Array.isArray(value) ? value.map(String) : value ? [String(value)] : []);

const quickFilters = computed<CollectionQuickFilter[]>(() => {
  const filters = source.filters.value;
  const scope = asList(filters.scope);
  const states = asList(filters.state);

  return [
    ...(props.managesResources
      ? [{
          id: 'waiting',
          label: $t('Laukia sprendimo'),
          active: scope.includes('administered') && states.length === 1 && states[0] === 'created',
        }]
      : []),
    ...(props.onlyOwn ? [] : [{ id: 'mine', label: $t('Mano rezervacijos'), active: scope.includes('mine') }]),
    ...(props.managesResources
      ? [{ id: 'administered', label: $t('Administruoju'), active: scope.includes('administered') && states.length === 0 }]
      : []),
  ];
});

function toggleQuickFilter(id: string): void {
  const active = quickFilters.value.find(filter => filter.id === id)?.active ?? false;

  if (id === 'waiting') {
    source.setFilter('scope', active ? undefined : 'administered');
    source.setFilter('state', active ? undefined : ['created']);
  }
  else {
    source.setFilter('state', undefined);
    source.toggleFilter('scope', id);
  }
}

const isFiltered = computed(() => source.query.value.trim() !== '' || source.activeFilterCount.value > 0);

// --- Row presentation ---------------------------------------------------------------------------

const reservationKey = (reservation: DashboardReservation) => String(reservation.id);
const reservationPeriod = (reservation: DashboardReservation) => `${formatDate(new Date(reservation.start_time))} – ${formatDate(new Date(reservation.end_time))}`;

/** An administrator's row is scoped to the items they manage; a requester sees the whole reservation. */
const isAdministrator = (reservation: DashboardReservation) => reservation.resources.some(resource => resource.pivot.approvable);
const statesOf = (reservation: DashboardReservation) => getReservationStates(reservation, { approvableOnly: isAdministrator(reservation) });
const isUnresolved = (reservation: DashboardReservation) => isReservationUnresolved(reservation, { approvableOnly: isAdministrator(reservation) });

/** One row carries the backtrack hint, so it never shows twice on a page. */
const spotlightKey = computed(() => {
  const first = source.items.value.find(reservation => getBacktrackAction(reservation) !== null);

  return first ? reservationKey(first) : null;
});

// --- Selection and decisions ---------------------------------------------------------------------

const selectedReservations = computed(() =>
  source.items.value.filter(reservation => selectedIds.value.includes(reservationKey(reservation)) && isReservationSelectable(reservation)),
);

const decisionOpen = ref(false);
const decision = ref<ReservationDecision>('approved');
const decisionTargets = ref<DashboardReservation[]>([]);

function openDecision(next: ReservationDecision, target: DashboardReservation | DashboardReservation[]): void {
  decision.value = next;
  decisionTargets.value = Array.isArray(target) ? target : [target];
  decisionOpen.value = true;
}

function onDecided(): void {
  selectedIds.value = [];
  source.refresh();
}

function restoreReservation(reservation: DashboardReservation): void {
  router.patch(route('reservations.restore', reservation.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      source.refresh();
      toast.success($t('Rezervacija atkurta.'));
    },
    onError: () => {
      toast.error($t('Nepavyko atkurti rezervacijos.'));
    },
  });
}

function forceDeleteReservation(): void {
  if (!targetReservationToForceDelete.value) return;
  const { id } = targetReservationToForceDelete.value;
  targetReservationToForceDelete.value = null;

  router.delete(route('reservations.forceDelete', id), {
    preserveScroll: true,
    onSuccess: () => {
      source.refresh();
      toast.success($t('Rezervacija ištrinta visam laikui.'));
    },
    onError: () => {
      toast.error($t('Nepavyko ištrinti rezervacijos.'));
    },
  });
}
</script>
