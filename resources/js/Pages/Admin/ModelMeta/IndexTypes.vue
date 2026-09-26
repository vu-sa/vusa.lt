<template>
  <CollectionPage
    :source
    collection="types"
    entity-type="type"
    :eyebrow="`${$t('shell.workspaces.sistema.title')} · ${$t('shell.sections.tipai')}`"
    :title="$t('Tipai')"
    :lead="isTrash
      ? $t('Ištrinti tipai. Atkurk tai, ko dar reikia.')
      : $t('Tipai grupuoja institucijas, pareigybes ir kitus įrašus – pagal juos veikia filtrai ir leidimai.')"
    default-view="table"
    :item-key="type => String(type.id)"
    :columns
    :trash="{ count: deletedCount, active: isTrash }"
    :search-placeholder="$t('Ieškoti tipų')"
  >
    <template #actions>
      <Button v-if="canCreate && !isTrash" as-child variant="brand" size="lg">
        <Link :href="route('types.create')">
          <Plus aria-hidden="true" />
          {{ $t('Naujas tipas') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center">
        <div class="min-w-0 flex-1">
          <CollectionPrimaryCell :title="titleOf(item)" :href="isTrash ? undefined : route('types.edit', item.id)" :sub="item.slug" mono />
          <p class="mt-2 text-xs text-muted-foreground">
            {{ modelLabel(item.model_type) }}
          </p>
        </div>
        <CollectionRowActions :actions="actionsFor(item)" @select="key => actions.select(key, item.force_delete_blocked_reason)" />
      </article>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell v-if="column.key === 'title'" :title="titleOf(item)" :href="isTrash ? undefined : route('types.edit', item.id)" :sub="item.slug" mono />
      <span v-else-if="column.key === 'model'" class="text-muted-foreground">{{ modelLabel(item.model_type) }}</span>
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
import { Button } from '@/Components/ui/button';
import { getEntityTypeDefinition } from '@/Constants/entityTypes';
import { useCollectionRecordActions } from '@/Composables/useCollectionRecordActions';
import { isTrashView, useLocalCollectionSource } from '@/Composables/useCollectionSource';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { formatDate } from '@/Utils/dateTime';

type TypeRow = App.Entities.Type & { force_delete_blocked_reason?: string | null };

const props = defineProps<{
  types: TypeRow[];
  deletedCount: number;
}>();

const page = usePage();
const isTrash = isTrashView();
const canCreate = computed(() => Boolean(page.props.auth?.can?.create?.type));
const canForceDelete = computed(() => Boolean(page.props.auth?.can?.forceDelete?.type));

const titleOf = (type: TypeRow) => getTranslatedValue(type.title) || type.slug || '—';
/** `App\Models\Institution` or the morph alias `institution` → "Institucija". */
function modelLabel(modelType?: string | null): string {
  const definition = getEntityTypeDefinition((modelType ?? '').split('\\').pop() ?? '');

  return definition ? $t(definition.label) : (modelType ?? '—');
}

const source = useLocalCollectionSource<TypeRow>({
  items: toRef(props, 'types'),
  searchText: type => [titleOf(type), type.slug, type.model_type],
  defaultSort: 'title:asc',
  sortOptions: [
    { value: 'title:asc', label: $t('Pagal pavadinimą (A–Z)'), by: titleOf },
    { value: 'title:desc', label: $t('Pagal pavadinimą (Z–A)'), by: titleOf },
    { value: 'updated_at:desc', label: $t('Neseniai atnaujinti'), by: type => type.updated_at },
  ],
  facets: [{ field: 'model_type', label: $t('Kam taikomas'), get: type => type.model_type, valueLabel: modelLabel }],
});

const actions = useCollectionRecordActions({
  routePrefix: 'types',
  canDelete: () => canCreate.value,
  canRestore: () => canCreate.value,
  canForceDelete: () => canForceDelete.value,
});
const actionsFor = (type: TypeRow) => actions.rowActions(type, titleOf(type), isTrash);

const columns = computed<CollectionColumn[]>(() => [
  { key: 'title', label: $t('Tipas'), sortField: 'title' },
  { key: 'model', label: $t('Kam taikomas'), class: 'w-48' },
  { key: 'updated', label: $t('Atnaujinta'), class: 'w-36' },
  { key: 'actions', label: $t('Veiksmai'), class: 'w-px text-right', pinned: true },
]);
</script>
