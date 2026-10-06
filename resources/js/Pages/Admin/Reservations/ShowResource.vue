<template>
  <div>
    <RecordPage
      v-model:section="currentSection"
      :title="resource.name || $t('Be pavadinimo')"
      :entity-type="ModelEnum.RESOURCE"
      :facts
      :sections
      :primary-action
      :overflow-actions
      actions-beside-title
      @action="handleAction"
    >
      <!-- Without a photo there is no anchor: the eyebrow already names the type. -->
      <template v-if="resource.images[0]" #identity>
        <img
          :src="resource.images[0]"
          alt=""
          class="size-16 border border-border object-cover sm:size-20"
          data-testid="resource-identity-image"
        >
      </template>

      <template #fact-managers>
        <span v-if="managers.length === 0" class="text-muted-foreground">{{ $t('reservations.resource.no_managers') }}</span>
        <span v-else class="flex flex-col">
          <a
            v-for="manager in managers"
            :key="manager.id"
            :href="`mailto:${manager.email}`"
            class="truncate underline underline-offset-4"
          >{{ manager.name }}</a>
        </span>
      </template>

      <template #alert>
        <!-- The one place an overdue return is mentioned: it does not stop anyone reserving. -->
        <div
          v-if="overdueLoans.length > 0"
          class="flex items-start gap-3 border-l-2 border-status-attention bg-status-attention-surface px-4 py-3 text-sm"
          data-testid="resource-overdue-alert"
        >
          <Clock class="mt-0.5 size-4 shrink-0 text-status-attention" aria-hidden="true" />
          <div>
            <p class="font-semibold text-foreground">
              {{ $tChoice('reservations.resource.overdue_notice', overdueLoans.length, { count: String(overdueLoans.length), date: overdueSince }) }}
            </p>
            <p class="mt-0.5 text-muted-foreground">
              {{ $t('reservations.resource.overdue_hint') }}
            </p>
          </div>
        </div>
      </template>

      <template #availability>
        <div class="space-y-10">
          <section v-if="period && resource.is_reservable" data-testid="resource-period-availability">
            <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">
              {{ $t('reservations.resource.check_period') }}
            </p>
            <p class="mt-1 text-sm text-muted-foreground">
              {{ formatReservationPeriod(period.start, period.end) }}
            </p>
            <p class="mt-2 text-2xl font-semibold tabular-nums" :class="freeInPeriod === 0 && 'text-status-attention'">
              {{ freeInPeriod === undefined ? '…' : $t('reservations.cart.free_of', { available: String(freeInPeriod), capacity: String(resource.capacity) }) }}
            </p>
          </section>

          <div v-if="currentLoans.length > 0 || upcoming.length > 0" class="grid gap-10 xl:grid-cols-2 xl:gap-16">
            <OverviewSection
              v-if="currentLoans.length > 0"
              variant="home"
              :title="$t('reservations.resource.current_loans')"
              :icon="PackageCheck"
              :count="currentLoans.length"
              data-testid="resource-current-loans"
            >
              <ul class="divide-y divide-border">
                <ResourceBookingRow v-for="booking in currentLoans" :key="booking.id" :booking />
              </ul>
            </OverviewSection>

            <OverviewSection
              v-if="upcoming.length > 0"
              variant="home"
              :title="$t('reservations.resource.upcoming')"
              :icon="CalendarClock"
              :count="upcoming.length"
              data-testid="resource-upcoming"
            >
              <ul class="divide-y divide-border">
                <ResourceBookingRow v-for="booking in upcoming" :key="booking.id" :booking />
              </ul>
            </OverviewSection>
          </div>

          <!-- Empty lists gather into one quiet status list, as on Pradžia ("Viskas tvarkoje"). -->
          <OverviewStatusList :entries="emptyBookingLists" />
        </div>
      </template>

      <template #about>
        <div class="grid gap-10 xl:grid-cols-2 xl:gap-16">
          <OverviewSection variant="home" :title="$t('Aprašymas')" :icon="FileText">
            <p v-if="resource.description" class="whitespace-pre-line text-sm text-foreground">
              {{ resource.description }}
            </p>
            <p v-else class="text-sm text-muted-foreground">
              {{ $t('reservations.resource.description_empty') }}
            </p>
          </OverviewSection>

          <OverviewSection v-if="resource.images.length > 0" variant="home" :title="$t('reservations.resource.photos')" :icon="ImageIcon">
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
              <a
                v-for="image in resource.images"
                :key="image"
                :href="image"
                target="_blank"
                rel="noopener noreferrer"
                class="block border border-border"
                :aria-label="`${$t('reservations.resource.photos')}: ${resource.name ?? ''}`"
              >
                <img :src="image" :alt="resource.name ?? ''" loading="lazy" class="aspect-square w-full object-cover">
              </a>
            </div>
          </OverviewSection>
        </div>
      </template>

      <template #history>
        <Deferred data="history">
          <template #fallback>
            <div class="max-w-3xl space-y-2" data-testid="resource-history-skeleton">
              <div v-for="n in 3" :key="n" class="h-14 animate-pulse border-b border-border bg-secondary/60" />
            </div>
          </template>

          <p v-if="(history ?? []).length === 0" class="max-w-3xl text-sm text-muted-foreground">
            {{ $t('reservations.resource.history_empty') }}
          </p>
          <ul v-else class="max-w-3xl divide-y divide-border border-y border-border">
            <ResourceBookingRow v-for="booking in history" :key="booking.id" :booking />
          </ul>
        </Deferred>
      </template>
    </RecordPage>

    <ReservationCartBar />
    <ReservationCartSheet />

    <ConfirmDialog
      v-model:open="deleteOpen"
      :title="$t('reservations.resource.delete_title')"
      :description="$t('reservations.resource.delete_description')"
      :confirm-label="$t('reservations.resource.delete')"
      destructive
      @confirm="router.delete(route('resources.destroy', resource.id))"
    />
  </div>
