<template>
  <CollectionPage
    :source
    collection="tags"
    entity-type="tag"
    :eyebrow="$t('shell.workspaces.website.title') + ' · ' + $t('shell.sections.tags')"
    :title="$t('Žymos')"
    :lead="$t('Tvarkyk žymas, kurios susieja svetainės turinį.')"
    default-view="table"
    :item-key="tagKey"
    :columns
    :search-placeholder="$t('Ieškoti žymų')"
  >
    <template #actions>
      <Button v-if="deletedCount > 0" as-child variant="ghost">
        <Link :href="route('tags.index', { showDeleted: 'true' })">
          <Trash2 aria-hidden="true" />
          {{ $t('Ištrinti') }} ({{ deletedCount }})
        </Link>
      </Button>
      <Button v-if="canMerge" variant="ghost" @click="mergeMode = true">
        <Merge aria-hidden="true" />
        {{ $t('Sujungti') }}
      </Button>
      <Button v-if="canCreate" variant="brand" @click="openSheet()">
        <Plus aria-hidden="true" />
        {{ $t('Nauja žyma') }}
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex min-h-16 items-center gap-3 px-3 py-3 sm:px-4">
        <Checkbox v-if="mergeMode" :model-value="selectedIds.includes(tagKey(item))" @update:model-value="toggle(item)" />
        <div class="min-w-0 flex-1">
          <button type="button" class="block max-w-full text-left font-medium hover:text-brand" @click="openSheet(item)">
            {{ title(item) }}
          </button>
          <p v-if="item.alias" class="mt-0.5 truncate text-sm text-muted-foreground">{{ item.alias }}</p>
        </div>
        <span v-if="item.is_topic" class="text-xs text-muted-foreground">{{ $t('Teminė') }}</span>
      </article>
    </template>

    <template #cell="{ item, column }">
      <Checkbox v-if="column.key === 'select'" :model-value="selectedIds.includes(tagKey(item))" @update:model-value="toggle(item)" />
      <button v-else-if="column.key === 'name'" type="button" class="font-medium hover:text-brand" @click="openSheet(item)">
        {{ title(item) }}
      </button>
      <span v-else-if="column.key === 'alias'">{{ item.alias || '—' }}</span>
      <span v-else-if="column.key === 'topic'">{{ item.is_topic ? $t('Taip') : '—' }}</span>
      <span v-else-if="column.key === 'created'" class="tabular-nums">{{ formatDate(new Date(item.created_at)) }}</span>
    </template>

    <template #preview="{ item }">
      <section class="flex flex-col gap-4 p-5">
        <div>
          <p class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">{{ $t('Žyma') }}</p>
          <h2 class="mt-1 text-lg font-semibold">{{ title(item) }}</h2>
          <p v-if="item.alias" class="mt-1 text-sm text-muted-foreground">{{ item.alias }}</p>
        </div>
        <p v-if="description(item)" class="border-y border-border py-4 text-sm text-muted-foreground">{{ description(item) }}</p>
        <Button variant="brand" @click="openSheet(item)">{{ $t('Redaguoti') }}</Button>
        <Button v-if="canDelete" variant="ghost" @click="remove(item)">{{ $t('Ištrinti') }}</Button>
      </section>
    </template>

    <template #empty>
      <EmptyState
        mode="empty"
        :icon="TagIcon"
        :title="$t('Žymų dar nėra')"
        :description="$t('Sukurk žymą, kad panašų svetainės turinį būtų lengviau rasti.')"
        :action-label="canCreate ? $t('Nauja žyma') : undefined"
        @action="openSheet()"
      />
    </template>
  </CollectionPage>

  <CollectionSelectionBar
    v-if="mergeMode"
    :count="selectedIds.length"
    :count-label="$t('Pasirinkta')"
    @clear="leaveMerge"
  >
    <Button variant="brand" size="sm" :disabled="selectedIds.length < 2" @click="mergeRecords = selectedTags">
      <Merge aria-hidden="true" />
      {{ $t('Sujungti') }}
    </Button>
  </CollectionSelectionBar>

  <TagSheetForm v-model:open="sheetOpen" :tag="editingTag" @saved="refresh" />
  <MergeRecordsDialog
    :open="mergeRecords.length > 0"
    type="tags"
    :records="mergeRecords"
    :submit-url="route('tags.processMerge')"
    target-field="target_tag_id"
    source-field="source_tag_ids"
    @close="mergeRecords = []"
    @merged="merged"
  />
