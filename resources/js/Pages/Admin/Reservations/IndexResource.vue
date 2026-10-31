<template>
  <div>
    <CollectionPage
      :row-actions
      :source
      :quick-filters
      @quick-filter="toggleQuickFilter"
      collection="resources"
      entity-type="resource"
      :eyebrow
      :title="capitalize($tChoice('entities.resource.model', 2))"
      :lead="$t('reservations.resource.index_lead')"
      default-view="cards"
      :available-views="['cards', 'rows', 'table', 'preview']"
      :item-key="resourceKey"
      @row-action="(_item, key) => collectionActions.select(key)"
      :columns
      :search-placeholder="$t('Ieškoti išteklių')"
    >
      <template #actions>
        <Button
          v-if="canReserve"
          variant="outline"
          voice="sentence"
          class="u-touch"
          :title="periodLabel"
          data-testid="resource-availability-period"
          @click="openSheet"
        >
          <CalendarClock aria-hidden="true" />
          {{ period ? formatReservationPeriod(period.start, period.end) : $t('reservations.cart.pick_period') }}
        </Button>
        <Button v-if="canCreate" as-child variant="brand">
          <Link :href="route('resources.create')">
            <Plus aria-hidden="true" />
            {{ $t('Naujas išteklius') }}
          </Link>
        </Button>
      </template>

      <template #card="{ item, actions: recordActions }">
        <!-- The name link stretches over the whole card; the add button sits above it. -->
        <article
          :class="[
            'group relative flex h-full flex-col border border-border bg-card transition-colors hover:border-foreground/40',
            'has-[[data-card-link]:focus-visible]:outline-2 has-[[data-card-link]:focus-visible]:outline-offset-2 has-[[data-card-link]:focus-visible]:outline-ring',
          ]"
          data-slot="resource-collection-card"
        >
          <div
            class="flex aspect-[4/3] items-center justify-center overflow-hidden border-b border-border bg-secondary"
          >
            <img
              v-if="item.image_url"
              :src="item.image_url"
              alt=""
              loading="lazy"
              class="size-full object-cover"
            >
            <EntityTypeMark v-else type="resource" size="xl" icon-only />
          </div>

          <div class="flex flex-1 flex-col gap-1.5 p-4">
            <Link
              :href="route('resources.show', item.id)"
              prefetch
              data-card-link
              class="line-clamp-2 text-base font-medium text-foreground outline-none after:absolute after:inset-0 after:content-[''] group-hover:text-brand"
            >
              <SearchMatch :match="matchTitle(nameOf(item), item._searchTitleMatches)" :title="nameOf(item)" />
            </Link>
            <p class="flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-muted-foreground">
              <span v-if="getSearchTranslation(item, 'category_name')"><SearchMatch :match="highlightText(getSearchTranslation(item, 'category_name'), item._searchFieldMatches?.category_name)" :title="getSearchTranslation(item, 'category_name')" /></span>
              <span v-if="item.tenant_shortname">{{ item.tenant_shortname }}</span>
              <span v-if="item.location">{{ item.location }}</span>
            </p>

            <div class="mt-auto flex flex-wrap items-center justify-between gap-2 pt-3">
              <StatusBadge v-if="!item.is_reservable" :status="notReservableStatus" />
              <template v-else>
                <span
                  class="text-sm tabular-nums"
                  :class="isFullyBooked(item) ? 'font-medium text-status-attention' : 'text-muted-foreground'"
                  data-slot="resource-availability"
                >{{ availabilityLabel(item) }}</span>
                <div v-if="canReserve" class="relative z-10">
                  <AddToReservationButton :resource-id="String(item.id)" />
                </div>
              </template>
            </div>
            <CollectionRowActions :actions="recordActions" class="relative z-10 mt-3" @select="key => collectionActions.select(key)" />
          </div>
        </article>
      </template>

      <template #row="{ item, actions: recordActions }">
        <div class="relative flex items-center gap-4 px-2 py-3 sm:px-4" data-slot="resource-collection-row">
          <Link
            :href="route('resources.show', item.id)"
            prefetch
            data-collection-open
            class="flex min-w-0 flex-1 items-center gap-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
          >
            <img
              v-if="item.image_url"
              :src="item.image_url"
              alt=""
              loading="lazy"
              class="size-12 shrink-0 border border-border object-cover"
            >
            <EntityTypeMark v-else type="resource" size="lg" icon-only class="size-12 justify-center" />
            <span class="min-w-0 flex-1">
              <span class="block truncate text-base font-medium text-foreground">
                <SearchMatch :match="matchTitle(nameOf(item), item._searchTitleMatches)" :title="nameOf(item)" />
              </span>
              <span class="mt-0.5 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-muted-foreground">
                <span v-if="getSearchTranslation(item, 'category_name')"><SearchMatch :match="highlightText(getSearchTranslation(item, 'category_name'), item._searchFieldMatches?.category_name)" :title="getSearchTranslation(item, 'category_name')" /></span>
                <span v-if="item.tenant_shortname">{{ item.tenant_shortname }}</span>
                <span v-if="item.location">{{ item.location }}</span>
                <span
                  v-if="item.is_reservable"
                  class="tabular-nums"
                  :class="isFullyBooked(item) && 'font-medium text-status-attention'"
                  data-slot="resource-availability"
                >{{ availabilityLabel(item) }}</span>
                <span v-else-if="item.capacity" class="tabular-nums">{{ $t('Kiekis: :count', { count: String(item.capacity) }) }}</span>
              </span>
            </span>
          </Link>
          <StatusBadge v-if="!item.is_reservable" :status="notReservableStatus" class="shrink-0" />
          <div v-else-if="canReserve" class="shrink-0">
            <AddToReservationButton :resource-id="String(item.id)" />
          </div>
          <CollectionRowActions :spotlight="item === source?.items.value[0]" :actions="recordActions" @select="key => collectionActions.select(key)" />
        </div>
      </template>

      <template #cell="{ item, column }">
        <Link
          v-if="column.key === 'name'"
          :href="route('resources.show', item.id)"
          prefetch
          class="font-medium hover:text-brand"
        >
          <SearchMatch :match="matchTitle(nameOf(item), item._searchTitleMatches)" :title="nameOf(item)" />
        </Link>
        <template v-else-if="column.key === 'category'">
          <SearchMatch v-if="getSearchTranslation(item, 'category_name')" :match="highlightText(getSearchTranslation(item, 'category_name'), item._searchFieldMatches?.category_name)" :title="getSearchTranslation(item, 'category_name')" />
          <template v-else>
            —
          </template>
        </template>
        <template v-else-if="column.key === 'tenant'">
          {{ item.tenant_shortname ?? '—' }}
        </template>
        <span v-else-if="column.key === 'availability'" class="tabular-nums" :class="isFullyBooked(item) && 'text-status-attention'">
          {{ item.is_reservable ? availabilityLabel(item) : (item.capacity ?? '—') }}
        </span>
        <template v-else-if="column.key === 'status'">
          <StatusBadge v-if="!item.is_reservable" :status="notReservableStatus" />
          <AddToReservationButton v-else-if="canReserve" :resource-id="String(item.id)" />
        </template>
      </template>

      <template #preview="{ item, actions: recordActions }">
        <ResourcePreview
          :key="item.id"
          :resource="item"
          :availability="availability.get(String(item.id))"
          show-availability-box
          show-upcoming-reservations
          show-managers
          allow-image-lightbox
        >
          <template #actions>
            <AddToReservationButton
              v-if="item.is_reservable && canReserve"
              :resource-id="String(item.id)"
              size="sm"
              variant="brand"
              without-draft="reserve"
              :label="$t('reservations.cart.add_to_reservation')"
            />
            <CollectionRowActions :actions="recordActions" full-labels @select="key => collectionActions.select(key)" />
          </template>
        </ResourcePreview>
      </template>

      <template #empty>
        <EmptyState
          mode="empty"
          :icon="ResourceIcon"
          :title="$t('Išteklių dar nėra')"
          :description="$t('Čia atsiras rezervuojami ištekliai. Pridėk pirmąjį, kad kiti galėtų jį užsisakyti.')"
          :action-label="canCreate ? $t('Naujas išteklius') : undefined"
          :action-href="canCreate ? route('resources.create') : undefined"
        />
      </template>
    </CollectionPage>

    <ReservationCartBar />
    <ReservationCartSheet />
    <CollectionConfirmAction :dialog="collectionActions.dialog.value" @confirm="collectionActions.confirm" @cancel="collectionActions.pending.value = null" />
  </div>
