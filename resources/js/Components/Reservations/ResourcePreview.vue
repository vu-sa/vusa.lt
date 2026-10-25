<template>
  <PreviewPane
    entity-type="resource"
    :eyebrow="$t('Išteklius')"
    :title="name"
    :href="showHref"
    :subtitle="resource.category_name"
  >
    <template v-if="!resource.is_reservable" #badges>
      <StatusBadge :status="NOT_RESERVABLE" />
    </template>

    <template v-if="resource.image_url" #media>
      <Dialog v-if="allowImageLightbox">
        <DialogTrigger as-child>
          <button
            type="button"
            class="relative size-20 overflow-hidden border border-border bg-muted focus-visible:outline-2 focus-visible:outline-ring pointer-coarse:size-24"
            :aria-label="$t('Padidinti nuotrauką')"
          >
            <img :src="resource.image_url" :alt="name" class="size-full object-cover">
            <!-- Always visible: a hover-only hint would hide the lightbox from touch users. -->
            <span class="absolute right-0 bottom-0 flex size-6 items-center justify-center bg-background/90 text-foreground">
              <Expand class="size-3.5" aria-hidden="true" />
            </span>
          </button>
        </DialogTrigger>
        <DialogContent class="max-w-3xl p-2">
          <DialogTitle class="sr-only">
            {{ name }}
          </DialogTitle>
          <img :src="resource.image_url" :alt="name" class="max-h-[80vh] w-full object-contain">
        </DialogContent>
      </Dialog>
      <div v-else class="size-20 overflow-hidden border border-border bg-muted">
        <img :src="resource.image_url" :alt="name" class="size-full object-cover">
      </div>
    </template>

    <template #actions>
      <slot name="actions">
        <Button as-child variant="brand" size="sm">
          <Link :href="showHref">
            {{ $t('Atidaryti išteklių') }}
          </Link>
        </Button>
      </slot>
    </template>

    <!-- Availability for the reservation window chosen in the picker -->
    <div
      v-if="showAvailabilityBox"
      :class="[
        'border-l-2 px-4 py-3',
        isUnavailable || hasDiscrepancies ? 'border-status-attention bg-status-attention-surface' : 'border-border bg-secondary/50',
      ]"
      data-slot="resource-availability"
    >
      <div class="flex items-center justify-between gap-3">
        <span class="text-sm font-medium">{{ $t('Laisva pasirinktu laiku') }}</span>
        <span v-if="availability" class="text-lg font-semibold tabular-nums" :class="isUnavailable ? 'text-status-attention' : 'text-foreground'">
          {{ availability.lowestCapacityAtDateTimeRange }} / {{ availability.capacity }}
        </span>
        <span v-else class="text-sm text-muted-foreground">…</span>
      </div>
      <p v-if="isUnavailable" class="mt-1 text-xs text-status-attention">
        {{ $t('Šiuo laikotarpiu išteklius nepasiekiamas.') }}
      </p>
      <p v-else-if="hasDiscrepancies" class="mt-1 flex items-start gap-1.5 text-xs text-status-attention">
        <AlertTriangle class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
        {{ $t('reservations.discrepancy.available_from_ended_reservations', { count: String(availability!.discrepancies.length) }) }}
      </p>
    </div>

    <PreviewFacts>
      <PreviewFact v-if="resource.location" :label="$t('Vieta')" :value="resource.location" />
      <PreviewFact v-if="resource.capacity != null" :label="$t('Kiekis')" :value="resource.capacity" tabular />
      <PreviewFact :label="$t('Padalinys')" :value="resource.tenant_shortname" />
    </PreviewFacts>

    <PreviewSection
      v-if="showAvailabilityBox && availability?.reservations.length"
      :title="$t('Rezervacijos pasirinktu laiku')"
    >
      <ul class="divide-y divide-border border-y border-border">
        <li v-for="reservation in availability.reservations" :key="reservation.id" class="flex items-center justify-between gap-3 py-2 text-sm">
          <span class="flex min-w-0 items-center gap-2">
            <AlertTriangle v-if="isTimeEndedActive(reservation)" class="size-3.5 shrink-0 text-status-attention" aria-hidden="true" />
            <span class="truncate">{{ reservation.name }}</span>
          </span>
          <span class="shrink-0 text-xs tabular-nums text-muted-foreground">{{ $t(':count vnt.', { count: String(reservation.quantity) }) }}</span>
        </li>
      </ul>
    </PreviewSection>

    <PreviewSection v-if="description" :title="$t('Aprašymas')">
      <p class="whitespace-pre-line text-sm text-muted-foreground">
        {{ description }}
      </p>
    </PreviewSection>

    <PreviewSection
      v-for="list in reservationLists"
      :key="list.key"
      :title="list.title"
      :loading="isLoading"
      :empty="!list.items.length"
      :empty-message="list.emptyMessage"
    >
      <ol class="divide-y divide-border border-y border-border">
        <li v-for="reservation in list.items" :key="reservation.id" class="flex flex-col gap-1.5 py-2 text-sm">
          <div class="flex items-center justify-between gap-3">
            <Link :href="route('reservations.show', reservation.id)" class="min-w-0 flex-1 truncate font-medium hover:text-brand">
              {{ reservation.name }}
            </Link>
            <span class="shrink-0 text-xs tabular-nums text-muted-foreground">{{ $t(':count vnt.', { count: String(reservation.quantity) }) }}</span>
          </div>
          <div class="flex items-center justify-between gap-3">
            <ReservationResourceStateTag
              :state="reservation.state"
              :unresolved="list.key === 'upcoming' && isTimeEndedActive(reservation)"
              class="shrink-0"
            />
            <span class="shrink-0 text-xs tabular-nums text-muted-foreground">{{ formatPeriod(reservation) }}</span>
          </div>
        </li>
      </ol>
    </PreviewSection>

    <PreviewSection
      v-if="showManagers"
      :title="$t('reservations.resource.managers')"
      :loading="isLoading"
      :empty="!data?.managers.length"
      :empty-message="$t('reservations.resource.no_managers')"
    >
      <ResourceManagers :managers="data?.managers ?? []" hide-label />
    </PreviewSection>
  </PreviewPane>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { AlertTriangle, Ban, Expand } from '@lucide/vue';
