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
      <div class="size-20 overflow-hidden rounded-lg border bg-muted">
        <img :src="resource.image_url" :alt="imageAlt" class="size-full object-cover">
      </div>
    </template>

    <!-- Availability for the chosen reservation window -->
    <div
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
    <div v-if="availability && availability.reservations.length > 0" class="mt-6">
      <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
        {{ $t('Rezervacijos pasirinktu laiku') }}
      </h3>
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
    </div>

    <div v-if="description" class="mt-6">
      <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
        {{ $t('Aprašymas') }}
      </h3>
      <p class="whitespace-pre-line text-sm text-muted-foreground">
        {{ description }}
      </p>
    </div>
  </DetailLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import DetailLayout from '../Detail/DetailLayout.vue';
import DetailRow from '../Detail/DetailRow.vue';
import { toneClass } from '../../Utils/searchBadges';
import type { ResourceAvailability } from '../../Composables/useResourceAvailability';

import { ResourceIcon } from '@/Components/icons';
import { Badge } from '@/Components/ui/badge';
import type { ResourceSearchResult } from '@/Shared/Search/types';

const props = defineProps<{
  resource: ResourceSearchResult;
  availability?: ResourceAvailability;
}>();

const description = computed(() => props.resource.description_lt || props.resource.description_en);
const imageAlt = computed(() => props.resource.name_lt || props.resource.name_en || '');
const isUnavailable = computed(() =>
  !props.resource.is_reservable || (props.availability != null && props.availability.lowestCapacityAtDateTimeRange <= 0),
);
</script>
