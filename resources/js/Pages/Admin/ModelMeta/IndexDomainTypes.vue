<template>
  <CollectionPage
    :source
    :collection="resource"
    :entity-type
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
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Plus } from 'lucide-vue-next';
import { computed, toRef } from 'vue';

import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import type { CollectionColumn } from '@/Components/Collection/types';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { Button } from '@/Components/ui/button';
import { ModelEnum } from '@/Types/enums';
import { useCollectionRecordActions } from '@/Composables/useCollectionRecordActions';
import { isTrashView, useLocalCollectionSource } from '@/Composables/useCollectionSource';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
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
const createAction = props.typeKind === 'institutionType' ? 'new_institution_type' : 'new_duty_type';
const spotlight = props.typeKind === 'institutionType'
  ? useFeatureSpotlight('institution-type-create-v1')
  : useFeatureSpotlight('duty-type-create-v1');
const page = usePage();
const isTrash = isTrashView();
const canCreate = computed(() => Boolean(page.props.auth?.can?.create?.[props.typeKind]));
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