</template>

<script setup lang="ts">
import { Deferred, router } from '@inertiajs/vue3';
import { useClipboard } from '@vueuse/core';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { Ban, CalendarClock, CalendarPlus, Check, Clock, Edit3, FileText, Image as ImageIcon, Link2, PackageCheck, PackageX, Plus, Trash2 } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { capitalize, computed, ref, watch } from 'vue';

import RecordPage, { type RecordAction, type RecordFact, type RecordPageSection } from '@/Components/Layouts/RecordPage.vue';
import { ConfirmDialog, OverviewSection, OverviewStatusList } from '@/Components/Patterns';
import ReservationCartBar from '@/Components/Reservations/ReservationCartBar.vue';
import ReservationCartSheet from '@/Components/Reservations/ReservationCartSheet.vue';
import { formatReservationPeriod } from '@/Components/Reservations/reservationPeriod';
import ResourceBookingRow, { type ResourceBooking } from '@/Components/Reservations/ResourceBookingRow.vue';
import type { ReservationCart } from '@/Components/Reservations/types';
import { useReservationCart } from '@/Components/Reservations/useReservationCart';
import type { StatusPresentation } from '@/Constants/statuses';
import { useResourceAvailability } from '@/Features/Admin/AdminSearch/Composables/useResourceAvailability';
import { ModelEnum } from '@/Types/enums';
import { formatDate } from '@/Utils/dateTime';

interface ShowResourcePayload {
  id: string;
  name: string | null;
  description: string | null;
  location: string | null;
  identifier: string | null;
  capacity: number;
  is_reservable: boolean;
  tenant: { id: number; shortname: string } | null;
  category: string | null;
  images: string[];
}

interface ResourceManager {
  id: string;
  name: string;
  email: string;
  profile_photo_path: string | null;
}

const props = defineProps<{
  resource: ShowResourcePayload;
  availableNow: number;
  currentLoans: ResourceBooking[];
  upcoming: ResourceBooking[];
  managers: ResourceManager[];
  /** Deferred: only the Istorija tab needs it. */
  history?: ResourceBooking[];
  reservationCart: ReservationCart | null;
  can: { update: boolean; delete: boolean; reserve: boolean };
}>();

const currentSection = ref('availability');
const deleteOpen = ref(false);

const { period, hasDraft, quantityOf, add, startWith, openSheet } = useReservationCart();

const inCart = computed(() => quantityOf(props.resource.id));
const overdueLoans = computed(() => props.currentLoans.filter(booking => booking.overdue));

// The oldest due date: the one a manager should chase first.
const overdueSince = computed(() => formatDate(Math.min(...overdueLoans.value.map(booking => booking.end_time ?? Date.now()))));