</template>

<script setup lang="ts">
import { Trash2, Pencil, ArrowUpRight, CalendarClock, Plus } from '@lucide/vue';
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { capitalize, computed, onMounted, watch } from 'vue';

import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import { useCollectionRecordActions } from '@/Composables/useCollectionRecordActions';
import { useRowAbilities } from '@/Composables/useRowAbilities';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import type { AdminRecordAction } from '@/Features/Admin/RecordActions/types';
import { getSearchTranslation } from '@/Composables/useTranslatedTitle';
import { highlightText, matchTitle } from '@/Shared/Search/matches';
import SearchMatch from '@/Components/ui/SearchMatch.vue';
import type { CollectionColumn, CollectionQuickFilter } from '@/Components/Collection/types';
import EntityTypeMark from '@/Components/EntityTypeMark.vue';
import { ResourceIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { EmptyState, StatusBadge } from '@/Components/Patterns';
import AddToReservationButton from '@/Components/Reservations/AddToReservationButton.vue';
import ResourcePreview from '@/Components/Reservations/ResourcePreview.vue';
import ReservationCartBar from '@/Components/Reservations/ReservationCartBar.vue';
import ReservationCartSheet from '@/Components/Reservations/ReservationCartSheet.vue';
import { formatReservationPeriod } from '@/Components/Reservations/reservationPeriod';
import type { ReservationCart } from '@/Components/Reservations/types';
import { useReservationCart } from '@/Components/Reservations/useReservationCart';
import { Button } from '@/Components/ui/button';
import { useTypesenseCollectionSource } from '@/Composables/useCollectionSource';
import { notReservableStatus } from '@/Constants/statuses';
import { useResourceAvailability } from '@/Features/Admin/AdminSearch/Composables/useResourceAvailability';
import type { ResourceSearchResult } from '@/Shared/Search/types';

const props = defineProps<{
  reservationCart: ReservationCart | null;
  mainTenantId: number | null;
  managedTenantIds: number[];
}>();

const page = usePage();

const canCreate = computed(() => Boolean(page.props.auth?.can?.create?.resource));
const canReserve = computed(() => Boolean(page.props.auth?.can?.create?.reservation));

const eyebrow = computed(() => $t('shell.workspaces.rezervacijos.title'));

const source = useTypesenseCollectionSource<ResourceSearchResult>({
  collection: 'resources',
  preserveUrlKeys: ['view', 'item', 'cart'],
  // The facet chip reads "Ar skolinamas: Taip" — name the value as the badge does (U10).
  valueLabel: (field, value) => {
    if (field === 'tenant_id') {
      return page.props.tenants?.find(tenant => String(tenant.id) === value)?.shortname;
    }

    return field === 'is_reservable' ? (value === 'true' ? $t('Rezervuojamas') : $t('Nerezervuojamas')) : undefined;
  },
});

const managedIds = computed(() => props.managedTenantIds.map(String));
const managedActive = computed(() => {
  const selected = source.filters.value.tenant_id;

  return Array.isArray(selected) && selected.length === managedIds.value.length
    && managedIds.value.every(id => selected.map(String).includes(id));
});
const quickFilters = computed<CollectionQuickFilter[]>(() => managedIds.value.length > 0 ? [{
  id: 'managed',
  label: $t('Mano administruojami'),
  active: managedActive.value,
  spotlight: {
    id: 'managed-resources-v1',
    title: $t('Mano administruojami'),
    description: $t('Rodyk išteklius tų padalinių, kurių rezervacijas administruoji.'),
  },
}] : []);

function toggleQuickFilter(id: string): void {
  if (id === 'managed' && managedIds.value.length > 0) {
    source.setFilter('tenant_id', managedActive.value ? undefined : managedIds.value);
  }
}

const resourceKey = (resource: ResourceSearchResult) => String(resource.id);
const nameOf = (resource: ResourceSearchResult) => getSearchTranslation(resource, 'name') || $t('Be pavadinimo');

const columns = computed<CollectionColumn[]>(() => [
  { key: 'name', label: capitalize($tChoice('entities.resource.model', 1)), sortField: 'name_lt' },
  { key: 'category', label: $t('Kategorija') },
  { key: 'tenant', label: $t('Padalinys'), class: 'w-32' },
  { key: 'availability', label: $t('reservations.cart.free_column'), class: 'w-32' },
  { key: 'status', label: '', class: 'w-48' },
]);

// --- Availability: for the cart's period, or right now until one is picked ---------------------

const { period, hasPeriod, openSheet } = useReservationCart();

// Captured once: a "now" that ticked would invalidate the availability cache on every render.
const openedAt = Date.now();
const availabilityRange = computed(() => period.value ?? { start: openedAt, end: openedAt + 60_000 });

const { availability, ensure } = useResourceAvailability(() => availabilityRange.value);

const reservableIds = computed(() => source.items.value.filter(item => item.is_reservable).map(item => String(item.id)));

watch([reservableIds, availabilityRange], ([ids]) => {
  if (canReserve.value && ids.length > 0) {
    void ensure(ids.slice(0, 100));
  }
}, { immediate: true });

const freeOf = (item: ResourceSearchResult) => availability.value.get(String(item.id))?.lowestCapacityAtDateTimeRange;

const isFullyBooked = (item: ResourceSearchResult) => {
  const free = freeOf(item);

  return free !== undefined && free <= 0;
};

const availabilityLabel = (item: ResourceSearchResult) => {
  const free = freeOf(item);

  return free === undefined
    ? $t('reservations.cart.total', { capacity: String(item.capacity ?? '—') })
    : $t('reservations.cart.free_of', { available: String(Math.max(0, free)), capacity: String(item.capacity) });
};

const periodLabel = computed(() => (period.value
  ? $t('reservations.cart.availability_for', { period: formatReservationPeriod(period.value.start, period.value.end) })
  : $t('reservations.cart.availability_now')));

// Notifications and "+ Sukurti" land here with `?cart=open` to continue a started reservation.
onMounted(() => {
  const params = new URLSearchParams(window.location.search);

  if (params.get('cart') !== 'open') {
    return;
  }

  openSheet();
  params.delete('cart');
  const query = params.toString();
  window.history.replaceState(window.history.state, '', `${window.location.pathname}${query ? `?${query}` : ''}`);
});
const rowActions = (item: ResourceSearchResult): AdminRecordAction[] => [
  { key: 'open', label: $t('Atidaryti'), icon: ArrowUpRight, role: 'open' as const, href: route('resources.show', item.id) },
  ...(rowAbilities.can(item.id, 'update') ? [{ key: 'edit', label: $t('Redaguoti'), icon: Pencil, role: 'edit' as const, href: route('resources.edit', item.id) }] : []),
  ...(rowAbilities.can(item.id, 'delete') ? [{ key: `delete:${item.id}:${nameOf(item)}`, label: $t('Ištrinti'), icon: Trash2, destructive: true }] : []),
];

const rowAbilities = useRowAbilities('resources', () => source.items.value.map(item => item.id));
const collectionActions = useCollectionRecordActions({
  source,
  routePrefix: 'resources',
  onMutated: id => rowAbilities.invalidate([id]),
});
</script>
