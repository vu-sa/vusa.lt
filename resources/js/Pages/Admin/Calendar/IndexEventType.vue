<template>
  <CollectionPage
    :source
    collection="eventTypes"
    entity-type="eventType"
    :eyebrow="isDeleted ? $t('Ištrinti renginių tipai') : ($t('shell.workspaces.kalendorius.title') + ' · ' + $t('Renginių tipai'))"
    :title="isDeleted ? $t('Ištrinti renginių tipai') : $t('Renginių tipai')"
    :lead="isDeleted ? $t('Peržiūrėk ištrintus renginių tipus arba atkurk juos.') : $t('Renginių tipai padeda grupuoti renginius pagal jų pobūdį.')"
    default-view="table"
    :item-key="eventTypeKey"
    :trash="{ count: deletedCount ?? 0, active: isDeleted }"
    :columns
    :search-placeholder="$t('Ieškoti renginių tipų…')"
  >
    <template #actions>
      <Button v-if="canCreate && !isDeleted" variant="brand" size="lg" @click="openSheet()">
        <Plus aria-hidden="true" />
        {{ $t('Naujas renginio tipas') }}
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex min-h-14 items-center justify-between gap-4 px-3 py-2.5 sm:px-4">
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="truncate text-left font-medium hover:text-brand"
              @click="openSheet(item)"
            >
              {{ title(item) }}
            </button>
            <span
              v-if="!item.is_active"
              class="border border-border bg-muted px-1.5 py-0.5 text-xs text-muted-foreground"
            >
              {{ $t('Neaktyvus') }}
            </span>
          </div>
          <p class="mt-0.5 truncate text-xs text-muted-foreground">
            {{ item.slug }}
          </p>
        </div>
        <div class="flex shrink-0 items-center gap-1">
          <Button
            v-if="!isDeleted"
            variant="outline"
            size="icon"

            :title="$t('Redaguoti')"
            :aria-label="$t('Redaguoti')"
            @click="openSheet(item)"
          >
            <Pencil aria-hidden="true" />
          </Button>
        </div>
      </article>
    </template>

    <template #cell="{ item, column }">
      <div v-if="column.key === 'name'" class="flex min-w-0 items-center gap-2">
        <button
          v-if="!isDeleted"
          type="button"
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

      <span v-else-if="column.key === 'is_active'">
        <span
          v-if="item.is_active"
          class="border border-status-success/20 bg-status-success/10 px-2 py-0.5 text-xs font-medium text-status-success"
        >
          {{ $t('Aktyvus') }}
        </span>
        <span
          v-else
          class="border border-border bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground"
        >
          {{ $t('Neaktyvus') }}
        </span>
      </span>

      <div v-else-if="column.key === 'actions'" class="flex justify-end gap-1">
        <template v-if="isDeleted">
          <Button
            v-if="canRestore"
            variant="outline"
            size="icon"

            :title="$t('Atkurti')"
            :aria-label="$t('Atkurti')"
            @click="restoreEventType(item)"
          >
            <RotateCcw aria-hidden="true" />
          </Button>
          <Button
            v-if="canForceDelete"
            variant="outline"
            size="icon"
            class="text-destructive hover:text-destructive pointer-coarse:size-11"
            :title="$t('Ištrinti visam laikui')"
            :aria-label="$t('Ištrinti visam laikui')"
            @click="targetToForceDelete = item"
          >
            <Trash2 aria-hidden="true" />
          </Button>
        </template>
        <template v-else>
          <Button
            v-if="canUpdate"
            variant="outline"
            size="icon"

            :title="$t('Redaguoti')"
            :aria-label="$t('Redaguoti')"
            @click="openSheet(item)"
          >
            <Pencil aria-hidden="true" />
          </Button>
          <Button
            v-if="canDelete"
            variant="outline"
            size="icon"
            class="text-destructive hover:text-destructive pointer-coarse:size-11"
            :title="$t('Ištrinti')"
            :aria-label="$t('Ištrinti')"
            @click="targetToDelete = item"
          >
            <Trash2 aria-hidden="true" />
          </Button>
        </template>
      </div>
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
import { Link, router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Pencil, Plus, RotateCcw, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import type { CollectionColumn } from '@/Components/Collection/types';
import { CalendarIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { ConfirmDialog, EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useDatabaseCollectionSource } from '@/Composables/useCollectionSource';
import EventTypeSheetForm from '@/Features/Admin/EventTypes/EventTypeSheetForm.vue';

const entityName = 'eventType';

type EventTypeRow = App.Entities.EventType & {
  name: { lt: string; en: string };
  slug: string;
  is_active: boolean | number;
  sort_order: number;
  force_delete_blocked_reason?: string | null;
};

const props = defineProps<{
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
const canUpdate = computed(() => Boolean(usePage().props.auth?.can?.update?.eventType));
const canDelete = computed(() => Boolean(usePage().props.auth?.can?.delete?.eventType));
const canRestore = computed(() => Boolean(usePage().props.auth?.can?.restore?.eventType));
const canForceDelete = computed(() => Boolean(usePage().props.auth?.can?.forceDelete?.eventType));

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
  sortOptions: [
    { value: 'sort_order:asc', label: $t('Pagal rikiavimo tvarką') },
    { value: 'name:asc', label: $t('Pagal pavadinimą (A–Z)') },
    { value: 'name:desc', label: $t('Pagal pavadinimą (Z–A)') },
  ],
  preserveUrlKeys: ['showDeleted'],
});

const columns = computed<CollectionColumn[]>(() => [
  { key: 'name', label: $t('forms.fields.title') },
  { key: 'slug', label: 'Slug', class: 'w-48' },
  { key: 'is_active', label: $t('forms.fields.is_active'), class: 'w-28' },
  { key: 'actions', label: '', class: 'w-28' },
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
