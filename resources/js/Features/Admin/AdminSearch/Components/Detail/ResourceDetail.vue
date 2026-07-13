<template>
  <DetailLayout
    :icon="ResourceIcon"
    :kicker="$t('Išteklius')"
    :title="resource.name_lt || resource.name_en || $t('Be pavadinimo')"
    :subtitle="resource.category_name"
  >
    <template #badges>
      <Badge v-if="resource.tenant_shortname" variant="outline">
        {{ resource.tenant_shortname }}
      </Badge>
      <Badge :class="toneClass(resource.is_reservable ? 'success' : 'neutral')">
        {{ resource.is_reservable ? $t('Skolinamas') : $t('Neskolinamas') }}
      </Badge>
    </template>

    <template v-if="resource.image_url" #media>
      <Dialog v-if="allowImageLightbox">
        <DialogTrigger as-child>
          <button
            type="button"
            class="group relative size-20 overflow-hidden rounded-lg border bg-muted transition-shadow hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary/40"
            :aria-label="$t('Padidinti nuotrauką')"
          >
            <img :src="resource.image_url" :alt="imageAlt" class="size-full object-cover">
            <span class="absolute inset-0 flex items-center justify-center bg-black/0 opacity-0 transition-all group-hover:bg-black/30 group-hover:opacity-100">
              <Expand class="size-5 text-white" />
            </span>
          </button>
        </DialogTrigger>
        <DialogContent class="max-w-3xl p-2">
          <DialogTitle class="sr-only">
            {{ imageAlt || $t('Ištekliaus nuotrauka') }}
          </DialogTitle>
          <img :src="resource.image_url" :alt="imageAlt" class="max-h-[80vh] w-full rounded-md object-contain">
        </DialogContent>
      </Dialog>

      <div v-else class="size-20 overflow-hidden rounded-lg border bg-muted">
        <img :src="resource.image_url" :alt="imageAlt" class="size-full object-cover">
      </div>
    </template>

    <template v-if="showActions" #actions>
      <Link :href="route('resources.edit', resource.id)">
        <Button size="sm">
          <Pencil class="mr-2 size-4" />
          {{ $t('Redaguoti') }}
        </Button>
      </Link>
    </template>

    <!-- Availability for the chosen reservation window -->
    <div
      v-if="showAvailabilityBox"
      class="mb-4 rounded-lg border p-4"
      :class="isUnavailable ? 'border-amber-300 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30' : 'bg-muted/30'"
    >
      <div class="flex items-center justify-between">
        <span class="text-sm font-medium">{{ $t('Laisva pasirinktu laiku') }}</span>
        <span v-if="availability" class="text-lg font-semibold tabular-nums" :class="isUnavailable ? 'text-amber-700 dark:text-amber-400' : 'text-foreground'">
          {{ availability.lowestCapacityAtDateTimeRange }} / {{ availability.capacity }}
        </span>
        <span v-else class="text-sm text-muted-foreground">…</span>
      </div>
      <p v-if="isUnavailable" class="mt-1 text-xs text-amber-700 dark:text-amber-400">
        {{ $t('Šiuo laikotarpiu išteklius nepasiekiamas.') }}
      </p>
    </div>

    <div class="divide-y rounded-lg border px-4">
      <DetailRow v-if="resource.category_name" :label="$t('Kategorija')" :value="resource.category_name" />
      <DetailRow v-if="resource.location" :label="$t('Vieta')" :value="resource.location" />
      <DetailRow v-if="resource.capacity != null" :label="$t('Kiekis')" :value="resource.capacity" />
      <DetailRow :label="$t('Padalinys')" :value="resource.tenant_shortname || '—'" />
    </div>

    <!-- Overlapping reservations in the chosen window -->
    <DetailSection
      v-if="showAvailabilityBox && availability && availability.reservations.length > 0"
      :title="$t('Rezervacijos pasirinktu laiku')"
    >
      <div class="space-y-2">
        <div
          v-for="reservation in availability.reservations"
          :key="reservation.id"
          class="flex items-center justify-between gap-3 rounded-lg border px-3 py-2 text-sm"
        >
          <span class="min-w-0 truncate">{{ reservation.name }}</span>
          <Badge variant="secondary" class="shrink-0">
            {{ $t(':count vnt.', { count: String(reservation.quantity) }) }}
          </Badge>
        </div>
      </div>
    </DetailSection>

    <DetailSection v-if="description" :title="$t('Aprašymas')">
      <p class="whitespace-pre-line text-sm text-muted-foreground">
        {{ description }}
      </p>
    </DetailSection>

    <!-- Upcoming reservations (fetched — not in the search document) -->
    <DetailAsyncSection
      v-if="showUpcomingReservations"
      :title="$t('Artimiausios rezervacijos')"
      :is-fetching
      :has-content="data?.upcoming_reservations?.length > 0"
      :empty-message="$t('Nėra artimiausių rezervacijų')"
    >
      <ol class="space-y-1.5">
        <li
          v-for="reservation in data!.upcoming_reservations"
          :key="reservation.id"
          class="flex items-center justify-between gap-3 rounded-md border px-3 py-2 text-sm"
        >
          <span class="min-w-0 flex-1 truncate">{{ reservation.name }}</span>
          <span class="shrink-0 text-xs tabular-nums text-muted-foreground">
            <template v-if="reservation.start_time">{{ formatSearchDate(reservation.start_time) }}</template>
          </span>
          <Badge variant="secondary" class="shrink-0">
            {{ $t(':count vnt.', { count: String(reservation.quantity) }) }}
          </Badge>
        </li>
      </ol>
    </DetailAsyncSection>

    <!-- Resource managers -->
    <DetailAsyncSection
      v-if="showManagers"
      :title="$t('Atsakingi asmenys')"
      :is-fetching
      :has-content="data?.managers?.length > 0"
      :empty-message="$t('Nėra atsakingų asmenų')"
    >
      <UsersAvatarGroup :users="data!.managers" :max="10" :size="32" clickable />
    </DetailAsyncSection>
  </DetailLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Expand, Pencil } from 'lucide-vue-next';

