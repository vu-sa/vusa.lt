<template>
  <CollectionPage
    :source
    collection="tenants"
    entity-type="tenant"
    :eyebrow="`${$t('shell.workspaces.organizacija.title')} · ${$t('shell.sections.padaliniai')}`"
    :title="$t('Padaliniai')"
    :lead="$t('VU SA padaliniai: kiekvienas turi savo svetainės dalį, narius ir pareigybes.')"
    default-view="table"
    :item-key="tenant => String(tenant.id)"
    :columns
    :search-placeholder="$t('Ieškoti padalinių')"
  >
    <template #actions>
      <Button v-if="canCreate" as-child variant="brand" size="lg">
        <Link :href="route('tenants.create')">
          <Plus aria-hidden="true" />
          {{ $t('Naujas padalinys') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center">
        <div class="min-w-0 flex-1">
          <CollectionPrimaryCell :title="item.fullname" :href="route('tenants.edit', item.id)" :sub="item.alias" mono />
          <p class="mt-2 text-xs text-muted-foreground">
            {{ item.shortname }} · {{ item.type }}
          </p>
        </div>
        <CollectionRowActions :actions="actions.rowActions(item, item.fullname, false)" @select="key => actions.select(key)" />
      </article>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell v-if="column.key === 'fullname'" :title="item.fullname" :href="route('tenants.edit', item.id)" :sub="item.alias" mono />
      <span v-else-if="column.key === 'shortname'">{{ item.shortname }}</span>
      <span v-else-if="column.key === 'type'" class="text-muted-foreground">{{ item.type }}</span>
      <CollectionRowActions v-else-if="column.key === 'actions'" :actions="actions.rowActions(item, item.fullname, false)" @select="key => actions.select(key)" />
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

type TenantRow = Pick<App.Entities.Tenant, 'id' | 'fullname' | 'shortname' | 'alias' | 'type'>;

const props = defineProps<{
  tenants: TenantRow[];
}>();

const canCreate = computed(() => Boolean(usePage().props.auth?.can?.create?.tenant));

const source = useLocalCollectionSource<TenantRow>({
  items: toRef(props, 'tenants'),
  searchText: tenant => [tenant.fullname, tenant.shortname, tenant.alias],
  defaultSort: 'fullname:asc',
  sortOptions: [
    { value: 'fullname:asc', label: $t('Pagal pavadinimą (A–Z)'), by: tenant => tenant.fullname },
    { value: 'fullname:desc', label: $t('Pagal pavadinimą (Z–A)'), by: tenant => tenant.fullname },
  ],
  facets: [{ field: 'type', label: $t('Tipas'), get: tenant => tenant.type }],
});

const actions = useCollectionRecordActions({ routePrefix: 'tenants', canDelete: () => canCreate.value });

const columns = computed<CollectionColumn[]>(() => [
  { key: 'fullname', label: $t('Padalinys'), sortField: 'fullname' },
  { key: 'shortname', label: $t('Trumpinys'), class: 'w-40' },
  { key: 'type', label: $t('Tipas'), class: 'w-40' },
  { key: 'actions', label: $t('Veiksmai'), class: 'w-px text-right', pinned: true },
]);
</script>
