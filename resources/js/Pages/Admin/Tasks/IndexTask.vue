<template>
  <CollectionPage
    :source
    :collection="`tasks-${scope}`"
    entity-type="task"
    :eyebrow
    :title="$t('tasks.collection.title')"
    :lead="scope === 'tenant' ? $t('tasks.collection.lead_tenant') : $t('tasks.collection.lead_mine')"
    default-view="preview"
    :available-views="['preview', 'table', 'rows']"
    :item-key="taskKey"
    :columns
    table-fixed
    :quick-filters
    :search-placeholder="$t('tasks.collection.search')"
    @quick-filter="toggleQuickFilter"
  >
    <template v-if="scope === 'mine' && canViewAllTasks" #actions>
      <Button as-child variant="outline" size="lg">
        <Link :href="route('tasks.summary')">
          {{ $t('tasks.collection.all_tasks') }}
          <ArrowRight aria-hidden="true" />
        </Link>
      </Button>
    </template>

    <template #row="{ item, view }">
      <TaskRow
        :task="item"
        :loading="loadingTaskId === item.id"
        :hide-actions="view === 'preview'"
        @open="detailDialogs.openTaskDetail(item)"
        @action="key => runAction(item, key)"
      />
    </template>

    <template #cell="{ item, column }">
      <TaskCompletionControl
        v-if="column.key === 'status'"
        :task="item"
        :loading="loadingTaskId === item.id"
        @toggle="toggleCompletion(item)"
      />

      <CollectionPrimaryCell
        v-else-if="column.key === 'name'"
        clickable
        :title-lines="2"
        :sub="item.description ?? undefined"
        @open="detailDialogs.openTaskDetail(item)"
      >
        <span :class="item.completed_at && 'line-through text-muted-foreground'">{{ item.name }}</span>
      </CollectionPrimaryCell>

      <template v-else-if="column.key === 'subject'">
        <span v-if="isOrphanedTask(item)" class="text-xs text-muted-foreground" :title="$t('tasks.orphaned_description')">
          {{ $t('tasks.orphaned') }}
        </span>
        <Link v-else-if="getTaskableUrl(item)" :href="getTaskableUrl(item)!" class="line-clamp-2 text-sm hover:text-brand">
          {{ subjectLabel(item) }}
        </Link>
        <span v-else class="line-clamp-2 text-sm">{{ subjectLabel(item) }}</span>
      </template>

      <UsersAvatarGroup v-else-if="column.key === 'users'" :users="item.users ?? []" :size="28" :max="3" />

      <span v-else-if="column.key === 'due_date'" :class="['text-sm tabular-nums', getDueDateUrgencyClasses(item)]">
        {{ item.due_date ? formatTaskDueDate(item.due_date, dateLocale) : '—' }}
      </span>

      <CollectionRowActions v-else-if="column.key === 'actions'" :actions="getTaskRowActions(item)" @select="key => runAction(item, key as TaskActionKey)" />
    </template>

    <template #preview="{ item }">
      <section class="flex flex-col gap-5 p-5">
        <div>
          <p class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
            {{ getTaskActionLabel(item.action_type) }}
          </p>
          <h2 class="mt-1.5 text-lg font-semibold leading-snug" :class="item.completed_at && 'line-through text-muted-foreground'">
            {{ item.name }}
          </h2>
          <Link
            v-if="getTaskableUrl(item)"
            :href="getTaskableUrl(item)!"
            class="mt-1 inline-block text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
          >
            {{ subjectLabel(item) }}
          </Link>
        </div>

        <TaskDetails :task="item" hide-actions />

        <div class="mt-auto flex flex-col gap-2 border-t border-border pt-4">
          <Button
            v-for="action in getTaskActions(item)"
            :key="action.key"
            :variant="action.primary ? 'brand' : 'outline'"
            size="sm"
            :as-child="Boolean(action.href)"
            :class="action.destructive && 'text-destructive hover:text-destructive'"
            @click="action.href ? undefined : runAction(item, action.key)"
          >
            <Link v-if="action.href" :href="action.href">
              <component :is="action.icon" aria-hidden="true" />
              {{ action.label }}
            </Link>
            <template v-else>
              <component :is="action.icon" aria-hidden="true" />
              {{ action.label }}
            </template>
          </Button>
        </div>
      </section>
    </template>

    <template #empty>
      <EmptyState
        :mode="isFiltered ? 'no-results' : 'empty'"
        :icon="TaskIcon"
        :title="$t('tasks.collection.empty_title')"
        :description="scope === 'tenant' ? $t('tasks.collection.empty_description_tenant') : $t('tasks.collection.empty_description_mine')"
      />
    </template>
  </CollectionPage>

  <ConfirmDialog
    :open="taskPendingDeletion !== null"
    :title="$t('tasks.delete_confirm_title')"
    :description="$t('tasks.delete_confirm_description', { name: taskPendingDeletion?.name ?? '' })"
    :confirm-label="$t('forms.delete')"
    destructive
    @update:open="!$event && (taskPendingDeletion = null)"
    @confirm="deleteTask"
  />

  <TaskDetailDialog
    v-if="detailDialogs.selectedDetailTask.value"
    :open="detailDialogs.showTaskDetail.value"
    :task="detailDialogs.selectedDetailTask.value"
    @close="detailDialogs.closeTaskDetail"
    @report="detailDialogs.reportFromDetail"
  />