import { computed } from 'vue';

import ResourceManagers, { type ResourceManagerContact } from '@/Components/Reservations/ResourceManagers.vue';
import { PreviewFact, PreviewFacts, PreviewPane, PreviewSection, StatusBadge } from '@/Components/Patterns';
import ReservationResourceStateTag from '@/Components/Tag/ReservationResourceStateTag.vue';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';
import { usePreviewApi } from '@/Composables/usePreviewApi';
import type { StatusPresentation } from '@/Constants/statuses';
import type { ResourceAvailability } from '@/Features/Admin/AdminSearch/Composables/useResourceAvailability';
import type { ResourceSearchResult } from '@/Shared/Search/types';
import { formatDateTime } from '@/Utils/dateTime';

interface ReservationRow {
  id: string;
  name: string;
  quantity: number;
  state: App.Entities.ReservationResource['state'];
  start_time: number | null;
  end_time: number | null;
}

interface ResourcePreviewData {
  upcoming_reservations: ReservationRow[];
  previous_reservations: ReservationRow[];
  managers: ResourceManagerContact[];
}

const NOT_RESERVABLE: StatusPresentation = { label: 'Nerezervuojamas', role: 'neutral', icon: Ban };

const props = defineProps<{
  resource: ResourceSearchResult;
  /** The picker's chosen reservation window. */
  availability?: ResourceAvailability;
  showAvailabilityBox?: boolean;
  showUpcomingReservations?: boolean;
  showPreviousReservations?: boolean;
  showManagers?: boolean;
  allowImageLightbox?: boolean;
}>();

const { data, isLoading } = usePreviewApi<ResourcePreviewData>(
  route('api.v1.admin.resources.preview', props.resource.id),
  props.showUpcomingReservations || props.showPreviousReservations || props.showManagers,
);

const name = computed(() => props.resource.name_lt || props.resource.name_en || $t('Be pavadinimo'));
const showHref = computed(() => route('resources.show', props.resource.id));
const description = computed(() => props.resource.description_lt || props.resource.description_en);
const isUnavailable = computed(() => !props.resource.is_reservable || (props.availability != null && props.availability.lowestCapacityAtDateTimeRange <= 0));
const hasDiscrepancies = computed(() => (props.availability?.discrepancies ?? []).length > 0);

const reservationLists = computed(() => [
  ...(props.showUpcomingReservations
    ? [{ key: 'upcoming', title: $t('Aktyvios rezervacijos'), emptyMessage: $t('Nėra aktyvių rezervacijų'), items: data.value?.upcoming_reservations ?? [] }]
    : []),
  ...(props.showPreviousReservations
    ? [{ key: 'previous', title: $t('Ankstesnės rezervacijos'), emptyMessage: $t('Nėra ankstesnių rezervacijų'), items: data.value?.previous_reservations ?? [] }]
    : []),
]);

/** A reservation still holding the resource after its window ended needs someone to close it. */
function isTimeEndedActive(reservation: { end_time: number | null; state: string }): boolean {
  return reservation.end_time != null
    && ['created', 'reserved', 'lent'].includes(reservation.state)
    && reservation.end_time * 1000 < Date.now();
}

// Search documents and the preview API both carry Unix seconds.
function formatPeriod(reservation: ReservationRow): string {
  return [reservation.start_time, reservation.end_time]
    .filter((time): time is number => time != null)
    .map(time => formatDateTime(time * 1000))
    .join(' – ');
}
</script>