</template>

<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Merge, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

import type { CollectionColumn } from '@/Components/Collection/types';
import CollectionSelectionBar from '@/Components/Collection/CollectionSelectionBar.vue';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import MergeRecordsDialog, { type MergeRecord } from '@/Components/Merge/MergeRecordsDialog.vue';
import TagSheetForm from '@/Features/Admin/Tags/TagSheetForm.vue';
import { TagIcon } from '@/Components/icons';
import { useDatabaseCollectionSource } from '@/Composables/useCollectionSource';
import { useAdminNavigation } from '@/Composables/useAdminNavigation';
import { formatDate } from '@/Utils/dateTime';

type Translation = { lt?: string; en?: string };
type Tag = App.Entities.Tag & { name: Translation; description?: Translation | null };

const props = defineProps<{
  tags: { data: Tag[]; meta: { total: number; per_page: number; current_page: number; last_page: number } };
  deletedCount: number;
}>();

const { hasCollectionAction } = useAdminNavigation();
const canCreate = computed(() => Boolean(usePage().props.auth?.can?.create?.tag));
const canDelete = computed(() => Boolean(usePage().props.auth?.can?.delete?.tag));
const canMerge = computed(() => hasCollectionAction('tags.index', 'merge'));
const sheetOpen = ref(false);
const editingTag = ref<Tag | null>(null);
const mergeMode = ref(false);
const selectedIds = ref<string[]>([]);
const mergeRecords = ref<MergeRecord[]>([]);

const source = useDatabaseCollectionSource<Tag>({
  endpoint: route('api.v1.admin.tags.index'),
  initial: {
    items: props.tags.data,
    total: props.tags.meta.total,
    perPage: props.tags.meta.per_page,
    currentPage: props.tags.meta.current_page,
    lastPage: props.tags.meta.last_page,
  },
  defaultSort: 'created_at:desc',
  sortOptions: [
    { value: 'created_at:desc', label: $t('Naujausios pirmiausia') },
    { value: 'created_at:asc', label: $t('Seniausios pirmiausia') },
    { value: 'alias:asc', label: $t('Pagal alias') },
  ],
  preserveUrlKeys: ['showDeleted'],
});

const columns = computed<CollectionColumn[]>(() => [
  ...(mergeMode.value ? [{ key: 'select', label: $t('Pasirinkti'), class: 'w-12' }] : []),
  { key: 'name', label: $t('Žyma') },
  { key: 'alias', label: $t('Alias'), class: 'w-48' },
  { key: 'topic', label: $t('Tema'), class: 'w-28' },
  { key: 'created', label: $t('Sukurta'), class: 'w-32' },
]);

const tagKey = (tag: Tag) => String(tag.id);
const title = (tag: Tag) => tag.name.lt || tag.name.en || '—';
const description = (tag: Tag) => tag.description?.lt || tag.description?.en || '';
const selectedTags = computed<MergeRecord[]>(() => source.items.value
  .filter(tag => selectedIds.value.includes(tagKey(tag)))
  .map(tag => ({ id: tag.id, label: title(tag), context: tag.alias ?? undefined })));

function openSheet(tag: Tag | null = null): void {
  editingTag.value = tag;
  sheetOpen.value = true;
}

function refresh(): void {
  editingTag.value = null;
  source.refresh();
}

function toggle(tag: Tag): void {
  const id = tagKey(tag);
  selectedIds.value = selectedIds.value.includes(id)
    ? selectedIds.value.filter(selected => selected !== id)
    : [...selectedIds.value, id];
}

function leaveMerge(): void {
  mergeMode.value = false;
  selectedIds.value = [];
}

function merged(): void {
  mergeRecords.value = [];
  leaveMerge();
  source.refresh();
}

function remove(tag: Tag): void {
  const before = [...source.items.value];
  source.replaceItems(source.items.value.filter(item => item.id !== tag.id));
  router.delete(route('tags.destroy', tag.id), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success($t('Žyma ištrinta.'), {
        action: {
          label: $t('Atšaukti'),
          onClick: () => router.patch(route('tags.restore', tag.id), {}, { preserveScroll: true, onSuccess: () => source.refresh() }),
        },
      });
    },
    onError: () => {
      source.replaceItems(before);
      toast.error($t('Nepavyko ištrinti žymos.'));
    },
  });
}
</script>
