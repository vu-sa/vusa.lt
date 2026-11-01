<template>
  <CollectionPage
    :row-actions="() => rowActions"
    :source
    collection="eventTypes"
    :eyebrow="isDeleted ? $t('Ištrinti renginių tipai') : ($t('shell.workspaces.kalendorius.title'))"
    :title="isDeleted ? $t('Ištrinti renginių tipai') : $t('Renginių tipai')"
    :lead="isDeleted ? $t('Peržiūrėk ištrintus renginių tipus arba atkurk juos.') : $t('Renginių tipai padeda grupuoti renginius pagal jų pobūdį.')"
    default-view="table"
    :item-key="eventTypeKey"
    :trash="{ count: deletedCount ?? 0, active: isDeleted }"
    :columns
    :row-muted="item => !item.is_active"
    table-fixed
    actions-width="w-44"
    table-min-width="min-w-[42rem]"
    @row-action="(item, key) => selectRowAction(key, item)"
    :search-placeholder="$t('Ieškoti renginių tipų…')"
  >
    <template #actions>
      <Button v-if="canCreate && !isDeleted" variant="brand" size="lg" @click="openSheet()">
        <Plus aria-hidden="true" />
        {{ $t('Naujas renginio tipas') }}
      </Button>
    </template>

    <template #row="{ item, actions: recordActions }">
      <CollectionRow :actions="recordActions" :spotlight="item === source?.items.value[0]" class="px-3 py-2.5 sm:px-4" @select="key => selectRowAction(key, item)">
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-2">
            <component :is="operatingStatuses.inactive.icon" v-if="!item.is_active" class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
        <span v-if="!item.is_active" class="sr-only">{{ $t('Neaktyvus') }}:</span>
            <button
              v-if="canUpdate && !isDeleted"
              type="button"
              data-collection-open
              class="truncate text-left font-medium hover:text-brand"
              @click="openSheet(item)"
            >
              {{ title(item) }}
            </button>
            <span v-else class="truncate font-medium">{{ title(item) }}</span>
          </div>
          <p class="mt-0.5 truncate text-xs text-muted-foreground">
            {{ item.slug }}
          </p>
        </div>
      </CollectionRow>
    </template>

    <template #cell="{ item, column }">
      <div v-if="column.key === 'name'" class="flex min-w-0 items-center gap-2">
        <component :is="operatingStatuses.inactive.icon" v-if="!item.is_active" class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
        <span v-if="!item.is_active" class="sr-only">{{ $t('Neaktyvus') }}:</span>
        <button
          v-if="canUpdate && !isDeleted"
          type="button"
          data-collection-open
          class="block truncate text-left font-medium hover:text-brand"
          @click="openSheet(item)"
        >
          {{ title(item) }}
        </button>
        <span v-else class="block truncate font-medium text-muted-foreground">
          {{ title(item) }}
        </span>
      </div>

      <span v-else-if="column.key === 'slug'" class="text-sm font-mono text-muted-foreground">
        {{ item.slug }}
      </span>

      <span v-else-if="column.key === 'created_at' || column.key === 'updated_at'" class="tabular-nums">{{ formatRecordDate(item[column.key]) }}</span>
    </template>

    <template #empty>
      <EmptyState
        mode="empty"
        :icon="CalendarIcon"
        :title="isDeleted ? $t('Ištrintų renginių tipų nėra') : $t('Renginių tipų dar nėra')"
        :description="isDeleted ? $t('Šiukšliadėžėje nėra pašalintų renginių tipų.') : $t('Sukurk renginio tipą renginiams grupuoti.')"
        :action-label="canCreate && !isDeleted ? $t('Naujas renginio tipas') : undefined"
        @action="openSheet()"
      />
    </template>
  </CollectionPage>

  <EventTypeSheetForm
    v-model:open="sheetOpen"
    :event-type="editing"
    @saved="refresh"
  />

  <ConfirmDialog
    :open="targetToDelete !== null"
    :title="$t('Ištrinti renginio tipą?')"
    :description="$t('Renginio tipas bus perkeltas į šiukšliadėžę.')"
    :confirm-label="$t('Ištrinti')"
    destructive
    @update:open="!$event && (targetToDelete = null)"
    @confirm="deleteEventType"
  />

  <ConfirmDialog
    :open="targetToForceDelete !== null"
    :title="$t('Ištrinti renginio tipą visam laikui?')"
    :description="targetToForceDelete?.force_delete_blocked_reason ?? $t('Šis veiksmas negrįžtamas. Renginio tipas bus visiškai pašalintas.')"
    :confirm-label="$t('Ištrinti visam laikui')"
    :confirm-disabled="Boolean(targetToForceDelete?.force_delete_blocked_reason)"
    destructive
    @update:open="!$event && (targetToForceDelete = null)"
    @confirm="forceDeleteEventType"
  />
</template>

<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Pencil, Plus, RotateCcw, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';

