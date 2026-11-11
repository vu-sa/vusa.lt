<template>
  <CollectionPage
    collection="glossaryTerms"
    :eyebrow="$t('shell.workspaces.pradzia.title')"
    :title="$t('learning.pages.glossary')"
    :lead="$t('learning_library.glossary.lead')"
    :source
    :columns
    :item-key="termKey"
    :row-actions="rowActions"
    default-view="rows"
    :available-views="['rows']"
    table-fixed
    actions-width="w-36"
    table-min-width="min-w-[48rem]"
    :search-placeholder="$t('learning_library.glossary.search_placeholder')"
    @row-action="(item, key) => selectRowAction(key, item)"
  >
    <template #actions>
      <Button v-if="canManageTerms" variant="brand" data-testid="create-glossary-term" @click="openSheet()">
        <Plus aria-hidden="true" />
        {{ $t('learning_library.glossary.new_term') }}
      </Button>
    </template>

    <template #row="{ item, actions: recordActions, view }">
      <CollectionRow
        :id="`term-${item.id}`"
        :spotlight="item === source.items.value[0]"
        :actions="view === 'preview' ? [] : recordActions"
        class="px-3 py-3 sm:px-4"
        @select="key => selectRowAction(key, item)"
      >
        <div class="min-w-0 flex-1">
          <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
            <Link :href="route('glossaryTerms.show', item.id)" class="font-semibold text-foreground underline decoration-border underline-offset-4 hover:decoration-foreground" data-testid="glossary-term-link">
              {{ item.term }}
            </Link>
            <abbr v-if="item.abbreviation" :title="item.term" :class="abbreviationClass" data-testid="glossary-term-abbreviation">{{ item.abbreviation }}</abbr>
            <span class="ml-auto text-xs tabular-nums text-muted-foreground" data-testid="glossary-term-steps-count">
              {{ $tChoice('learning_library.glossary.items_usage', item.itemsCount, { count: String(item.itemsCount) }) }}
            </span>
          </div>
          <p class="mt-1 line-clamp-2 max-w-prose text-sm text-muted-foreground">{{ item.definition }}</p>
        </div>
      </CollectionRow>
    </template>

    <template #cell="{ item, column }">
      <template v-if="column.key === 'term'">
        <Link :href="route('glossaryTerms.show', item.id)" class="font-semibold text-foreground underline decoration-border underline-offset-4 hover:decoration-foreground">{{ item.term }}</Link>
        <abbr v-if="item.abbreviation" :title="item.term" :class="[abbreviationClass, 'ml-2']">{{ item.abbreviation }}</abbr>
      </template>
      <p v-else-if="column.key === 'definition'" class="line-clamp-3 text-sm text-muted-foreground">{{ item.definition }}</p>
      <span v-else-if="column.key === 'stepsCount'" class="tabular-nums text-sm text-muted-foreground" data-testid="glossary-term-steps-count">
        {{ item.itemsCount }}
      </span>
    </template>

    <template #empty>
      <EmptyState
        mode="empty"
        :icon="BookA"
        :title="$t('learning_library.glossary.empty_title')"
        :description="$t('learning_library.glossary.empty_description')"
        :action-label="canManageTerms ? $t('learning_library.glossary.new_term') : undefined"
        @action="openSheet()"
      />
    </template>
  </CollectionPage>

  <GlossaryTermSheet v-if="canManageTerms" v-model:open="sheetOpen" :term="editing" />

  <ConfirmDialog
    v-if="canManageTerms"
    :open="toDelete !== null"
    :title="$t('learning_library.glossary.delete_title')"
    :description="$t('learning_library.glossary.delete_description')"
    :confirm-label="$t('learning_library.glossary.delete_confirm')"
    destructive
    @update:open="!$event && (toDelete = null)"
    @confirm="removeTerm"
  />
</template>

<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { BookA, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref, toRef } from 'vue';

import type { CollectionColumn } from '@/Components/Collection/types';
import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import type { CollectionRowAction } from '@/Components/Collection/CollectionRowActions.vue';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { ConfirmDialog, EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useLocalCollectionSource } from '@/Composables/useCollectionSource';
import GlossaryTermSheet from '@/Features/Admin/Learning/GlossaryTermSheet.vue';
import type { GlossaryTermData } from '@/Types/learning';

const props = defineProps<{
  terms: GlossaryTermData[];
  canManage?: boolean;
}>();

const canManageTerms = computed(() => Boolean(props.canManage));
const termKey = (item: GlossaryTermData) => item.id;
const abbreviationClass = 'inline-flex items-center border border-border px-1.5 text-xs font-bold tracking-wide text-foreground no-underline';

const source = useLocalCollectionSource<GlossaryTermData>({
  items: toRef(props, 'terms'),
  searchText: item => [
    item.term,
    item.abbreviation,
    item.translations?.abbreviation?.lt,
    item.translations?.abbreviation?.en,
    item.definition,
    item.translations?.term?.lt,
    item.translations?.term?.en,
    item.translations?.definition?.lt,
    item.translations?.definition?.en,
  ],
  defaultSort: 'priority:asc',
  sortOptions: () => [
    { value: 'priority:asc', label: $t('learning.documents.by_importance'), by: item => props.terms.findIndex(term => term.id === item.id) },
    { value: 'term:asc', label: $t('learning_library.library.sort.title_asc'), by: item => item.term },
    { value: 'term:desc', label: $t('learning_library.library.sort.title_desc'), by: item => item.term },
  ],
});

const columns = computed<CollectionColumn[]>(() => [
  { key: 'term', label: $t('learning_library.glossary.term'), class: 'w-56', sortField: 'term' },
  { key: 'definition', label: $t('learning_library.glossary.definition') },
  { key: 'stepsCount', label: $t('learning_library.glossary.items_count'), class: 'w-28' },
]);

const rowActions = (_item: GlossaryTermData): CollectionRowAction[] => {
  if (!canManageTerms.value) {
    return [];
  }
  return [
    { key: 'edit', role: 'edit' as const, label: $t('Redaguoti'), icon: Pencil },
    { key: 'delete', label: $t('Ištrinti'), icon: Trash2, destructive: true },
  ];
};

const sheetOpen = ref(false);
const editing = ref<GlossaryTermData | null>(null);
const toDelete = ref<GlossaryTermData | null>(null);

function openSheet(item: GlossaryTermData | null = null): void {
  editing.value = item;
  sheetOpen.value = true;
}

function selectRowAction(key: string, item: GlossaryTermData): void {
  if (key === 'edit') {
    openSheet(item);
  }
  else if (key === 'delete') {
    toDelete.value = item;
  }
}

function removeTerm(): void {
  if (!toDelete.value) {
    return;
  }
  const { id } = toDelete.value;
  toDelete.value = null;
  router.delete(route('glossaryTerms.destroy', id), { preserveScroll: true });
}
</script>
