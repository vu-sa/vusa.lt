<template>
  <CollectionPage
    :source
    collection="institutions"
    entity-type="institution"
    :eyebrow
    :title="$t('Institucijos')"
    :lead="$t('VU SA ir VU organai, kuriuose dirba studentų atstovai.')"
    default-view="rows"
    :item-key="institutionKey"
    :columns
    :search-placeholder="$t('Ieškoti institucijų')"
  >
    <template #actions>
      <Button v-if="deletedCount > 0" as-child variant="ghost">
        <Link :href="route('institutions.index', { showDeleted: 'true' })">
          <Trash2 aria-hidden="true" />
          {{ $t('Ištrinti') }} ({{ deletedCount }})
        </Link>
      </Button>
      <Button v-if="canCreate" as-child variant="brand">
        <Link :href="route('institutions.create')">
          <Plus aria-hidden="true" />
          {{ $t('Nauja institucija') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item }">
      <div class="relative flex items-center gap-4 px-2 py-3 sm:px-4" data-slot="institution-collection-row">
        <Link
          :href="route('institutions.show', item.id)"
          prefetch
          data-collection-open
          class="flex min-w-0 flex-1 items-center gap-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
        >
          <EntityTypeMark type="institution" size="md" />
          <span class="min-w-0 flex-1">
            <span class="block truncate text-base font-medium text-foreground">
              {{ nameOf(item) }}
            </span>
            <span class="mt-0.5 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-muted-foreground">
              <span v-if="item.tenant_shortname">{{ item.tenant_shortname }}</span>
              <span v-if="item.type_titles?.length">{{ item.type_titles.join(', ') }}</span>
              <span v-if="item.current_user_names?.length">
                {{ $t('Narių: :count', { count: String(item.current_user_names.length) }) }}
              </span>
            </span>
          </span>
        </Link>
      </div>
    </template>

    <template #cell="{ item, column }">
      <Link
        v-if="column.key === 'name'"
        :href="route('institutions.show', item.id)"
        prefetch
        class="font-medium hover:text-brand"
      >
        {{ nameOf(item) }}
      </Link>
      <template v-else-if="column.key === 'tenant'">
        {{ item.tenant_shortname ?? '—' }}
      </template>
      <template v-else-if="column.key === 'types'">
        {{ item.type_titles?.join(', ') || '—' }}
      </template>
      <span v-else-if="column.key === 'members'" class="tabular-nums">
        {{ item.current_user_names?.length ?? 0 }}
      </span>
    </template>

    <template #empty>
      <EmptyState
        mode="empty"
        :icon="InstitutionIcon"
        :title="$t('Institucijų dar nėra')"
        :description="$t('Čia atsiras VU SA ir VU organai. Sukūrusi instituciją, jos puslapyje pridėsi pareigybes ir narius.')"
        :action-label="canCreate ? $t('Nauja institucija') : undefined"
        :action-href="canCreate ? route('institutions.create') : undefined"
      />
    </template>
  </CollectionPage>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

import type { CollectionColumn } from '@/Components/Collection/types';
import EntityTypeMark from '@/Components/EntityTypeMark.vue';
import { InstitutionIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useTypesenseCollectionSource } from '@/Composables/useCollectionSource';
import type { InstitutionSearchResult } from '@/Shared/Search/types';

defineProps<{
  /** Soft-deleted institutions the user could restore; the trash itself is a database table. */
  deletedCount: number;
}>();

const canCreate = computed(() => Boolean(usePage().props.auth?.can?.create?.institution));

const eyebrow = computed(() => `${$t('shell.workspaces.atstovavimas.title')} · ${$t('shell.sections.institucijos')}`);

const source = useTypesenseCollectionSource<InstitutionSearchResult>({
  collection: 'institutions',
  preserveUrlKeys: ['view', 'item'],
});

const institutionKey = (institution: InstitutionSearchResult) => String(institution.id);
const nameOf = (institution: InstitutionSearchResult) => institution.name_lt || institution.name_en || $t('Be pavadinimo');

const columns = computed<CollectionColumn[]>(() => [
  { key: 'name', label: $t('Institucija') },
  { key: 'tenant', label: $t('Padalinys'), class: 'w-32' },
  { key: 'types', label: $t('Tipas') },
  { key: 'members', label: $t('Nariai'), class: 'w-24' },
]);
</script>
