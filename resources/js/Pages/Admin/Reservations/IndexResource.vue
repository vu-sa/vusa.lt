<template>
  <CollectionPage
    :source
    collection="resources"
    entity-type="resource"
    :eyebrow
    :title="capitalize($tChoice('entities.resource.model', 2))"
    :lead="$t('Patalpos, įranga ir kiti daiktai, kuriuos galima rezervuoti.')"
    default-view="rows"
    :item-key="resourceKey"
    :columns
    :search-placeholder="$t('Ieškoti išteklių')"
  >
    <template #actions>
      <Button v-if="canCreate" as-child variant="brand">
        <Link :href="route('resources.create')">
          <Plus aria-hidden="true" />
          {{ $t('Naujas išteklius') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item }">
      <div class="relative flex items-center gap-4 px-2 py-3 sm:px-4" data-slot="resource-collection-row">
        <Link
          :href="route('resources.edit', item.id)"
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
          <EntityTypeMark v-else type="resource" size="md" />
          <span class="min-w-0 flex-1">
            <span class="block truncate text-base font-medium text-foreground">
              {{ nameOf(item) }}
            </span>
            <span class="mt-0.5 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-muted-foreground">
              <span v-if="item.category_name">{{ item.category_name }}</span>
              <span v-if="item.tenant_shortname">{{ item.tenant_shortname }}</span>
              <span v-if="item.location">{{ item.location }}</span>
              <span v-if="item.capacity" class="tabular-nums">{{ $t('Kiekis: :count', { count: String(item.capacity) }) }}</span>
            </span>
          </span>
        </Link>
        <StatusBadge v-if="!item.is_reservable" :status="notReservable" class="shrink-0" />
      </div>
    </template>

    <template #cell="{ item, column }">
      <Link
        v-if="column.key === 'name'"
        :href="route('resources.edit', item.id)"
        prefetch
        class="font-medium hover:text-brand"
      >
        {{ nameOf(item) }}
      </Link>
      <template v-else-if="column.key === 'category'">
        {{ item.category_name ?? '—' }}
      </template>
      <template v-else-if="column.key === 'tenant'">
        {{ item.tenant_shortname ?? '—' }}
      </template>
      <span v-else-if="column.key === 'capacity'" class="tabular-nums">{{ item.capacity ?? '—' }}</span>
      <StatusBadge v-else-if="column.key === 'status' && !item.is_reservable" :status="notReservable" />
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
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { Ban, Plus } from 'lucide-vue-next';
import { capitalize, computed } from 'vue';

import type { CollectionColumn } from '@/Components/Collection/types';
import EntityTypeMark from '@/Components/EntityTypeMark.vue';
import { ResourceIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { EmptyState, StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useTypesenseCollectionSource } from '@/Composables/useCollectionSource';
import type { StatusPresentation } from '@/Constants/statuses';
import type { ResourceSearchResult } from '@/Shared/Search/types';

const canCreate = computed(() => Boolean(usePage().props.auth?.can?.create?.resource));

const eyebrow = computed(() => `${$t('shell.workspaces.rezervacijos.title')} · ${$t('shell.sections.istekliai')}`);

const source = useTypesenseCollectionSource<ResourceSearchResult>({
  collection: 'resources',
  preserveUrlKeys: ['view', 'item'],
  // The facet chip reads "Ar skolinamas: Taip" — name the value as the badge does (U10).
  valueLabel: (field, value) => (field === 'is_reservable' ? (value === 'true' ? $t('Rezervuojamas') : $t('Nerezervuojamas')) : undefined),
});

/** Only the exception is painted: a reservable resource is the healthy default. */
const notReservable: StatusPresentation = { label: 'Nerezervuojamas', role: 'neutral', icon: Ban };

const resourceKey = (resource: ResourceSearchResult) => String(resource.id);
const nameOf = (resource: ResourceSearchResult) => resource.name_lt || resource.name_en || $t('Be pavadinimo');

const columns = computed<CollectionColumn[]>(() => [
  { key: 'name', label: capitalize($tChoice('entities.resource.model', 1)) },
  { key: 'category', label: $t('Kategorija') },
  { key: 'tenant', label: $t('Padalinys'), class: 'w-32' },
  { key: 'capacity', label: $t('Kiekis'), class: 'w-24' },
  { key: 'status', label: $t('Būsena'), class: 'w-40' },
]);
</script>