const emptyBookingLists = computed(() => [
  ...(props.currentLoans.length === 0
    ? [{ id: 'current', title: $t('reservations.resource.not_taken'), emptyText: $t('reservations.resource.current_loans_empty'), icon: PackageCheck }]
    : []),
  ...(props.upcoming.length === 0
    ? [{ id: 'upcoming', title: $t('reservations.resource.upcoming'), emptyText: $t('reservations.resource.upcoming_empty'), icon: CalendarClock }]
    : []),
]);

// --- Availability for the cart's period ------------------------------------------------------

const { availability, ensure } = useResourceAvailability(() => period.value ?? { start: 0, end: 0 });

watch(period, (value) => {
  if (value && props.resource.is_reservable && props.can.reserve) {
    void ensure([props.resource.id]);
  }
}, { immediate: true });

const freeInPeriod = computed(() => {
  const free = availability.value.get(props.resource.id)?.lowestCapacityAtDateTimeRange;

  return free === undefined ? undefined : Math.max(0, free);
});

// --- Identity band ----------------------------------------------------------------------------

// Only the exception is painted: free and reservable is the healthy default.
const status = computed<StatusPresentation | undefined>(() => {
  if (!props.resource.is_reservable) {
    return { label: $t('reservations.resource.status_not_reservable'), role: 'neutral', icon: Ban };
  }

  if (props.availableNow <= 0) {
    return { label: $t('reservations.resource.status_all_out'), role: 'attention', icon: PackageX };
  }

  return undefined;
});

const facts = computed<RecordFact[]>(() => {
  const list: RecordFact[] = [
    // A status (not reservable, all out) rides on the availability it explains.
    status.value
      ? { key: 'available', label: $t('reservations.resource.available_now'), status: status.value, detail: `${props.availableNow} / ${props.resource.capacity}` }
      : { key: 'available', label: $t('reservations.resource.available_now'), value: `${props.availableNow} / ${props.resource.capacity}` },
  ];

  if (props.resource.tenant) {
    list.push({ key: 'tenant', label: $t('Padalinys'), value: props.resource.tenant.shortname });
  }

  if (props.resource.category) {
    list.push({ key: 'category', label: $t('reservations.resource.category'), value: props.resource.category });
  }

  if (props.resource.location) {
    list.push({ key: 'location', label: $t('reservations.resource.location'), value: props.resource.location });
  }

  if (props.resource.identifier) {
    list.push({ key: 'identifier', label: $t('reservations.resource.identifier'), value: props.resource.identifier });
  }

  list.push({ key: 'managers', label: $t('reservations.resource.managers') });

  return list;
});

const sections = computed<RecordPageSection[]>(() => [
  { value: 'availability', label: $t('reservations.resource.sections.availability'), count: props.currentLoans.length + props.upcoming.length },
  { value: 'about', label: $t('reservations.resource.sections.about') },
  { value: 'history', label: $t('reservations.resource.sections.history') },
]);

const primaryAction = computed<RecordAction | undefined>(() => {
  if (!props.can.reserve) {
    return undefined;
  }

  if (!hasDraft.value) {
    return { key: 'reserve', label: $t('reservations.cart.reserve'), icon: CalendarPlus };
  }

  return inCart.value > 0
    ? { key: 'reserve', label: $t('reservations.cart.in_cart', { count: String(inCart.value) }), icon: Check }
    : { key: 'reserve', label: $t('reservations.cart.add_to_reservation'), icon: Plus };
});

const overflowActions = computed<RecordAction[]>(() => {
  const actions: RecordAction[] = [
    { key: 'copy-link', label: $t('reservations.resource.copy_link'), icon: Link2 },
  ];

  if (props.can.update) {
    actions.push({ key: 'edit', label: $t('reservations.resource.edit'), icon: Edit3, href: route('resources.edit', props.resource.id) });
  }

  if (props.can.delete) {
    actions.push({ key: 'delete', label: $t('reservations.resource.delete'), icon: Trash2, destructive: true });
  }

  return actions;
});

const { copy: copyUrl } = useClipboard({ legacy: true });

const handleAction = async (key: string) => {
  switch (key) {
    case 'reserve':
      if (!hasDraft.value) {
        startWith(props.resource.id);
      }
      else if (inCart.value > 0) {
        openSheet();
      }
      else {
        add(props.resource.id);
      }
      break;
    case 'copy-link':
      await copyUrl(window.location.href);
      toast.success($t('reservations.resource.link_copied'));
      break;
    case 'delete':
      deleteOpen.value = true;
      break;
  }
};

</script>
