<template>
  <CollectionPage
    :row-actions="item => rowActions(item)"
    :source
    collection="problemCategories"
    :eyebrow="$t('shell.workspaces.atstovavimas.title')"
    :title="$t('shell.sections.problemu_kategorijos')"
    :lead="$t('Kategorijos bendros visiems padaliniams: pagal jas filtruojamos problemos.')"
    default-view="table"
    :item-key="categoryKey"
    :columns
    table-fixed
    actions-width="w-40"
    table-min-width="min-w-[52rem]"
    :search-placeholder="$t('Ieškoti kategorijų')"
    @row-action="(item, key) => selectRowAction(key, item)"
  >
    <template #actions>
      <Button variant="brand" @click="openSheet()">
        <Plus aria-hidden="true" />
        {{ $t('Nauja kategorija') }}
      </Button>
    </template>

    <template #row="{ item, actions: recordActions, view }">
      <CollectionRow :spotlight="item === source?.items.value[0]" :actions="view === 'preview' ? [] : recordActions" @select="key => selectRowAction(key, item)" class="px-3 py-3 sm:px-4">
        <div class="min-w-0 flex-1">
          <button type="button" class="block max-w-full text-left font-medium hover:text-brand" @click="openSheet(item)">
            {{ title(item) }}
          </button>
          <p class="mt-0.5 truncate text-sm text-muted-foreground">
            {{ [description(item), usage(item)].filter(Boolean).join(' · ') }}
          </p>
        </div>
      </CollectionRow>
    </template>

    <template #cell="{ item, column }">
      <button v-if="column.key === 'name'" type="button" class="font-medium hover:text-brand" @click="openSheet(item)">
        {{ title(item) }}
      </button>
      <span v-else-if="column.key === 'description'" class="text-muted-foreground">{{ description(item) || '—' }}</span>
      <span v-else-if="column.key === 'problems_count'" class="tabular-nums">{{ item.problems_count }}</span>
      <span v-else-if="column.key === 'created_at' || column.key === 'updated_at'" class="tabular-nums">{{ formatRecordDate(item[column.key]) }}</span>
    </template>

    <template #empty>
      <EmptyState
        mode="empty"
        :icon="CategoryIcon"
        :title="$t('Kategorijų dar nėra')"
        :description="$t('Sukurk kategoriją, kad panašias problemas būtų lengva rasti.')"
        :action-label="$t('Nauja kategorija')"
        @action="openSheet()"
      />
    </template>
  </CollectionPage>

  <ProblemCategorySheetForm v-model:open="sheetOpen" :category="editing" @saved="editing = null" />

  <ConfirmDialog
    :open="toDelete !== null"
    :title="$t('Ištrinti kategoriją?')"
    :description="$t('Kategorija nepriskirta nė vienai problemai.')"
    :confirm-label="$t('Ištrinti')"
    destructive
    @update:open="!$event && (toDelete = null)"
    @confirm="remove"
  />
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref, toRef } from 'vue';

import type { CollectionColumn } from '@/Components/Collection/types';
import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import type { CollectionRowAction } from '@/Components/Collection/CollectionRowActions.vue';
import { CategoryIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { ConfirmDialog, EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useLocalCollectionSource } from '@/Composables/useCollectionSource';
import ProblemCategorySheetForm, { type ProblemCategoryInput } from '@/Features/Admin/ProblemCategories/ProblemCategorySheetForm.vue';
import { formatRecordDate } from '@/Utils/dateTime';

type Category = ProblemCategoryInput & { id: number; slug: string; problems_count: number; created_at: string | null; updated_at: string | null };

const props = defineProps<{
  problemCategories: Category[];
  abilities: { delete: boolean };
}>();

const source = useLocalCollectionSource<Category>({
  items: toRef(props, 'problemCategories'),
  searchText: category => [category.name.lt, category.name.en, category.description?.lt],
  defaultSort: 'name:asc',
  sortOptions: () => [
    { value: 'name:asc', label: $t('Pagal pavadinimą (A–Z)'), by: category => title(category) },
    { value: 'name:desc', label: $t('Pagal pavadinimą (Z–A)'), by: category => title(category) },
    { value: 'problems_count:desc', label: $t('Dažniausiai naudojamos'), by: category => category.problems_count },
    { value: 'problems_count:asc', label: $t('Rečiausiai naudojamos'), by: category => category.problems_count },
    { value: 'created_at:desc', label: $t('Naujausios pirmiausia'), by: item => item.created_at ?? '' },
    { value: 'created_at:asc', label: $t('Seniausios pirmiausia'), by: item => item.created_at ?? '' },
    { value: 'updated_at:desc', label: $t('Paskutiniai atnaujinti'), by: item => item.updated_at ?? '' },
    { value: 'updated_at:asc', label: $t('Seniausios atnaujintos'), by: item => item.updated_at ?? '' },
  ],
});

// A category in use cannot be deleted (ProblemCategoryController::destroy), so the action is not offered.
const rowActions = (category: Category): CollectionRowAction[] => [
  { key: 'edit', role: 'edit' as const, label: $t('Redaguoti'), icon: Pencil },
  ...(props.abilities.delete && category.problems_count === 0
    ? [{ key: 'delete', label: $t('Ištrinti'), icon: Trash2, destructive: true }]
    : []),
];

function selectRowAction(key: string, category: Category): void {
  if (key === 'edit') openSheet(category);
  if (key === 'delete') toDelete.value = category;
}

const columns = computed<CollectionColumn[]>(() => [
  { key: 'name', label: $t('Kategorija'), sortField: 'name' },
  { key: 'description', label: $t('Aprašymas'), class: 'w-64' },
  { key: 'problems_count', label: $tChoice('entities.problem.model', 2), class: 'w-28', sortField: 'problems_count' },
  { key: 'created_at', label: $t('Sukurta'), class: 'w-36', defaultHidden: true, sortField: 'created_at' },
  { key: 'updated_at', label: $t('Atnaujinta'), class: 'w-36', defaultHidden: true, sortField: 'updated_at' },
]);

const sheetOpen = ref(false);
const editing = ref<Category | null>(null);
const toDelete = ref<Category | null>(null);

const categoryKey = (category: Category) => String(category.id);
const title = (category: Category) => category.name.lt || category.name.en || '—';
const description = (category: Category) => category.description?.lt || category.description?.en || '';
const usage = (category: Category) => `${category.problems_count} ${$tChoice('entities.problem.model', category.problems_count)}`;

function openSheet(category: Category | null = null): void {
  editing.value = category;
  sheetOpen.value = true;
}

function remove(): void {
  if (!toDelete.value) {
    return;
  }

  const { id } = toDelete.value;
  toDelete.value = null;

  router.delete(route('problemCategories.destroy', id), { preserveScroll: true });
}
</script>
