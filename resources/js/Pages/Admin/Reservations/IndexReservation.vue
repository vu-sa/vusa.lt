<template>
  <CollectionPage
    :source
    collection="reservations"
    entity-type="reservation"
    :eyebrow="$t('shell.workspaces.reservations.title') + ' · ' + $t('shell.sections.rezervacijos')"
    :title="$t('Rezervacijos')"
    :lead="$t('Stebėk prašymus, patvirtink išdavimą ir pažymėk grąžinimą.')"
    default-view="table"
    :item-key="reservationKey"
    :columns
    :search-placeholder="$t('Ieškoti rezervacijų')"
  >
    <template #actions>
      <Button v-if="deletedCount > 0" as-child variant="ghost">
        <Link :href="route('reservations.index', { showDeleted: 'true' })">
          <Trash2 aria-hidden="true" />
          {{ $t('Ištrinti') }} ({{ deletedCount }})
        </Link>
      </Button>
      <Button v-if="canCreate" as-child variant="brand">
        <Link :href="route('reservations.create')">
          <Plus aria-hidden="true" />
          {{ $t('Nauja rezervacija') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex min-h-16 items-center gap-3 px-3 py-3 sm:px-4">
        <StatusBadge v-if="primaryStatus(item)" :status="primaryStatus(item)!" class="shrink-0" />
        <div class="min-w-0 flex-1">
          <Link :href="route('reservations.show', item.id)" data-collection-open class="font-medium hover:text-brand">
            {{ item.name }}
          </Link>
          <p class="mt-0.5 truncate text-sm text-muted-foreground">
            {{ resourceNames(item) }} · {{ reservationPeriod(item) }}
          </p>
        </div>
        <span class="text-xs tabular-nums text-muted-foreground">{{ item.users?.length ?? 0 }}</span>
      </article>
    </template>

    <template #cell="{ item, column }">
      <Checkbox
        v-if="column.key === 'select'"
        :model-value="selectedIds.includes(reservationKey(item))"
        :aria-label="$t('Pasirinkti :name', { name: item.name })"
        @update:model-value="toggleReservation(item)"
      />
      <Link
        v-else-if="column.key === 'name'"
        :href="route('reservations.show', item.id)"
        data-collection-open
        prefetch
        class="font-medium hover:text-brand"
      >
        {{ item.name }}
      </Link>
      <span v-else-if="column.key === 'resources'">{{ resourceNames(item) }}</span>
      <span v-else-if="column.key === 'period'" class="tabular-nums">{{ reservationPeriod(item) }}</span>
      <StatusBadge v-else-if="column.key === 'status' && primaryStatus(item)" :status="primaryStatus(item)!" />
      <span v-else-if="column.key === 'status'">—</span>
      <span v-else-if="column.key === 'managers'" class="tabular-nums">{{ item.users?.length ?? 0 }}</span>
    </template>

    <template #preview="{ item }">
      <section class="flex flex-col gap-5 p-5">
        <div>
          <p class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">{{ $t('Rezervacija') }}</p>
          <Link :href="route('reservations.show', item.id)" class="mt-1 block text-lg font-semibold hover:text-brand">
            {{ item.name }}
          </Link>
          <p v-if="item.description" class="mt-2 text-sm text-muted-foreground">{{ item.description }}</p>
        </div>
        <dl class="grid gap-3 border-y border-border py-4 text-sm">
          <div>
            <dt class="text-xs text-muted-foreground">{{ $t('Laikas') }}</dt>
            <dd class="mt-1 tabular-nums">{{ reservationPeriod(item) }}</dd>
          </div>
          <div>
            <dt class="text-xs text-muted-foreground">{{ $t('Ištekliai') }}</dt>
            <dd class="mt-1">{{ resourceNames(item) }}</dd>
          </div>
        </dl>
        <Button v-if="approvableResourceIds(item).length" variant="brand" :disabled="approving" @click="approve(item)">
          <Check aria-hidden="true" />
          {{ $t('Patvirtinti') }}
        </Button>
      </section>
    </template>

    <template #empty>
      <EmptyState
        mode="empty"
        :icon="ReservationIcon"
        :title="$t('Rezervacijų dar nėra')"
        :description="$t('Čia matysi patalpų ir įrangos prašymus bei jų eigą.')"
        :action-label="canCreate ? $t('Nauja rezervacija') : undefined"
        @action="router.visit(route('reservations.create'))"
      />
    </template>
  </CollectionPage>

  <CollectionSelectionBar
    v-if="selectedResourceIds.length"
    :count="selectedResourceIds.length"
    :count-label="$t('Pasirinkta')"
    @clear="selectedIds = []"
  >
    <Button variant="brand" size="sm" :disabled="approving" @click="approveSelected">
      <Check aria-hidden="true" />
      {{ $t('Patvirtinti') }}
    </Button>
  </CollectionSelectionBar>
</template>

<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Check, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

import type { CollectionColumn } from '@/Components/Collection/types';
import CollectionSelectionBar from '@/Components/Collection/CollectionSelectionBar.vue';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { EmptyState, StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { ReservationIcon } from '@/Components/icons';
import { reservationResourceStatuses, type ReservationResourceStatus, type StatusPresentation } from '@/Constants/statuses';
import { useDatabaseCollectionSource } from '@/Composables/useCollectionSource';
import { formatDate } from '@/Utils/dateTime';

type ReservationResource = App.Entities.Resource & {
  pivot?: App.Entities.ReservationResource & { approvable?: boolean };
};
type Reservation = App.Entities.Reservation & { resources?: ReservationResource[] };

const props = defineProps<{
  reservations: { data: Reservation[]; meta: { total: number; per_page: number; current_page: number; last_page: number } };
  deletedCount: number;
}>();

const canCreate = computed(() => Boolean(usePage().props.auth?.can?.create?.reservation));
const selectedIds = ref<string[]>([]);
const approving = ref(false);

const source = useDatabaseCollectionSource<Reservation>({
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
});

const columns = computed<CollectionColumn[]>(() => [
  { key: 'select', label: $t('Pasirinkti'), class: 'w-12' },
  { key: 'name', label: $t('Rezervacija') },
  { key: 'resources', label: $t('Ištekliai') },
  { key: 'period', label: $t('Laikas'), class: 'w-48' },
  { key: 'status', label: $t('Būsena'), class: 'w-36' },
  { key: 'managers', label: $t('Valdytojai'), class: 'w-24' },
]);

const reservationKey = (reservation: Reservation) => String(reservation.id);
const resourceNames = (reservation: Reservation) => reservation.resources?.map(resource => resource.name).join(', ') || '—';
const reservationPeriod = (reservation: Reservation) => `${formatDate(new Date(reservation.start_time))} – ${formatDate(new Date(reservation.end_time))}`;

function primaryStatus(reservation: Reservation): StatusPresentation | null {
  const state = reservation.resources?.find(resource => resource.pivot?.state && resource.pivot.state !== 'returned')?.pivot?.state as ReservationResourceStatus | undefined;
  return state ? reservationResourceStatuses[state] : null;
}

function approvableResourceIds(reservation: Reservation): string[] {
  return reservation.resources
    ?.filter(resource => resource.pivot?.approvable && ['created', 'reserved', 'lent'].includes(resource.pivot.state))
    .map(resource => String(resource.pivot?.id)) ?? [];
}

const selectedResourceIds = computed(() => source.items.value
  .filter(reservation => selectedIds.value.includes(reservationKey(reservation)))
  .flatMap(approvableResourceIds));

function toggleReservation(reservation: Reservation): void {
  const id = reservationKey(reservation);
  selectedIds.value = selectedIds.value.includes(id)
    ? selectedIds.value.filter(selected => selected !== id)
    : [...selectedIds.value, id];
}

function advance(state: ReservationResourceStatus): ReservationResourceStatus {
  return ({ created: 'reserved', reserved: 'lent', lent: 'returned' } as Partial<Record<ReservationResourceStatus, ReservationResourceStatus>>)[state] ?? state;
}

function approve(reservation: Reservation): void {
  approveIds(approvableResourceIds(reservation));
}

function approveSelected(): void {
  approveIds(selectedResourceIds.value);
}

function approveIds(ids: string[]): void {
  if (!ids.length) return;

  const before = structuredClone(source.items.value) as Reservation[];
  approving.value = true;
  source.items.value.forEach((reservation) => {
    reservation.resources?.forEach((resource) => {
      if (resource.pivot && ids.includes(String(resource.pivot.id))) {
        resource.pivot.state = advance(resource.pivot.state as ReservationResourceStatus);
      }
    });
  });

  router.post(route('approvals.bulkStore'), {
    approvable_type: 'reservation_resource',
    approvable_ids: ids,
    decision: 'approved',
    step: 1,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      selectedIds.value = [];
      toast.success($t('Rezervacija patvirtinta.'));
    },
    onError: () => {
      source.replaceItems(before);
      toast.error($t('Nepavyko patvirtinti rezervacijos.'));
    },
    onFinish: () => { approving.value = false; },
  });
}
</script>
