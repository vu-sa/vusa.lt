<template>
  <CollectionPage
    :row-actions="item => actionsFor(item)"
    :source
    :collection="resource"
    :eyebrow="$t('shell.workspaces.sistema.title')"
    :title="$t(`types.${typeKind}.title`)"
    :lead="isTrash
      ? $t('Ištrinti tipai. Atkurk tai, ko dar reikia.')
      : $t(`types.${typeKind}.lead`)"
    default-view="table"
    :item-key="type => String(type.id)"
    :columns
    table-fixed
    actions-width="w-44"
    table-min-width="min-w-[53rem]"
    :trash="{ count: deletedCount, active: isTrash }"
    @row-action="(item, key) => actions.select(key, item.force_delete_blocked_reason)"
    :search-placeholder="$t('Ieškoti tipų')"
  >
    <template #actions>
      <SpotlightPopover
        v-if="canCreate && !isTrash"
        :title="$t(`shell.actions.${createAction}.title`)"
        :description="$t(`shell.actions.${createAction}.description`)"
        :is-dismissed="spotlight.isDismissed.value"
        align="end"
        @dismiss="spotlight.dismiss"
      >
        <Button as-child variant="brand" size="lg">
          <Link :href="route(`${resource}.create`)" @click="spotlight.dismiss">
            <Plus aria-hidden="true" />
            {{ $t(`shell.actions.${createAction}.title`) }}
          </Link>
        </Button>
      </SpotlightPopover>
    </template>

    <template #row="{ item, actions: recordActions }">
      <CollectionRow :actions="recordActions" :spotlight="item === source?.items.value[0]" class="px-4 py-4" @select="key => actions.select(key, item.force_delete_blocked_reason)">
        <div class="min-w-0 flex-1">
          <CollectionPrimaryCell
            :title="titleOf(item)"
            :href="isTrash ? undefined : route(`${resource}.show`, item.id)"
            :sub="subOf(item)"
            mono
          />
        </div>
      </CollectionRow>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell
        v-if="column.key === 'title'"
        :title="titleOf(item)"
        :href="isTrash ? undefined : route(`${resource}.show`, item.id)"
        :sub="subOf(item)"
        mono
      />
      <span v-else-if="column.key === 'created_at' || column.key === 'updated_at'" class="tabular-nums text-muted-foreground">{{ formatRecordDate(item[column.key]) }}</span>
    </template>
  </CollectionPage>

  <CollectionConfirmAction :dialog="actions.dialog.value" @confirm="actions.confirm" @cancel="actions.pending.value = null" />
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Plus } from '@lucide/vue';
import { computed, toRef } from 'vue';

import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import type { CollectionColumn } from '@/Components/Collection/types';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { Button } from '@/Components/ui/button';
import { useCollectionRecordActions } from '@/Composables/useCollectionRecordActions';
import { isTrashView, useLocalCollectionSource } from '@/Composables/useCollectionSource';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { formatRecordDate } from '@/Utils/dateTime';

type TypeRow = (App.Entities.InstitutionType | App.Entities.DutyType) & { force_delete_blocked_reason?: string | null };

const props = defineProps<{
  typeKind: 'institutionType' | 'dutyType';
  types: TypeRow[];
  deletedCount: number;
}>();

const resource = props.typeKind === 'institutionType' ? 'institutionTypes' : 'dutyTypes';
const createAction = props.typeKind === 'institutionType' ? 'new_institution_type' : 'new_duty_type';
const spotlight = props.typeKind === 'institutionType'
  ? useFeatureSpotlight('institution-type-create-v1')
  : useFeatureSpotlight('duty-type-create-v1');
const page = usePage();
const isTrash = isTrashView();
const canCreate = computed(() => Boolean(page.props.auth?.can?.create?.[props.typeKind]));
const canForceDelete = computed(() => Boolean(page.props.auth?.can?.forceDelete?.[props.typeKind]));

const titleOf = (type: TypeRow) => getTranslatedValue(type.title) || ('slug' in type ? type.slug : null) || '—';
const subOf = (type: TypeRow): string | undefined => {
  const slug = props.typeKind === 'institutionType' ? (type as App.Entities.InstitutionType).slug : undefined;
  return slug && slug !== titleOf(type) ? slug : undefined;
};

const source = useLocalCollectionSource<TypeRow>({
  items: toRef(props, 'types'),
  searchText: type => ('slug' in type && type.slug ? [titleOf(type), type.slug] : [titleOf(type)]),
  defaultSort: 'title:asc',
  sortOptions: () => [
    { value: 'created_at:asc', label: $t('Seniausiai sukurti'), by: type => type.created_at ?? '' },
    { value: 'created_at:desc', label: $t('Naujausiai sukurti'), by: type => type.created_at ?? '' },
    { value: 'title:asc', label: $t('Pagal pavadinimą (A–Z)'), by: titleOf },
    { value: 'title:desc', label: $t('Pagal pavadinimą (Z–A)'), by: titleOf },
    { value: 'updated_at:desc', label: $t('Neseniai atnaujinti'), by: type => type.updated_at },
    { value: 'updated_at:asc', label: $t('Seniausiai atnaujinti'), by: type => type.updated_at },
  ],
});

const actions = useCollectionRecordActions({
  source,
  routePrefix: resource,
  openHref: id => route(`${resource}.show`, id),
  canDelete: () => Boolean(page.props.auth?.can?.delete?.[props.typeKind]),
  canRestore: () => Boolean(page.props.auth?.can?.restore?.[props.typeKind]),
  canForceDelete: () => canForceDelete.value,
});
const actionsFor = (type: TypeRow) => actions.rowActions(type, titleOf(type), isTrash);

const columns = computed<CollectionColumn[]>(() => [
  { key: 'title', label: $t('Tipas'), sortField: 'title' },
  { key: 'created_at', label: $t('Sukurta'), class: 'w-36', sortField: 'created_at', defaultHidden: true },
  { key: 'updated_at', label: $t('Atnaujinta'), class: 'w-36', sortField: 'updated_at', defaultHidden: true },
]);
</script>