</template>

<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowRight } from 'lucide-vue-next';
import { computed, defineAsyncComponent, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import type { CollectionColumn, CollectionQuickFilter } from '@/Components/Collection/types';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import { ConfirmDialog, EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { TaskIcon } from '@/Components/icons';
import { useDatabaseCollectionSource, type DatabaseFacetDefinition } from '@/Composables/useCollectionSource';
import { useDateLocale } from '@/Composables/useDateLocale';
import { useTaskActionDialogs } from '@/Composables/useTaskActionDialogs';
import {
  formatTaskDueDate,
  getDueDateUrgencyClasses,
  getTaskActionLabel,
  getTaskableUrl,
  isOrphanedTask,
  type TaskDisplayData,
} from '@/Composables/useTaskPresentation';
import { getTaskActions, getTaskRowActions, type TaskActionKey } from '@/Features/Admin/TaskManager/taskActions';
import TaskCompletionControl from '@/Features/Admin/TaskManager/TaskCompletionControl.vue';
import TaskDetails from '@/Features/Admin/TaskManager/TaskDetails.vue';
import TaskRow from '@/Features/Admin/TaskManager/TaskRow.vue';

const TaskDetailDialog = defineAsyncComponent(() => import('@/Features/Admin/TaskManager/TaskDetailDialog.vue'));

type Scope = 'mine' | 'tenant';

interface TaskCounts {
  pending: number;
  overdue: number;
  auto: number;
  assigned: number;
  completed: number;
}

const props = defineProps<{
  scope: Scope;
  data: TaskDisplayData[];
  meta: { total: number; per_page: number; current_page: number; last_page: number };
  /** The `?item=` task, resolved in scope even when it is not on the first page. */
  linkedTask?: TaskDisplayData | null;
  taskCounts: TaskCounts;
  canViewAllTasks: boolean;
  tenants: { id: number; shortname: string }[];
}>();

const dateLocale = useDateLocale();
const detailDialogs = useTaskActionDialogs();

const eyebrow = computed(() => props.scope === 'tenant'
  ? `${$t('shell.workspaces.atstovavimas.title')} · ${$t('tasks.collection.title')}`
  : `${$t('shell.workspaces.pradzia.title')} · ${$t('tasks.collection.title')}`);

const taskKey = (task: TaskDisplayData) => String(task.id);

// A linked task past the first page leads the list, so the preview can open on it.
const initialItems = computed(() => {
  const linked = props.linkedTask;
  if (!linked || props.data.some(task => task.id === linked.id)) {
    return props.data;
  }

  return [linked, ...props.data];
});

const facets = computed<DatabaseFacetDefinition[]>(() => [
  {
    field: 'completion',
    label: $t('tasks.collection.facet_completion'),
    single: true,
    values: [
      { value: 'pending', label: $t('tasks.collection.pending') },
      { value: 'completed', label: $t('tasks.collection.completed') },
      { value: 'all', label: $t('tasks.collection.all') },
    ],
  },
  {
    field: 'taskable_type',
    label: $t('tasks.collection.facet_type'),
    values: [
      { value: 'institution', label: $t('Institucijos') },
      { value: 'meeting', label: $t('Posėdžiai') },
      { value: 'reservation', label: $t('Rezervacijos') },
    ],
  },
  ...(props.scope === 'tenant' && props.tenants.length > 1
    ? [{
        field: 'tenant',
        label: $t('Padalinys'),
        values: props.tenants.map(tenant => ({ value: String(tenant.id), label: tenant.shortname })),
      }]
    : []),
  {
    field: 'overdue',
    label: $t('tasks.collection.facet_deadline'),
    single: true,
    values: [{ value: '1', label: $t('tasks.collection.overdue') }],
  },
  {
    field: 'auto',
    label: $t('tasks.collection.facet_completing'),
    single: true,
    values: [{ value: '1', label: $t('tasks.collection.automatic') }],
  },
  ...(props.scope === 'tenant'
    ? [{
        field: 'assigned',
        label: $t('tasks.collection.facet_assigned'),
        single: true,
        values: [{ value: 'me', label: $t('tasks.collection.assigned_to_me') }],
      }]
    : []),
]);

const source = useDatabaseCollectionSource<TaskDisplayData>({
  endpoint: route('api.v1.admin.tasks.index', { scope: props.scope }),
  initial: {
    items: initialItems.value,
    total: props.meta.total,
    perPage: props.meta.per_page,
    currentPage: props.meta.current_page,
    lastPage: props.meta.last_page,
  },
  defaultSort: 'due_date:asc',
  sortOptions: [
    { value: 'due_date:asc', label: $t('tasks.collection.sort_due') },
    { value: 'created_at:desc', label: $t('tasks.collection.sort_newest') },
  ],
  facets: facets.value,
});

// The action window reloads the page after it saves; refetch through the source so the list keeps
// the filters it shows rather than whatever URL Inertia reloaded.
watch(() => props.data, () => source.refresh());

const columns = computed<CollectionColumn[]>(() => [
  { key: 'status', label: '', class: 'w-12', pinned: true },
  { key: 'name', label: $t('tasks.collection.column_task'), pinned: true },
  { key: 'subject', label: $t('tasks.collection.column_subject'), class: 'w-44' },
  { key: 'users', label: $t('tasks.collection.column_assignees'), class: 'w-28' },
  { key: 'due_date', label: $t('tasks.collection.column_due'), class: 'w-32' },
  // Fits the labelled first action ("Darbotvarkė") beside one icon button.
  { key: 'actions', label: $t('tasks.collection.column_actions'), class: 'w-56 text-right' },
]);

// --- Quick filters carry their counts; they replace the old stat cards --------------------------

const withCount = (label: string, count: number) => (count > 0 ? `${label} · ${count}` : label);

const quickFilters = computed<CollectionQuickFilter[]>(() => {
  const filters = source.filters.value;

  return [
    { id: 'overdue', label: withCount($t('tasks.collection.overdue'), props.taskCounts.overdue), active: filters.overdue === '1' },
    ...(props.scope === 'tenant'
      ? [{ id: 'assigned', label: withCount($t('tasks.collection.assigned_to_me'), props.taskCounts.assigned), active: filters.assigned === 'me' }]
      : []),
    { id: 'auto', label: withCount($t('tasks.collection.automatic'), props.taskCounts.auto), active: filters.auto === '1' },
    { id: 'completed', label: withCount($t('tasks.collection.completed'), props.taskCounts.completed), active: filters.completion === 'completed' },
  ];
});

function toggleQuickFilter(id: string): void {
  const active = quickFilters.value.find(filter => filter.id === id)?.active ?? false;
  const values: Record<string, [string, string]> = {
    overdue: ['overdue', '1'],
    assigned: ['assigned', 'me'],
    auto: ['auto', '1'],
    completed: ['completion', 'completed'],
  };
  const [field, value] = values[id] ?? [];

  if (field) {
    source.setFilter(field, active ? undefined : value);
  }
}

const isFiltered = computed(() => source.query.value.trim() !== '' || source.activeFilterCount.value > 0);

// --- Actions ------------------------------------------------------------------------------------

const subjectLabel = (task: TaskDisplayData): string => (isOrphanedTask(task)
  ? $t('tasks.orphaned')
  : task.taskable?.name ?? task.taskable_type);

function runAction(task: TaskDisplayData, key: TaskActionKey): void {
  switch (key) {
    case 'report':
      detailDialogs.openReportWindow(task);
      break;
    case 'complete':
      toggleCompletion(task);
      break;
    case 'delete':
      taskPendingDeletion.value = task;
      break;
  }
}

const loadingTaskId = ref<string | null>(null);

/**
 * Optimistic: the row flips at once and stays in place, so unticking it again is the undo.
 * Only the counts reload; the list catches up on the next filter or refresh.
 */
function toggleCompletion(task: TaskDisplayData): void {
  if (loadingTaskId.value || task.can_be_manually_completed === false) {
    return;
  }

  const completed = !task.completed_at;
  const undo = source.patchItems([String(task.id)], { completed_at: completed ? new Date().toISOString() : null });
  loadingTaskId.value = task.id;

  router.post(route('tasks.updateCompletionStatus', task.id), { completed }, {
    only: ['taskCounts'],
    preserveScroll: true,
    preserveState: true,
    onError: () => {
      undo();
      toast.error($t('tasks.collection.completing_failed'));
    },
    onFinish: () => {
      loadingTaskId.value = null;
    },
  });
}

const taskPendingDeletion = ref<TaskDisplayData | null>(null);

function deleteTask(): void {
  const task = taskPendingDeletion.value;
  taskPendingDeletion.value = null;
  if (!task) {
    return;
  }

  const undo = source.hideItems([String(task.id)]);

  router.delete(route('tasks.destroy', task.id), {
    only: ['taskCounts'],
    preserveScroll: true,
    preserveState: true,
    onError: () => undo(),
  });
}
</script>
