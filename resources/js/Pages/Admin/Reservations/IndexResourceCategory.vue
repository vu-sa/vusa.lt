<template>
  <CollectionPage
    :row-actions="() => rowActions"
    :source
    collection="resourceCategories"
    :eyebrow="$t('shell.workspaces.rezervacijos.title')"
    :title="$t('Išteklių kategorijos')"
    :lead="$t('Kategorijos sugrupuoja išteklius, kad juos būtų lengviau rasti.')"
    default-view="table"
    :item-key="categoryKey"
    :columns
    table-fixed
    actions-width="w-40"
    table-min-width="min-w-[48rem]"
    :search-placeholder="$t('Ieškoti kategorijų')"
    @row-action="(item, key) => selectRowAction(key, item)"
  >
    <template #actions>
      <Button v-if="canCreate" variant="brand" @click="openSheet()">
        <Plus aria-hidden="true" />
        {{ $t('Nauja kategorija') }}
      </Button>
    </template>

    <template #row="{ item, actions: recordActions, view }">
      <CollectionRow :spotlight="item === source?.items.value[0]" :actions="view === 'preview' ? [] : recordActions" @select="key => selectRowAction(key, item)" class="px-3 py-3 sm:px-4">
        <template v-if="item.icon" #lead>
          <Icon :icon="`fluent:${item.icon}`" class="size-5 text-muted-foreground" aria-hidden="true" />
        </template>
        <div class="min-w-0 flex-1">
          <button v-if="canUpdate" type="button" class="block max-w-full text-left font-medium hover:text-brand" @click="openSheet(item)">
            {{ title(item) }}
          </button>
          <span v-else class="block font-medium">{{ title(item) }}</span>
          <p v-if="description(item)" class="mt-0.5 truncate text-sm text-muted-foreground">
            {{ description(item) }}
          </p>
        </div>
      </CollectionRow>
    </template>

    <template #cell="{ item, column }">
      <button v-if="column.key === 'name' && canUpdate" type="button" class="inline-flex items-center gap-2 font-medium hover:text-brand" @click="openSheet(item)">
        <Icon v-if="item.icon" :icon="`fluent:${item.icon}`" class="size-4 text-muted-foreground" aria-hidden="true" />
        {{ title(item) }}
      </button>
      <span v-else-if="column.key === 'name'" class="font-medium">{{ title(item) }}</span>
      <span v-else-if="column.key === 'description'" class="text-muted-foreground">{{ description(item) || '—' }}</span>
      <span v-else-if="column.key === 'created_at' || column.key === 'updated_at'" class="tabular-nums">{{ formatRecordDate(item[column.key]) }}</span>
    </template>

    <template #empty>
      <EmptyState
        mode="empty"
        :icon="CategoryIcon"
        :title="$t('Kategorijų dar nėra')"
        :description="$t('Sukurk kategoriją, kad panašūs ištekliai atsidurtų vienoje vietoje.')"
        :action-label="canCreate ? $t('Nauja kategorija') : undefined"
        @action="openSheet()"
      />
    </template>
  </CollectionPage>

  <ResourceCategorySheetForm v-model:open="sheetOpen" :category="editing" @saved="refresh" />

  <ConfirmDialog
    :open="toDelete !== null"
    :title="$t('Ištrinti kategoriją?')"
    :description="$t('Ištekliai šioje kategorijoje liks be kategorijos.')"
    :confirm-label="$t('Ištrinti')"
    destructive
    @update:open="!$event && (toDelete = null)"
    @confirm="remove"
  />
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';

import type { CollectionColumn } from '@/Components/Collection/types';
import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import type { CollectionRowAction } from '@/Components/Collection/CollectionRowActions.vue';
import { CategoryIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { ConfirmDialog, EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useDatabaseCollectionSource } from '@/Composables/useCollectionSource';
import ResourceCategorySheetForm from '@/Features/Admin/ResourceCategories/ResourceCategorySheetForm.vue';
import { formatRecordDate } from '@/Utils/dateTime';

interface Translation { lt?: string; en?: string }
type Category = App.Entities.ResourceCategory & { name: Translation; description?: Translation | null; icon?: string | null };

const props = defineProps<{
  abilities: { update: boolean; delete: boolean };
  resourceCategories: { data: Category[]; meta: { total: number; per_page: number; current_page: number; last_page: number } };
}>();

const canCreate = computed(() => Boolean(usePage().props.auth?.can?.create?.resource));
const canUpdate = computed(() => props.abilities.update);
const canDelete = computed(() => props.abilities.delete);
const rowActions = computed<CollectionRowAction[]>(() => [
  ...(canUpdate.value ? [{ key: 'edit', role: 'edit' as const, label: $t('Redaguoti'), icon: Pencil }] : []),
  ...(canDelete.value ? [{ key: 'delete', label: $t('Ištrinti'), icon: Trash2, destructive: true }] : []),
]);

function selectRowAction(key: string, item: Category): void {
  if (key === 'edit') openSheet(item);
  if (key === 'delete') toDelete.value = item;
}

const sheetOpen = ref(false);
const editing = ref<Category | null>(null);
const toDelete = ref<Category | null>(null);

const source = useDatabaseCollectionSource<Category>({
  endpoint: route('api.v1.admin.resourceCategories.index'),
  initial: {
    items: props.resourceCategories.data,
    total: props.resourceCategories.meta.total,
    perPage: props.resourceCategories.meta.per_page,
    currentPage: props.resourceCategories.meta.current_page,
    lastPage: props.resourceCategories.meta.last_page,
  },
  defaultSort: 'name:asc',
  sortOptions: () => [
    { value: 'name:asc', label: $t('Pagal pavadinimą (A–Z)') },
    { value: 'name:desc', label: $t('Pagal pavadinimą (Z–A)') },
    { value: 'created_at:desc', label: $t('Naujausios pirmiausia') },
    { value: 'created_at:asc', label: $t('Seniausios pirmiausia') },
    { value: 'updated_at:desc', label: $t('Paskutiniai atnaujinti') },
    { value: 'updated_at:asc', label: $t('Seniausios atnaujintos') },
  ],
});

const columns = computed<CollectionColumn[]>(() => [
  { key: 'name', label: $t('Kategorija'), sortField: 'name' },
  { key: 'description', label: $t('Aprašymas'), class: 'w-64' },
  { key: 'created_at', label: $t('Sukurta'), class: 'w-36', defaultHidden: true, sortField: 'created_at' },
  { key: 'updated_at', label: $t('Atnaujinta'), class: 'w-36', defaultHidden: true, sortField: 'updated_at' },
]);

const categoryKey = (category: Category) => String(category.id);
const title = (category: Category) => category.name.lt || category.name.en || '—';
const description = (category: Category) => category.description?.lt || category.description?.en || '';

function openSheet(category: Category | null = null): void {
  editing.value = category;
  sheetOpen.value = true;
}

function refresh(): void {
  editing.value = null;
  source.refresh();
}

function remove(): void {
  if (!toDelete.value) {
    return;
  }

  const { id } = toDelete.value;
  toDelete.value = null;

  router.delete(route('resourceCategories.destroy', id), {
    preserveScroll: true,
    onSuccess: () => source.refresh(),
  });
}
</script>
