<template>
  <CollectionPage
    :source
    collection="roles"
    entity-type="role"
    :eyebrow="`${$t('shell.workspaces.sistema.title')} · ${$t('shell.sections.roles')}`"
    :title="$t('Rolės')"
    :lead="$t('Rolė sujungia leidimus; ją priskiri pareigybei, ir visi jos nariai gauna tuos leidimus.')"
    default-view="table"
    :item-key="role => String(role.id)"
    :columns
    :search-placeholder="$t('Ieškoti rolių')"
  >
    <template #actions>
      <Button v-if="canCreate" as-child variant="brand" size="lg">
        <Link :href="route('roles.create')">
          <Plus aria-hidden="true" />
          {{ $t('Nauja rolė') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center">
        <div class="min-w-0 flex-1">
          <CollectionPrimaryCell :title="item.name" :href="route('roles.show', item.id)" :sub="permissionCount(item)" />
        </div>
        <CollectionRowActions :actions="actions.rowActions(item, item.name, false)" @select="key => actions.select(key)" />
      </article>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell v-if="column.key === 'name'" :title="item.name" :href="route('roles.show', item.id)" />
      <span v-else-if="column.key === 'permissions'" class="tabular-nums text-muted-foreground">{{ item.permissions_count ?? 0 }}</span>
      <span v-else-if="column.key === 'updated'" class="tabular-nums text-muted-foreground">{{ formatDate(item.updated_at) }}</span>
      <CollectionRowActions v-else-if="column.key === 'actions'" :actions="actions.rowActions(item, item.name, false)" @select="key => actions.select(key)" />
    </template>
  </CollectionPage>

  <CollectionConfirmAction :dialog="actions.dialog.value" @confirm="actions.confirm" @cancel="actions.pending.value = null" />
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
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
import { formatDate } from '@/Utils/dateTime';

interface RoleRow { id: string | number; name: string; permissions_count?: number; created_at: string; updated_at: string }

const props = defineProps<{
  roles: RoleRow[];
}>();

const canCreate = computed(() => Boolean(usePage().props.auth?.can?.create?.role));

const source = useLocalCollectionSource<RoleRow>({
  items: toRef(props, 'roles'),
  searchText: role => [role.name],
  defaultSort: 'name:asc',
  sortOptions: [
    { value: 'name:asc', label: $t('Pagal pavadinimą (A–Z)'), by: role => role.name },
    { value: 'name:desc', label: $t('Pagal pavadinimą (Z–A)'), by: role => role.name },
    { value: 'updated_at:desc', label: $t('Neseniai atnaujintos'), by: role => role.updated_at },
  ],
});

const actions = useCollectionRecordActions({ routePrefix: 'roles', canDelete: () => canCreate.value });

const permissionCount = (role: RoleRow) => $tChoice(':count leidimas|:count leidimai|:count leidimų', role.permissions_count ?? 0, { count: role.permissions_count ?? 0 });

const columns = computed<CollectionColumn[]>(() => [
  { key: 'name', label: $t('Rolė'), sortField: 'name' },
  { key: 'permissions', label: $t('Leidimai'), class: 'w-32' },
  { key: 'updated', label: $t('Atnaujinta'), class: 'w-36' },
  { key: 'actions', label: $t('Veiksmai'), class: 'w-px text-right', pinned: true },
]);
</script>