import { toneClass } from '../../Utils/searchBadges';
import { formatSearchDate } from '../../Utils/searchHitMappers';
import type { ResourceAvailability } from '../../Composables/useResourceAvailability';

import DetailLayout from './DetailLayout.vue';
import DetailRow from './DetailRow.vue';
import DetailSection from './DetailSection.vue';
import DetailAsyncSection from './DetailAsyncSection.vue';

import { ResourceIcon } from '@/Components/icons';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import { Dialog, DialogContent, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';
import { useApi } from '@/Composables/useApi';
import type { ResourceSearchResult } from '@/Shared/Search/types';

interface ResourcePreviewData {
  upcoming_reservations: Array<{ id: string; name: string; quantity: number; state: string; start_time: number | null; end_time: number | null }>;
  managers: Array<{ id: string; name: string; profile_photo_path: string | null }>;
}

const props = withDefaults(defineProps<{
  resource: ResourceSearchResult;
  availability?: ResourceAvailability;
  showAvailabilityBox?: boolean;
  showUpcomingReservations?: boolean;
  showManagers?: boolean;
  allowImageLightbox?: boolean;
  showActions?: boolean;
}>(), {
  showAvailabilityBox: false,
  showUpcomingReservations: false,
  showManagers: false,
  allowImageLightbox: false,
  showActions: false,
});

const description = computed(() => props.resource.description_lt || props.resource.description_en);
const imageAlt = computed(() => props.resource.name_lt || props.resource.name_en || '');
const isUnavailable = computed(() =>
  !props.resource.is_reservable || (props.availability != null && props.availability.lowestCapacityAtDateTimeRange <= 0),
);

// Preview-mode data is only fetched when sections that need it are enabled.
// The parent keys this component by hit id, so an immediate fetch is fresh each time.
const shouldFetchPreview = computed(() => props.showUpcomingReservations || props.showManagers);

const { data, isFetching } = useApi<ResourcePreviewData>(
  () => route('api.v1.admin.resources.preview', props.resource.id),
  { immediate: shouldFetchPreview.value, showErrorToast: false },
);
</script>
