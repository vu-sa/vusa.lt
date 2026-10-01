<template>
  <CollectionPage
    :source
    :collection="resource"
    :entity-type="entityType"
    :eyebrow="`${$t('shell.workspaces.sistema.title')} · ${$t('shell.sections.tipai')}`"
    :title="$t(`types.${typeKind}.title`)"
    :lead="isTrash
      ? $t('Ištrinti tipai. Atkurk tai, ko dar reikia.')
      : $t(`types.${typeKind}.lead`)"
    default-view="table"
    :item-key="type => String(type.id)"
    :columns
    :trash="{ count: deletedCount, active: isTrash }"
    :search-placeholder="$t('Ieškoti tipų')"
  >
    <template #row="{ item }">
      <article class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center">
        <div class="min-w-0 flex-1">
          <CollectionPrimaryCell :title="titleOf(item)" :href="isTrash ? undefined : route(`${resource}.show`, item.id)" :sub="item.slug" mono />
        </div>
        <CollectionRowActions :actions="actionsFor(item)" @select="key => actions.select(key, item.force_delete_blocked_reason)" />
      </article>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell v-if="column.key === 'title'" :title="titleOf(item)" :href="isTrash ? undefined : route(`${resource}.show`, item.id)" :sub="item.slug" mono />
      <span v-else-if="column.key === 'updated'" class="tabular-nums text-muted-foreground">{{ formatDate(item.updated_at) }}</span>
      <CollectionRowActions v-else-if="column.key === 'actions'" :actions="actionsFor(item)" @select="key => actions.select(key, item.force_delete_blocked_reason)" />
    </template>
  </CollectionPage>

  <CollectionConfirmAction :dialog="actions.dialog.value" @confirm="actions.confirm" @cancel="actions.pending.value = null" />
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, toRef } from 'vue';

import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import type { CollectionColumn } from '@/Components/Collection/types';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { ModelEnum } from '@/Types/enums';
import { useCollectionRecordActions } from '@/Composables/useCollectionRecordActions';
import { isTrashView, useLocalCollectionSource } from '@/Composables/useCollectionSource';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { formatDate } from '@/Utils/dateTime';

type TypeRow = (App.Entities.InstitutionType | App.Entities.DutyType) & { force_delete_blocked_reason?: string | null };

const props = defineProps<{
  typeKind: 'institutionType' | 'dutyType';
  types: TypeRow[];
  deletedCount: number;
}>();

const resource = props.typeKind === 'institutionType' ? 'institutionTypes' : 'dutyTypes';
const entityType = props.typeKind === 'institutionType' ? ModelEnum.INSTITUTION_TYPE : ModelEnum.DUTY_TYPE;
const page = usePage();
const isTrash = isTrashView();
const canForceDelete = computed(() => Boolean(page.props.auth?.can?.forceDelete?.[props.typeKind]));

const titleOf = (type: TypeRow) => getTranslatedValue(type.title) || type.slug || '—';

const source = useLocalCollectionSource<TypeRow>({
  items: toRef(props, 'types'),
  searchText: type => [titleOf(type), type.slug],
  defaultSort: 'title:asc',
  sortOptions: () => [
    { value: 'title:asc', label: $t('Pagal pavadinimą (A–Z)'), by: titleOf },
    { value: 'title:desc', label: $t('Pagal pavadinimą (Z–A)'), by: titleOf },
    { value: 'updated_at:desc', label: $t('Neseniai atnaujinti'), by: type => type.updated_at },
  ],
});

const actions = useCollectionRecordActions({
  routePrefix: resource,
  canDelete: () => Boolean(page.props.auth?.can?.delete?.[props.typeKind]),
  canRestore: () => Boolean(page.props.auth?.can?.restore?.[props.typeKind]),
  canForceDelete: () => canForceDelete.value,
});
const actionsFor = (type: TypeRow) => actions.rowActions(type, titleOf(type), isTrash);

const columns = computed<CollectionColumn[]>(() => [
  { key: 'title', label: $t('Tipas'), sortField: 'title' },
  { key: 'updated', label: $t('Atnaujinta'), class: 'w-36' },
  { key: 'actions', label: $t('Veiksmai'), class: 'w-px text-right', pinned: true },
]);
</script>
