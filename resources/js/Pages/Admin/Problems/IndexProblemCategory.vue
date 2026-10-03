<template>
  <CollectionPage
    :source
    collection="problemCategories"
    entity-type="problem"
    :eyebrow="`${$t('shell.workspaces.atstovavimas.title')} · ${$t('shell.sections.problemu_kategorijos')}`"
    :title="$t('shell.sections.problemu_kategorijos')"
    :lead="$t('Kategorijos bendros visiems padaliniams: pagal jas filtruojamos problemos.')"
    default-view="table"
    :item-key="categoryKey"
    :columns
    :search-placeholder="$t('Ieškoti kategorijų')"
  >
    <template #actions>
      <Button variant="brand" @click="openSheet()">
        <Plus aria-hidden="true" />
        {{ $t('Nauja kategorija') }}
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex min-h-16 items-center gap-3 px-3 py-3 sm:px-4">
        <div class="min-w-0 flex-1">
          <button type="button" class="block max-w-full text-left font-medium hover:text-brand" @click="openSheet(item)">
            {{ title(item) }}
          </button>
          <p v-if="description(item)" class="mt-0.5 truncate text-sm text-muted-foreground">
            {{ description(item) }}
          </p>
        </div>
        <span class="shrink-0 text-sm tabular-nums text-muted-foreground">{{ usage(item) }}</span>
        <CollectionRowActions :actions="rowActions(item)" @select="key => selectRowAction(key, item)" />
      </article>
    </template>

    <template #cell="{ item, column }">
      <button v-if="column.key === 'name'" type="button" class="font-medium hover:text-brand" @click="openSheet(item)">
        {{ title(item) }}
      </button>
      <span v-else-if="column.key === 'description'" class="text-muted-foreground">{{ description(item) || '—' }}</span>
      <span v-else-if="column.key === 'problems_count'" class="tabular-nums">{{ item.problems_count }}</span>
      <CollectionRowActions v-else-if="column.key === 'actions'" :actions="rowActions(item)" @select="key => selectRowAction(key, item)" />
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
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref, toRef } from 'vue';

import type { CollectionColumn } from '@/Components/Collection/types';
import CollectionRowActions, { type CollectionRowAction } from '@/Components/Collection/CollectionRowActions.vue';
import { CategoryIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { ConfirmDialog, EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useLocalCollectionSource } from '@/Composables/useCollectionSource';
import ProblemCategorySheetForm, { type ProblemCategoryInput } from '@/Features/Admin/ProblemCategories/ProblemCategorySheetForm.vue';

type Category = ProblemCategoryInput & { id: number; slug: string; problems_count: number };

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
    { value: 'problems_count:desc', label: $t('Dažniausiai naudojamos'), by: category => category.problems_count },
  ],
});

// A category in use cannot be deleted (ProblemCategoryController::destroy), so the action is not offered.
const rowActions = (category: Category): CollectionRowAction[] => [
  { key: 'edit', label: $t('Redaguoti'), icon: Pencil, labelled: true },
  ...(props.abilities.delete && category.problems_count === 0
    ? [{ key: 'delete', label: $t('Ištrinti'), icon: Trash2, destructive: true }]
    : []),
];

function selectRowAction(key: string, category: Category): void {
  if (key === 'edit') openSheet(category);
  if (key === 'delete') toDelete.value = category;
}

const columns = computed<CollectionColumn[]>(() => [
  { key: 'name', label: $t('Kategorija') },
  { key: 'description', label: $t('Aprašymas') },
  { key: 'problems_count', label: $tChoice('entities.problem.model', 2), class: 'w-28' },
  { key: 'actions', label: $t('Veiksmai'), class: 'w-px text-right', pinned: true },
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
