<template>
  <CollectionPage
    @row-action="(_item, key) => collectionActions.select(key)"
    :row-actions="rowActions"
    :source
    collection="roles"
    entity-type="role"
    :eyebrow="`${$t('shell.workspaces.sistema.title')} · ${$t('shell.sections.roles')}`"
    :title="$t('shell.sections.roles')"
    :lead="$t('responsibilities.index.lead')"
    default-view="table"
    :item-key="responsibility => responsibility.id"
    :columns
    :search-placeholder="$t('responsibilities.index.search')"
  >
    <template #actions>
      <Button v-if="canCreate" as-child variant="brand" size="lg">
        <Link :href="route('responsibilities.create')">
          <Plus aria-hidden="true" />
          {{ $t('responsibilities.index.create') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item, actions: recordActions }">
      <article class="flex flex-col gap-2 px-4 py-4">
        <CollectionPrimaryCell :title="item.title" :href="route('responsibilities.show', item.id)" :sub="item.description" :sub-lines="3" />
        <p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
          <PurposeChip :label="item.purpose_label" show-access />
          <span>{{ counts(item) }}</span>
        </p>
        <CollectionRowActions @select="key => collectionActions.select(key)" :spotlight="item === source?.items.value[0]" :actions="recordActions" />
      </article>
    </template>

    <template #cell="{ item, column }">
      <div v-if="column.key === 'title'" class="min-w-0 space-y-1">
        <CollectionPrimaryCell :title="item.title" :href="route('responsibilities.show', item.id)" :sub="item.description" :sub-lines="3" />
      </div>
      <PurposeChip v-else-if="column.key === 'purpose'" :label="item.purpose_label" />
      <span v-else-if="column.key === 'permissions'" class="tabular-nums text-muted-foreground">{{ item.permissions_count }}</span>
      <span v-else-if="column.key === 'duties'" class="tabular-nums text-muted-foreground">{{ item.duties_count }}</span>
      <span v-else-if="column.key === 'people'" class="tabular-nums text-muted-foreground">{{ item.people_count }}</span>
      <span v-else-if="column.key === 'updated'" class="tabular-nums text-muted-foreground">{{ formatDate(item.updated_at) }}</span>
    </template>
  </CollectionPage>
  <CollectionConfirmAction :dialog="collectionActions.dialog.value" @confirm="collectionActions.confirm" @cancel="collectionActions.pending.value = null" />
</template>

<script setup lang="ts">
import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import { useCollectionRecordActions } from '@/Composables/useCollectionRecordActions';
import { useRowAbilities } from '@/Composables/useRowAbilities';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import type { AdminRecordAction } from '@/Features/Admin/RecordActions/types';
import { Trash2, Pencil, ArrowUpRight } from '@lucide/vue';
import { computed, toRef } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { Plus } from '@lucide/vue';

import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import type { CollectionColumn } from '@/Components/Collection/types';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { Button } from '@/Components/ui/button';
import { useLocalCollectionSource } from '@/Composables/useCollectionSource';
import PurposeChip from '@/Features/Admin/Responsibilities/PurposeChip.vue';
import { formatDate } from '@/Utils/dateTime';

interface ResponsibilityRow {
  is_super_admin: boolean;
  delete_blocked: boolean;
  id: string;
  title: string;
  description: string | null;
  purpose: string | null;
  purpose_label: string | null;
  permissions_count: number;
  duties_count: number;
  people_count: number;
  updated_at: string | null;
}

const props = defineProps<{
  responsibilities: ResponsibilityRow[];
}>();

const canCreate = computed(() => Boolean(usePage().props.auth?.can?.create?.role));

const source = useLocalCollectionSource<ResponsibilityRow>({
  items: toRef(props, 'responsibilities'),
  searchText: row => [row.title, row.description ?? '', row.purpose_label ?? ''],
  defaultSort: 'purpose:asc',
  sortOptions: () => [
    { value: 'purpose:asc', label: $t('responsibilities.index.sort_purpose'), by: row => `${row.purpose ? 0 : 1}${row.title}` },
    { value: 'title:asc', label: $t('Pagal pavadinimą (A–Z)'), by: row => row.title },
    { value: 'title:desc', label: $t('Pagal pavadinimą (Z–A)'), by: row => row.title },
    { value: 'people:desc', label: $t('responsibilities.index.sort_people'), by: row => row.people_count },
    { value: 'people:asc', label: $t('Mažiausiai žmonių'), by: row => row.people_count },
    { value: 'updated_at:desc', label: $t('Neseniai atnaujintos'), by: row => row.updated_at ?? '' },
    { value: 'updated_at:asc', label: $t('Seniausiai atnaujintos'), by: row => row.updated_at ?? '' },
  ],
});

const counts = (row: ResponsibilityRow) => [
  $tChoice('responsibilities.index.permissions', row.permissions_count, { count: row.permissions_count }),
  $tChoice('responsibilities.index.duties', row.duties_count, { count: row.duties_count }),
  $tChoice('responsibilities.index.people', row.people_count, { count: row.people_count }),
].join(' · ');

const columns = computed<CollectionColumn[]>(() => [
  { key: 'title', label: $t('responsibilities.entity.singular'), sortField: 'title' },
  { key: 'purpose', label: $t('responsibilities.index.purpose'), class: 'w-56' },
  { key: 'permissions', label: $t('responsibilities.facts.permissions'), class: 'w-24' },
  { key: 'duties', label: $t('responsibilities.facts.duties'), class: 'w-28' },
  { key: 'people', label: $t('responsibilities.facts.people'), class: 'w-24', sortField: 'people' },
  { key: 'updated', label: $t('Atnaujinta'), class: 'w-32', sortField: 'updated_at' },
]);
const rowActions = (item: ResponsibilityRow): AdminRecordAction[] => [
  { key: 'open', label: $t('Atidaryti'), icon: ArrowUpRight, role: 'open' as const, href: route('responsibilities.show', item.id) },
  ...(rowAbilities.can(item.id, 'update') && !item.is_super_admin ? [{ key: 'edit', label: $t('Redaguoti'), icon: Pencil, role: 'edit' as const, href: route('responsibilities.edit', item.id) }] : []),
  ...(rowAbilities.can(item.id, 'delete') && !item.delete_blocked ? [{ key: `delete:${item.id}:${item.title}`, label: $t('Ištrinti'), icon: Trash2, destructive: true }] : []),
];

const rowAbilities = useRowAbilities('responsibilities', () => source.items.value.map(item => item.id));
const collectionActions = useCollectionRecordActions({
  source,
  deletePermanently: true,
  routePrefix: 'responsibilities',
  onMutated: id => rowAbilities.invalidate([id]),
});
</script>
