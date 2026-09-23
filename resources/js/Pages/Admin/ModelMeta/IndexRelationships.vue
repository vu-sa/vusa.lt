<template>
  <CollectionPage
    :source
    collection="relationships"
    entity-type="relationship"
    :eyebrow="`${$t('shell.workspaces.sistema.title')} · ${$t('shell.sections.rysiai')}`"
    :title="$t('Ryšiai')"
    :lead="$t('Ryšių tipai, kuriais susiejami įrašai: pavyzdžiui, kuri institucija kuriai atsiskaito.')"
    default-view="table"
    :item-key="relationship => String(relationship.id)"
    :columns
    :search-placeholder="$t('Ieškoti ryšių')"
  >
    <template #actions>
      <Button v-if="canCreate" as-child variant="brand" size="lg">
        <Link :href="route('relationships.create')">
          <Plus aria-hidden="true" />
          {{ $t('Naujas ryšys') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center">
        <div class="min-w-0 flex-1">
          <CollectionPrimaryCell :title="item.name" :href="route('relationships.edit', item.id)" :sub="item.slug" mono />
          <p v-if="item.description" class="mt-2 line-clamp-2 text-xs text-muted-foreground">{{ item.description }}</p>
        </div>
        <CollectionRowActions :actions="actions.rowActions(item, item.name, false)" @select="key => actions.select(key)" />
      </article>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell v-if="column.key === 'name'" :title="item.name" :href="route('relationships.edit', item.id)" :sub="item.slug" mono />
      <span v-else-if="column.key === 'description'" class="line-clamp-2 text-muted-foreground">{{ item.description || '—' }}</span>
      <CollectionRowActions v-else-if="column.key === 'actions'" :actions="actions.rowActions(item, item.name, false)" @select="key => actions.select(key)" />
    </template>
  </CollectionPage>

  <CollectionConfirmAction :dialog="actions.dialog.value" @confirm="actions.confirm" @cancel="actions.pending.value = null" />
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Plus } from 'lucide-vue-next';
import { computed, toRef } from 'vue';

import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import type { CollectionColumn } from '@/Components/Collection/types';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { Button } from '@/Components/ui/button';
import { useCollectionRecordActions } from '@/Composables/useCollectionRecordActions';
import { useLocalCollectionSource } from '@/Composables/useCollectionSource';

type RelationshipRow = Pick<App.Entities.Relationship, 'id' | 'name' | 'slug' | 'description'>;

const props = defineProps<{
  relationships: RelationshipRow[];
}>();

const canCreate = computed(() => Boolean(usePage().props.auth?.can?.create?.relationship));

const source = useLocalCollectionSource<RelationshipRow>({
  items: toRef(props, 'relationships'),
  searchText: relationship => [relationship.name, relationship.slug, relationship.description],
  defaultSort: 'name:asc',
  sortOptions: [
    { value: 'name:asc', label: $t('Pagal pavadinimą (A–Z)'), by: relationship => relationship.name },
    { value: 'name:desc', label: $t('Pagal pavadinimą (Z–A)'), by: relationship => relationship.name },
  ],
});

const actions = useCollectionRecordActions({ routePrefix: 'relationships', canDelete: () => canCreate.value });

const columns = computed<CollectionColumn[]>(() => [
  { key: 'name', label: $t('Ryšys'), sortField: 'name' },
  { key: 'description', label: $t('Aprašymas') },
  { key: 'actions', label: $t('Veiksmai'), class: 'w-px text-right', pinned: true },
]);
</script>
