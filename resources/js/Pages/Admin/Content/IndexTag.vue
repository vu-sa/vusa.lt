<template>
  <CollectionPage
    :source
    collection="tags"
    entity-type="tag"
    :eyebrow="`${$t('shell.workspaces.svetaine.title')} · ${$t('shell.sections.zymos')}`"
    :title="$t('Žymos')"
    :lead="isDeleted ? $t('Peržiūrėk ištrintas žymas arba atkurk jas.') : $t('Tvarkyk žymas, kurios susieja svetainės turinį.')"
    default-view="table"
    :item-key="tagKey"
    :trash="{ count: deletedCount ?? 0, active: isDeleted }"
    :columns
    :selectable="mergeMode"
    :search-placeholder="$t('Ieškoti žymų')"
  >
    <template #actions>
      <Button v-if="canMerge && !isDeleted" variant="outline" size="lg" :aria-pressed="mergeMode" @click="mergeMode = !mergeMode">
        <Merge aria-hidden="true" />
        {{ mergeMode ? $t('Atšaukti sujungimą') : $t('Sujungti žymas') }}
      </Button>
      <Button v-if="canCreate && !isDeleted" variant="brand" size="lg" @click="openSheet()">
        <Plus aria-hidden="true" />
        {{ $t('Nauja žyma') }}
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex min-h-16 items-center gap-3 px-4 py-4">
        <CollectionPrimaryCell class="flex-1" :title="title(item)" :clickable="!isDeleted" :sub="item.alias" mono @open="openSheet(item)" />
        <span v-if="item.is_topic" class="text-xs text-muted-foreground">{{ $t('Teminė') }}</span>
      </article>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell v-if="column.key === 'name'" :title="title(item)" :clickable="!isDeleted" @open="openSheet(item)" />
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
        <template v-if="isDeleted">
          <div class="flex flex-col gap-2 pt-2">
            <Button variant="outline" @click="restoreTag(item)">
              <RotateCcw aria-hidden="true" class="size-4" />
              {{ $t('Atkurti') }}
            </Button>
            <Button variant="ghost" class="text-destructive hover:text-destructive" @click="targetTagToForceDelete = item">
              <Trash2 aria-hidden="true" class="size-4" />
              {{ $t('Ištrinti visam laikui') }}
            </Button>
          </div>
        </template>
        <template v-else>
          <Button variant="brand" @click="openSheet(item)">{{ $t('Redaguoti') }}</Button>
          <Button v-if="canDelete" variant="ghost" @click="remove(item)">{{ $t('Ištrinti') }}</Button>
        </template>
      </section>
    </template>

    <template #bulk-actions="{ selected }">
      <Button variant="brand" size="sm" :disabled="selected.length < 2" @click="mergeRecords = toMergeRecords(selected)">
        <Merge aria-hidden="true" />
        {{ $t('Sujungti') }}
      </Button>
    </template>

    <template #empty>
      <EmptyState
        mode="empty"
        :icon="TagIcon"
        :title="isDeleted ? $t('Ištrintų žymų nėra') : $t('Žymų dar nėra')"
        :description="isDeleted ? $t('Šiukšliadėžėje nėra pašalintų žymų.') : $t('Sukurk žymą, kad panašų svetainės turinį būtų lengviau rasti.')"
        :action-label="canCreate && !isDeleted ? $t('Nauja žyma') : undefined"
        @action="openSheet()"
      />
    </template>
  </CollectionPage>

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
  <ConfirmDialog
    :open="targetTagToForceDelete !== null"
    :title="$t('Ištrinti žymą visam laikui?')"
    :description="$t('Šis veiksmas negrįžtamas. Žyma bus visiškai pašalinta.')"
    :confirm-label="$t('Ištrinti visam laikui')"
    destructive
    @update:open="!$event && (targetTagToForceDelete = null)"
    @confirm="forceDeleteTag"
  />
</template>

<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Merge, Plus, RotateCcw, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

import type { CollectionColumn } from '@/Components/Collection/types';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { ConfirmDialog, EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import MergeRecordsDialog, { type MergeRecord } from '@/Components/Merge/MergeRecordsDialog.vue';
import TagSheetForm from '@/Features/Admin/Tags/TagSheetForm.vue';
import { TagIcon } from '@/Components/icons';
import { useDatabaseCollectionSource } from '@/Composables/useCollectionSource';
import { useAdminNavigation } from '@/Composables/useAdminNavigation';
import { formatDate } from '@/Utils/dateTime';

const entityName = 'tag';

type Translation = { lt?: string; en?: string };
type Tag = App.Entities.Tag & { name: Translation; description?: Translation | null };

const props = defineProps<{
  tags: { data: Tag[]; meta: { total: number; per_page: number; current_page: number; last_page: number } };
  deletedCount: number;
  showDeleted?: boolean;
}>();

const isDeleted = computed(() => Boolean(props.showDeleted));
const { hasCollectionAction } = useAdminNavigation();
const canCreate = computed(() => Boolean(usePage().props.auth?.can?.create?.tag));
const canDelete = computed(() => Boolean(usePage().props.auth?.can?.delete?.tag));
const canMerge = computed(() => hasCollectionAction('tags.index', 'merge'));
const sheetOpen = ref(false);
const editingTag = ref<Tag | null>(null);
const mergeMode = ref(false);
const mergeRecords = ref<MergeRecord[]>([]);
const targetTagToForceDelete = ref<Tag | null>(null);

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
  { key: 'name', label: $t('Žyma') },
  { key: 'alias', label: $t('Alias'), class: 'w-48' },
  { key: 'topic', label: $t('Tema'), class: 'w-28' },
  { key: 'created', label: $t('Sukurta'), class: 'w-32' },
]);

const tagKey = (tag: Tag) => String(tag.id);
const title = (tag: Tag) => tag.name.lt || tag.name.en || '—';
const description = (tag: Tag) => tag.description?.lt || tag.description?.en || '';
const toMergeRecords = (tags: Tag[]): MergeRecord[] =>
  tags.map(tag => ({ id: tag.id, label: title(tag), context: tag.alias ?? undefined }));

function openSheet(tag: Tag | null = null): void {
  editingTag.value = tag;
  sheetOpen.value = true;
}

function refresh(): void {
  editingTag.value = null;
  source.refresh();
}

function merged(): void {
  mergeRecords.value = [];
  mergeMode.value = false;
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

function restoreTag(tag: Tag): void {
  router.patch(route('tags.restore', tag.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      source.refresh();
      toast.success($t('Žyma atkurta.'));
    },
    onError: () => {
      toast.error($t('Nepavyko atkurti žymos.'));
    },
  });
}

function forceDeleteTag(): void {
  if (!targetTagToForceDelete.value) return;
  const { id } = targetTagToForceDelete.value;
  targetTagToForceDelete.value = null;

  router.delete(route('tags.forceDelete', id), {
    preserveScroll: true,
    onSuccess: () => {
      source.refresh();
      toast.success($t('Žyma ištrinta visam laikui.'));
    },
    onError: () => {
      toast.error($t('Nepavyko ištrinti žymos.'));
    },
  });
}
</script>
