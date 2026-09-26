<template>
  <CollectionPage
    :source
    collection="resourceCategories"
    entity-type="resource"
    :eyebrow="`${$t('shell.workspaces.rezervacijos.title')} · ${$t('shell.sections.kategorijos')}`"
    :title="$t('Išteklių kategorijos')"
    :lead="$t('Kategorijos sugrupuoja išteklius, kad juos būtų lengviau rasti.')"
    default-view="table"
    :item-key="categoryKey"
    :columns
    :search-placeholder="$t('Ieškoti kategorijų')"
  >
    <template #actions>
      <Button v-if="canCreate" variant="brand" @click="openSheet()">
        <Plus aria-hidden="true" />
        {{ $t('Nauja kategorija') }}
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex min-h-16 items-center gap-3 px-3 py-3 sm:px-4">
        <!-- The icon name is stored as data, so it can only be resolved at runtime. -->
        <Icon v-if="item.icon" :icon="`fluent:${item.icon}`" class="size-5 shrink-0 text-muted-foreground" aria-hidden="true" />
        <div class="min-w-0 flex-1">
          <button type="button" class="block max-w-full text-left font-medium hover:text-brand" @click="openSheet(item)">
            {{ title(item) }}
          </button>
          <p v-if="description(item)" class="mt-0.5 truncate text-sm text-muted-foreground">
            {{ description(item) }}
          </p>
        </div>
      </article>
    </template>

    <template #cell="{ item, column }">
      <button v-if="column.key === 'name'" type="button" class="inline-flex items-center gap-2 font-medium hover:text-brand" @click="openSheet(item)">
        <Icon v-if="item.icon" :icon="`fluent:${item.icon}`" class="size-4 text-muted-foreground" aria-hidden="true" />
        {{ title(item) }}
      </button>
      <span v-else-if="column.key === 'description'" class="text-muted-foreground">{{ description(item) || '—' }}</span>
      <div v-else-if="column.key === 'actions'" class="flex justify-end gap-1">
        <Button variant="ghost" size="icon-sm" class="pointer-coarse:size-11" :title="$t('Redaguoti')" :aria-label="$t('Redaguoti')" @click="openSheet(item)">
          <Pencil aria-hidden="true" />
        </Button>
        <Button
          v-if="canDelete"
          variant="ghost"
          size="icon-sm"
          class="text-destructive hover:text-destructive pointer-coarse:size-11"
          :title="$t('Ištrinti')"
          :aria-label="$t('Ištrinti')"
          @click="toDelete = item"
        >
          <Trash2 aria-hidden="true" />
        </Button>
      </div>
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
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import type { CollectionColumn } from '@/Components/Collection/types';
import { CategoryIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { ConfirmDialog, EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useDatabaseCollectionSource } from '@/Composables/useCollectionSource';
import ResourceCategorySheetForm from '@/Features/Admin/ResourceCategories/ResourceCategorySheetForm.vue';

interface Translation { lt?: string; en?: string }
type Category = App.Entities.ResourceCategory & { name: Translation; description?: Translation | null; icon?: string | null };

const props = defineProps<{
  resourceCategories: { data: Category[]; meta: { total: number; per_page: number; current_page: number; last_page: number } };
}>();

const canCreate = computed(() => Boolean(usePage().props.auth?.can?.create?.resource));
const canDelete = computed(() => Boolean(usePage().props.auth?.can?.delete?.resource));

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
  defaultSort: 'created_at:desc',
  sortOptions: [
    { value: 'created_at:desc', label: $t('Naujausios pirmiausia') },
    { value: 'created_at:asc', label: $t('Seniausios pirmiausia') },
  ],
});

const columns = computed<CollectionColumn[]>(() => [
  { key: 'name', label: $t('Kategorija') },
  { key: 'description', label: $t('Aprašymas') },
  { key: 'actions', label: '', class: 'w-28' },
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
