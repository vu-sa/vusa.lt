<template>
  <DetailLayout
    :icon="ResourceIcon"
    :kicker="$t('Išteklius')"
    :title="resource.name_lt || resource.name_en || $t('Be pavadinimo')"
    :subtitle="resource.category_name"
  >
    <template #badges>
      <Badge v-if="resource.tenant_shortname" variant="outline">{{ resource.tenant_shortname }}</Badge>
      <Badge :class="toneClass(resource.is_reservable ? 'success' : 'neutral')">
        {{ resource.is_reservable ? $t('Skolinamas') : $t('Neskolinamas') }}
      </Badge>
    </template>

    <template v-if="resource.image_url" #media>
      <Dialog>
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
          <DialogTitle class="sr-only">{{ imageAlt || $t('Ištekliaus nuotrauka') }}</DialogTitle>
          <img :src="resource.image_url" :alt="imageAlt" class="max-h-[80vh] w-full rounded-md object-contain">
        </DialogContent>
      </Dialog>
    </template>

    <template #actions>
      <Link :href="route('resources.edit', resource.id)">
        <Button size="sm">
          <Pencil class="mr-2 size-4" />
          {{ $t('Redaguoti') }}
        </Button>
      </Link>
    </template>

    <div class="divide-y rounded-lg border px-4">
      <DetailRow v-if="resource.category_name" :label="$t('Kategorija')" :value="resource.category_name" />
      <DetailRow v-if="resource.location" :label="$t('Vieta')" :value="resource.location" />
      <DetailRow v-if="resource.capacity != null" :label="$t('Kiekis')" :value="resource.capacity" />
      <DetailRow :label="$t('Padalinys')" :value="resource.tenant_shortname || '—'" />
    </div>

    <div v-if="description" class="mt-6">
      <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
        {{ $t('Aprašymas') }}
      </h3>
      <p class="whitespace-pre-line text-sm text-muted-foreground">{{ description }}</p>
    </div>

    <!-- Upcoming reservations (fetched — not in the search document) -->
    <div class="mt-6">
      <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
        {{ $t('Artimiausios rezervacijos') }}
      </h3>

      <div v-if="isFetching && !data" class="space-y-2">
        <div v-for="i in 3" :key="i" class="h-9 animate-pulse rounded-md bg-muted/50" />
      </div>

      <ol v-else-if="data?.upcoming_reservations?.length" class="space-y-1.5">
        <li
          v-for="reservation in data.upcoming_reservations"
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

      <p v-else class="text-sm text-muted-foreground">
        {{ $t('Nėra artimiausių rezervacijų') }}
      </p>
    </div>

    <!-- Resource managers -->
    <div v-if="data?.managers?.length" class="mt-6">
      <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
        {{ $t('Atsakingi asmenys') }}
      </h3>
      <div class="flex flex-wrap items-center gap-1.5">
        <Avatar
          v-for="manager in data.managers"
          :key="manager.id"
          class="size-8 ring-2 ring-background"
          :title="manager.name"
        >
          <AvatarImage v-if="manager.profile_photo_path" :src="manager.profile_photo_path" :alt="manager.name" />
          <AvatarFallback class="text-[10px]">{{ initials(manager.name) }}</AvatarFallback>
        </Avatar>
      </div>
    </div>
  </DetailLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Expand, Pencil } from 'lucide-vue-next';

import DetailLayout from './DetailLayout.vue';
import DetailRow from './DetailRow.vue';
import { toneClass } from '../../Utils/searchBadges';
import { formatSearchDate } from '../../Utils/searchHitMappers';

import { ResourceIcon } from '@/Components/icons';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Dialog, DialogContent, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';
import { useApi } from '@/Composables/useApi';
import type { ResourceSearchResult } from '@/Shared/Search/types';

interface ResourcePreviewData {
  upcoming_reservations: Array<{ id: string; name: string; quantity: number; state: string; start_time: number | null; end_time: number | null }>;
  managers: Array<{ id: string; name: string; profile_photo_path: string | null }>;
}

const props = defineProps<{
  resource: ResourceSearchResult;
}>();

const description = computed(() => props.resource.description_lt || props.resource.description_en);
const imageAlt = computed(() => props.resource.name_lt || props.resource.name_en || '');

// Remounted per selection (parent keys by hit id), so an immediate fetch is fresh each time.
const { data, isFetching } = useApi<ResourcePreviewData>(
  route('api.v1.admin.resources.preview', props.resource.id),
  { immediate: true, showErrorToast: false },
);

const initials = (name: string): string => name.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase();
</script>
