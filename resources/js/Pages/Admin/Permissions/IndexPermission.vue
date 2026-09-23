<template>
  <CollectionPage
    :source
    collection="permissions"
    entity-type="permission"
    :eyebrow="`${$t('shell.workspaces.sistema.title')} · ${$t('shell.sections.leidimai')}`"
    :title="$t('Leidimai')"
    :lead="$t('Leidimo pavadinimas sako, ką jis leidžia: išteklius.veiksmas.apimtis. Leidimus priskiri per roles.')"
    default-view="table"
    :item-key="permission => String(permission.id)"
    :columns
    :search-placeholder="$t('Ieškoti leidimų')"
  >
    <template #row="{ item }">
      <article class="px-4 py-4">
        <CollectionPrimaryCell :title="item.name" :sub="scopeLabel(item)" />
      </article>
    </template>

    <template #cell="{ item, column }">
      <span v-if="column.key === 'name'" class="font-mono text-sm font-bold">{{ item.name }}</span>
      <span v-else-if="column.key === 'resource'" class="text-muted-foreground">{{ part(item, 0) }}</span>
      <span v-else-if="column.key === 'action'" class="text-muted-foreground">{{ part(item, 1) }}</span>
      <span v-else-if="column.key === 'scope'" class="text-muted-foreground">{{ scopeLabel(item) }}</span>
    </template>
  </CollectionPage>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed, toRef } from 'vue';

import type { CollectionColumn } from '@/Components/Collection/types';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { useLocalCollectionSource } from '@/Composables/useCollectionSource';

type PermissionRow = { id: string | number; name: string };

const props = defineProps<{
  permissions: PermissionRow[];
}>();

/** `news.update.padalinys` → ['news', 'update', 'padalinys']. */
const part = (permission: PermissionRow, index: number) => permission.name.split('.')[index] ?? '—';

const SCOPES: Record<string, string> = { '*': 'Visi', 'padalinys': 'Padalinys', 'own': 'Savi' };
const scopeLabel = (permission: PermissionRow) => $t(SCOPES[part(permission, 2)] ?? part(permission, 2));

const source = useLocalCollectionSource<PermissionRow>({
  items: toRef(props, 'permissions'),
  searchText: permission => [permission.name],
  defaultSort: 'name:asc',
  sortOptions: [
    { value: 'name:asc', label: $t('Pagal pavadinimą (A–Z)'), by: permission => permission.name },
    { value: 'name:desc', label: $t('Pagal pavadinimą (Z–A)'), by: permission => permission.name },
  ],
  facets: [
    { field: 'resource', label: $t('Išteklius'), get: permission => part(permission, 0) },
    { field: 'action', label: $t('Veiksmas'), get: permission => part(permission, 1) },
    { field: 'scope', label: $t('Apimtis'), get: permission => part(permission, 2), valueLabel: value => $t(SCOPES[value] ?? value) },
  ],
});

const columns = computed<CollectionColumn[]>(() => [
  { key: 'name', label: $t('Leidimas'), sortField: 'name' },
  { key: 'resource', label: $t('Išteklius'), class: 'w-40' },
  { key: 'action', label: $t('Veiksmas'), class: 'w-32' },
  { key: 'scope', label: $t('Apimtis'), class: 'w-32' },
]);
</script>