import type { CollectionColumn } from '@/Components/Collection/types';
import type { CollectionRowAction } from '@/Components/Collection/CollectionRowActions.vue';
import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import { CalendarIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { ConfirmDialog, EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { operatingStatuses } from '@/Constants/statuses';
import { useDatabaseCollectionSource } from '@/Composables/useCollectionSource';
import EventTypeSheetForm from '@/Features/Admin/EventTypes/EventTypeSheetForm.vue';
import { formatRecordDate } from '@/Utils/dateTime';

type EventTypeRow = App.Entities.EventType & {
  name: { lt: string; en: string };
  slug: string;
  is_active: boolean | number;
  sort_order: number;
  force_delete_blocked_reason?: string | null;
};

const props = defineProps<{
  abilities: { update: boolean; delete: boolean; restore: boolean };
  eventTypes: {
    data: EventTypeRow[];
    meta: {
      total: number;
      current_page: number;
      per_page: number;
      last_page: number;
      from: number;
      to: number;
    };
  };
  filters?: Record<string, unknown>;
  sorting?: { id: string; desc: boolean }[];
  showDeleted?: boolean;
  deletedCount?: number;
}>();

const isDeleted = computed(() => Boolean(props.showDeleted));
const deletedCount = computed(() => props.deletedCount ?? 0);

const canCreate = computed(() => Boolean(usePage().props.auth?.can?.create?.eventType));
const canUpdate = computed(() => props.abilities.update);
const canDelete = computed(() => props.abilities.delete);
const canRestore = computed(() => props.abilities.restore);
const canForceDelete = computed(() => Boolean(usePage().props.auth?.can?.forceDelete?.eventType));
const rowActions = computed<CollectionRowAction[]>(() => isDeleted.value
  ? [
      ...(canRestore.value ? [{ key: 'restore', label: $t('Atkurti'), icon: RotateCcw, primary: !canForceDelete.value }] : []),
      ...(canForceDelete.value ? [{ key: 'forceDelete', label: $t('Ištrinti visam laikui'), icon: Trash2, destructive: true }] : []),
    ]
  : [
      ...(canUpdate.value ? [{ key: 'edit', role: 'edit' as const, label: $t('Redaguoti'), icon: Pencil }] : []),
      ...(canDelete.value ? [{ key: 'delete', label: $t('Ištrinti'), icon: Trash2, destructive: true }] : []),
    ]);

function selectRowAction(key: string, item: EventTypeRow): void {
  if (key === 'edit') openSheet(item);
  if (key === 'delete') targetToDelete.value = item;
  if (key === 'restore') restoreEventType(item);
  if (key === 'forceDelete') targetToForceDelete.value = item;
}

const sheetOpen = ref(false);
const editing = ref<EventTypeRow | null>(null);
const targetToDelete = ref<EventTypeRow | null>(null);
const targetToForceDelete = ref<EventTypeRow | null>(null);

const eventTypeKey = (item: EventTypeRow) => String(item.id);

const title = (item: EventTypeRow) => {
  if (typeof item.name === 'object' && item.name !== null) {
    return item.name.lt || item.name.en || '—';
  }
  return String(item.name ?? '—');
};

const source = useDatabaseCollectionSource<EventTypeRow>({
  endpoint: route('api.v1.admin.eventTypes.index'),
  initial: {
    items: props.eventTypes.data,
    total: props.eventTypes.meta.total,
    perPage: props.eventTypes.meta.per_page,
    currentPage: props.eventTypes.meta.current_page,
    lastPage: props.eventTypes.meta.last_page,
  },
  defaultSort: 'sort_order:asc',
  sortOptions: () => [
    { value: 'updated_at:desc', label: `${$t('Atnaujinta')} ↓` },
    { value: 'updated_at:asc', label: `${$t('Atnaujinta')} ↑` },
    { value: 'created_at:desc', label: `${$t('Sukurta')} ↓` },
    { value: 'created_at:asc', label: `${$t('Sukurta')} ↑` },
    { value: 'sort_order:asc', label: $t('Pagal rikiavimo tvarką') },
    { value: 'sort_order:desc', label: $t('Pagal rikiavimo tvarką (atvirkščiai)') },
    { value: 'name:asc', label: $t('Pagal pavadinimą (A–Z)') },
    { value: 'name:desc', label: $t('Pagal pavadinimą (Z–A)') },
  ],
  facets: [{ field: 'is_active', label: $t('Būsena'), single: true, values: [
    { value: '1', label: $t('Aktyvus') },
    { value: '0', label: $t('Neaktyvus') },
  ] }],
  preserveUrlKeys: ['showDeleted'],
});

const columns = computed<CollectionColumn[]>(() => [
  { key: 'name', label: $t('forms.fields.title'), sortField: 'name' },
  { key: 'slug', label: 'Slug', class: 'w-48' },
  { key: 'created_at', label: $t('Sukurta'), class: 'w-36', sortField: 'created_at', defaultHidden: true },
  { key: 'updated_at', label: $t('Atnaujinta'), class: 'w-36', sortField: 'updated_at', defaultHidden: true },
]);

function openSheet(item: EventTypeRow | null = null): void {
  editing.value = item;
  sheetOpen.value = true;
}

function refresh(): void {
  editing.value = null;
  source.refresh();
}

function deleteEventType(): void {
  if (!targetToDelete.value) return;

  const { id } = targetToDelete.value;
  targetToDelete.value = null;

  router.delete(route('eventTypes.destroy', id), {
    preserveScroll: true,
    onSuccess: () => source.refresh(),
  });
}

function restoreEventType(item: EventTypeRow): void {
  router.patch(route('eventTypes.restore', item.id), {}, {
    preserveScroll: true,
    onSuccess: () => source.refresh(),
  });
}

function forceDeleteEventType(): void {
  if (!targetToForceDelete.value) return;

  const { id } = targetToForceDelete.value;
  targetToForceDelete.value = null;

  router.delete(route('eventTypes.forceDelete', id), {
    preserveScroll: true,
    onSuccess: () => source.refresh(),
  });
}
</script>
