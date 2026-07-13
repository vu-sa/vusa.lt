<template>
  <AdminContentPage>
    <Head>
      <title>{{ $t('reservations.dashboard.title') }}</title>
    </Head>

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="max-w-xl">
          <h1 class="mb-1 text-3xl font-bold tracking-tight">
            {{ $t('reservations.dashboard.title') }}
          </h1>
          <p class="text-sm text-muted-foreground">
            {{ $t('reservations.dashboard.subtitle') }}
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <Button variant="ghost" size="sm" @click="showHelpModal = true">
            <Info class="size-4" />
            {{ $t('reservations.dashboard.rules') }}
          </Button>
          <Link :href="route('resources.index')">
            <Button variant="ghost" size="sm">
              <Box class="size-4" />
              {{ capitalize($tChoice('entities.resource.model', 2)) }}
            </Button>
          </Link>
          <Link :href="route('reservations.create')">
            <Button size="sm">
              <Plus class="size-4" />
              {{ $t('Nauja rezervacija') }}
            </Button>
          </Link>
        </div>
      </div>

      <ReservationKpiStrip v-model="statusFilter" :stats />

      <Tabs v-model="currentTab" class="space-y-4">
        <TabsList class="h-auto flex-wrap gap-2">
          <TabsTrigger value="mine" class="gap-2">
            {{ $t('reservations.dashboard.tabs.mine') }}
            <Badge v-if="activeMineCount" variant="secondary" size="tiny">
              {{ activeMineCount }}
            </Badge>
          </TabsTrigger>
          <!-- Shown to anyone who manages resources, even before anything has been reserved. -->
          <TabsTrigger v-if="managedTenants.length > 0" value="administered" class="gap-2">
            {{ $t('reservations.dashboard.tabs.administered') }}
            <Badge v-if="activeAdministeredCount" variant="secondary" size="tiny">
              {{ activeAdministeredCount }}
            </Badge>
          </TabsTrigger>
        </TabsList>

        <!-- Filters are shared by both tabs. -->
        <div class="flex flex-wrap items-center gap-3">
          <div class="relative min-w-[220px] flex-1 sm:max-w-xs">
            <Search class="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              v-model="search"
              class="pl-8"
              :placeholder="$t('reservations.dashboard.filters.search')"
            />
          </div>

          <Select v-model="statusFilter">
            <SelectTrigger class="w-[190px]">
              <!-- The dot repeats on the trigger, so the current status filter is obvious without
                   having to open the dropdown and read which row is ticked. -->
              <span class="flex items-center gap-2">
                <span v-if="statusFilter === 'active'" class="flex shrink-0 -space-x-1">
                  <span
                    v-for="dot in ACTIVE_STATUS_DOT_CLASSES"
                    :key="dot"
                    :class="['size-2 rounded-full ring-1 ring-background', dot]"
                  />
                </span>
                <span
                  v-else-if="selectedStatusDot"
                  :class="['size-2 shrink-0 rounded-full', selectedStatusDot]"
                />
                <SelectValue :placeholder="$t('reservations.dashboard.filters.status')" />
              </span>
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="active">
                <span class="flex items-center gap-2">
                  <!-- Active covers every open state, so show them all rather than pick one. -->
                  <span class="flex shrink-0 -space-x-1">
                    <span
                      v-for="dot in ACTIVE_STATUS_DOT_CLASSES"
                      :key="dot"
                      :class="['size-2 rounded-full ring-1 ring-background', dot]"
                    />
                  </span>
                  {{ $t('reservations.dashboard.filters.status_active') }}
                </span>
              </SelectItem>
              <SelectItem value="all">
                <span class="flex items-center gap-2">
                  <span class="size-2 shrink-0 rounded-full border border-dashed border-muted-foreground" />
                  {{ $t('reservations.dashboard.filters.status_all') }}
                </span>
              </SelectItem>
              <SelectSeparator />
              <SelectItem v-for="status in STATUS_OPTIONS" :key="status" :value="status">
                <span class="flex items-center gap-2">
                  <span :class="['size-2 shrink-0 rounded-full', getStatusDotClass(status)]" />
                  {{ capitalize($t(`state.status.${status}`)) }}
                </span>
              </SelectItem>
            </SelectContent>
          </Select>

          <Select v-if="managedTenants.length > 1" v-model="tenantFilter">
            <SelectTrigger class="w-[170px]">
              <SelectValue :placeholder="$t('reservations.dashboard.filters.tenant')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">
                {{ $t('reservations.dashboard.filters.tenant_all') }}
              </SelectItem>
              <SelectItem v-for="tenant in managedTenants" :key="tenant.id" :value="String(tenant.id)">
                {{ tenant.shortname }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <TabsContent value="mine">
          <ReservationsTable
            mode="mine"
            :reservations="filteredMine"
            :empty-message="$t('reservations.dashboard.empty.mine')"
            :filters-applied="hasActiveFilters"
            @clear-filters="clearFilters"
          />
        </TabsContent>

        <TabsContent value="administered">
          <ReservationsTable
            mode="administered"
            :reservations="filteredAdministered"
            :empty-message="$t('reservations.dashboard.empty.administered')"
            :filters-applied="hasActiveFilters"
            @clear-filters="clearFilters"
          />
        </TabsContent>
      </Tabs>
    </div>

    <Dialog v-model:open="showHelpModal">
      <DialogContent class="max-h-[85vh] max-w-3xl overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{{ $t('reservations.dashboard.rules') }}</DialogTitle>
        </DialogHeader>
        <MdSuspenseWrapper directory="reservations" :locale="$page.props.app.locale" file="help" />
      </DialogContent>
    </Dialog>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { useStorage } from '@vueuse/core';
import { Box, Info, Plus, Search } from 'lucide-vue-next';

import ReservationKpiStrip from './Partials/ReservationKpiStrip.vue';
import ReservationsTable from './Partials/ReservationsTable.vue';

import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import MdSuspenseWrapper from '@/Features/MarkdownGetterFromDocs/MdSuspenseWrapper.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectSeparator,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { ReservationIconFilled } from '@/Components/icons';
import { capitalize } from '@/Utils/String';
import {
  ACTIVE_STATUS_DOT_CLASSES,
  computeReservationStats,
  getStatusDotClass,
  isReservationActive,
  matchesStatusFilter,
  type DashboardReservation,
  type ReservationRowStatus,
  type StatusFilter,
} from '@/Utils/ReservationStatus';

const props = defineProps<{
  myReservations: DashboardReservation[];
  administeredReservations: DashboardReservation[];
  managedTenants: { id: string; shortname: string }[];
}>();

usePageBreadcrumbs([
  { label: $t('reservations.dashboard.title'), icon: ReservationIconFilled },
]);

const currentTab = useStorage('reservations-dashboard-tab', 'mine');

const STATUS_OPTIONS: ReservationRowStatus[] = [
  'created',
  'reserved',
  'lent',
  'returned',
  'rejected',
  'cancelled',
];

const search = ref('');
const tenantFilter = ref<string>('all');

/**
 * Defaults to the open work. Reservations that have been returned, rejected or cancelled are
 * archive: showing them first buries the handful of rows that actually need a decision.
 */
const statusFilter = ref<StatusFilter>('active');

const matchesSearch = (reservation: DashboardReservation) => {
  const term = search.value.trim().toLowerCase();

  if (!term) {
    return true;
  }

  return reservation.name.toLowerCase().includes(term)
    || reservation.resources.some(resource => resource.name.toLowerCase().includes(term))
    || reservation.users.some(user => user.name?.toLowerCase().includes(term));
};

const matchesTenant = (reservation: DashboardReservation) =>
  tenantFilter.value === 'all'
  || reservation.resources.some(resource => String(resource.tenant?.id) === tenantFilter.value);

/** Search and tenant, but not status — the KPI counts are taken from this, before the status filter. */
const narrowed = (reservations: DashboardReservation[]) =>
  reservations.filter(reservation => matchesSearch(reservation) && matchesTenant(reservation));

/**
 * A row's status is scoped to the pivots the viewer can act on, so the filter has to be evaluated
 * the same way — otherwise "Overdue" could surface a row whose badge reads "Lent".
 */
const filterFor = (reservations: DashboardReservation[], approvableOnly: boolean) =>
  narrowed(reservations).filter(reservation =>
    matchesStatusFilter(reservation, statusFilter.value, { approvableOnly }),
  );

const filteredMine = computed(() => filterFor(props.myReservations, false));
const filteredAdministered = computed(() => filterFor(props.administeredReservations, true));

/**
 * The KPI tiles filter the table below them, so they have to count the same thing the table shows:
 * reservations in the active tab, already narrowed by search and unit. Anything else and clicking
 * "Overdue 10" lands you on a table of 2 rows.
 */
const isAdministeredTab = computed(() => currentTab.value === 'administered');

const stats = computed(() => computeReservationStats(
  isAdministeredTab.value ? narrowed(props.administeredReservations) : narrowed(props.myReservations),
  { approvableOnly: isAdministeredTab.value },
));

/** `active` is the default, so it does not count as a filter the user chose to apply. */
const hasActiveFilters = computed(
  () => search.value.trim() !== ''
    || tenantFilter.value !== 'all'
    || statusFilter.value !== 'active',
);

const clearFilters = () => {
  search.value = '';
  tenantFilter.value = 'all';
  statusFilter.value = 'active';
};

/** Only the concrete states get a dot; `active` and `all` are not one status. */
const selectedStatusDot = computed(() =>
  (STATUS_OPTIONS.includes(statusFilter.value as ReservationRowStatus)
    ? getStatusDotClass(statusFilter.value as ReservationRowStatus)
    : null),
);

/** Tab counts report open work, so they agree with what the default filter shows. */
const activeMineCount = computed(
  () => props.myReservations.filter(reservation => isReservationActive(reservation)).length,
);

const activeAdministeredCount = computed(
  () => props.administeredReservations
    .filter(reservation => isReservationActive(reservation, { approvableOnly: true }))
    .length,
);

const showHelpModal = ref(false);
</script>
