<template>
  <CollectionPage
    :source
    collection="users"
    entity-type="user"
    :eyebrow="`${$t('shell.workspaces.organizacija.title')} · ${$t('shell.sections.nariai')}`"
    :title="$t('Nariai')"
    :lead="isTrash
      ? $t('Ištrinti nariai. Atkurk paskyrą, jei jos dar reikia.')
      : $t('Dabartiniai ir buvę nariai, turintys pareigybių ar roles VU SA sistemoje.')"
    default-view="table"
    :item-key="userKey"
    :columns
    :selectable="mergeMode"
    :trash="{ count: deletedCount, active: isTrash }"
    :search-placeholder="$t('Ieškoti narių')"
    :quick-filters
    @quick-filter="toggleQuickFilter"
  >
    <template #actions>
      <Button v-if="canMerge && !isTrash" variant="outline" size="lg" :aria-pressed="mergeMode" @click="mergeMode = !mergeMode">
        <Merge aria-hidden="true" />
        {{ mergeMode ? $t('Atšaukti sujungimą') : $t('Sujungti narius') }}
      </Button>
      <Button v-if="canCreate && !isTrash" as-child variant="brand" size="lg">
        <Link :href="route('users.create')">
          <Plus aria-hidden="true" />
          {{ $t('Naujas narys (-ė)') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center">
        <div class="flex min-w-0 flex-1 items-center gap-3">
          <UserAvatar :user="item" :size="40" class="shrink-0" />
          <CollectionPrimaryCell :title="item.name" :href="isTrash ? undefined : route('users.show', item.id)" :sub="item.email ?? undefined" />
          <StatusBadge v-if="!isTrash && noUnit(item)" :status="withoutUnit" class="shrink-0" />
          <span v-else-if="!isTrash && yearSpan(item)" class="shrink-0 text-sm tabular-nums text-muted-foreground">{{ yearSpan(item) }}</span>
        </div>
        <MemberTermsSummary
          v-if="!isTrash && !noUnit(item)"
          :duties="item.duties ?? []"
          :current-count="Number(item.current_duties_count ?? 0)"
          :total-count="Number(item.duties_count ?? 0)"
          class="self-start sm:self-center"
        />
        <CollectionRowActions v-if="isTrash" :actions="actionsFor(item)" @select="key => actions.select(key, item.force_delete_blocked_reason)" />
      </article>
    </template>

    <template #cell="{ item, column }">
      <div v-if="column.key === 'name'" class="flex items-center gap-3">
        <UserAvatar :user="item" :size="32" class="shrink-0" />
        <CollectionPrimaryCell :title="item.name" :href="isTrash ? undefined : route('users.show', item.id)" />
      </div>
      <a v-else-if="column.key === 'email' && item.email" :href="`mailto:${item.email}`" class="text-muted-foreground hover:text-brand">{{ item.email }}</a>
      <template v-else-if="column.key === 'years'">
        <StatusBadge v-if="noUnit(item)" :status="withoutUnit" />
        <span v-else :class="['tabular-nums', isFormer(item) ? 'text-muted-foreground' : 'text-foreground']">{{ yearSpan(item) ?? '—' }}</span>
      </template>
      <template v-else-if="column.key === 'duties'">
        <MemberTermsSummary
          v-if="!noUnit(item)"
          :duties="item.duties ?? []"
          :current-count="Number(item.current_duties_count ?? 0)"
          :total-count="Number(item.duties_count ?? 0)"
        />
        <span v-else class="text-muted-foreground">—</span>
      </template>
      <CollectionRowActions
        v-else-if="column.key === 'actions'"
        :actions="actionsFor(item)"
        @select="key => actions.select(key, item.force_delete_blocked_reason)"
      />
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
        :icon="UserIcon"
        :title="isTrash ? $t('Ištrintų narių nėra') : $t('Narių dar nėra')"
        :description="isTrash ? undefined : $t('Čia atsiras žmonės, turintys pareigybių ar roles.')"
        :action-label="canCreate && !isTrash ? $t('Naujas narys (-ė)') : undefined"
        :action-href="canCreate && !isTrash ? route('users.create') : undefined"
      />
    </template>
  </CollectionPage>

  <MergeRecordsDialog
    :open="mergeRecords.length > 0"
    type="users"
    :records="mergeRecords"
    :submit-url="route('users.mergeUsers')"
    target-field="kept_user_id"
    source-field="source_user_ids"
    :extra-payload="{ login_email: loginEmail }"
    @close="mergeRecords = []"
    @merged="merged"
  >
    <template #details="{ targetId }">
      <UserMergeDetails v-model="loginEmail" :records="mergeRecords" :target-id />
    </template>
  </MergeRecordsDialog>
  <CollectionConfirmAction :dialog="actions.dialog.value" @confirm="actions.confirm" @cancel="actions.pending.value = null" />
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { CircleSlash, Merge, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import type { CollectionColumn, CollectionQuickFilter } from '@/Components/Collection/types';
import { UserIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import MergeRecordsDialog, { type MergeRecord } from '@/Components/Merge/MergeRecordsDialog.vue';
import UserMergeDetails from '@/Components/Merge/UserMergeDetails.vue';
import { EmptyState, StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useAdminNavigation } from '@/Composables/useAdminNavigation';
import { useCollectionRecordActions } from '@/Composables/useCollectionRecordActions';
import { isTrashView, useDatabaseCollectionSource } from '@/Composables/useCollectionSource';
import type { StatusPresentation } from '@/Constants/statuses';
import { MemberTermsSummary, termYearSpan } from '@/Features/Admin/Occupancy';

type UserRow = App.Entities.User & {
  duties_count?: number;
  current_duties_count?: number;
  is_current?: boolean;
  last_term_end?: string | null;
  force_delete_blocked_reason?: string | null;
};

const props = defineProps<{
  users: { data: UserRow[]; meta: { total: number; per_page: number; current_page: number; last_page: number } };
  deletedCount: number;
}>();

const page = usePage();
const isTrash = isTrashView();
const { hasCollectionAction } = useAdminNavigation();
const canCreate = computed(() => Boolean(page.props.auth?.can?.create?.user));
const canForceDelete = computed(() => Boolean(page.props.auth?.can?.forceDelete?.user));
const canMerge = computed(() => hasCollectionAction('users.index', 'merge'));

const mergeMode = ref(false);
const mergeRecords = ref<(MergeRecord & { email: string })[]>([]);
const loginEmail = ref<string | null>(null);

const source = useDatabaseCollectionSource<UserRow>({
  endpoint: route('api.v1.admin.users.index'),
  initial: {
    items: props.users.data,
    total: props.users.meta.total,
    perPage: props.users.meta.per_page,
    currentPage: props.users.meta.current_page,
    lastPage: props.users.meta.last_page,
  },
  defaultSort: 'name:asc',
  facets: [{
    field: 'membership',
    label: $t('Narystė'),
    single: true,
    values: [
      { value: 'current', label: $t('Dabartiniai') },
      { value: 'former', label: $t('Buvę') },
    ],
  }, {
    field: 'future_duty',
    label: $t('users.filters.duty_timing'),
    single: true,
    values: [{ value: 'scheduled', label: $t('users.filters.scheduled') }],
  }],
  sortOptions: () => [
    { value: 'name:asc', label: $t('Pagal vardą (A–Z)') },
    { value: 'name:desc', label: $t('Pagal vardą (Z–A)') },
    { value: 'last_action:desc', label: $t('Neseniai prisijungę') },
    { value: 'created_at:desc', label: $t('Naujausi pirmiausia') },
  ],
  preserveUrlKeys: ['showDeleted'],
});

// Live members are edited on their record page; the list only acts on the trash.
const actions = useCollectionRecordActions({
  routePrefix: 'users',
  canRestore: () => canCreate.value,
  canForceDelete: () => canForceDelete.value,
});
const actionsFor = (user: UserRow) => actions.rowActions(user, user.name, true);

/** A member with no duties belongs to no unit, so no other tenant admin sees them until one is assigned. */
const withoutUnit: StatusPresentation = { label: 'Be padalinio', role: 'attention', icon: CircleSlash };
const noUnit = (user: UserRow) => Number(user.duties_count ?? 0) === 0;

/** Someone with a duty history and nothing in force today; role-only accounts are neither. */
const isFormer = (user: UserRow) => !noUnit(user) && user.is_current === false;
const yearSpan = (user: UserRow) => termYearSpan(
  (user.duties ?? []).map(duty => (duty as { pivot?: { start_date?: string | null; end_date?: string | null } }).pivot ?? {}),
  $t('dabar'),
);

const quickFilters = computed<CollectionQuickFilter[]>(() => [
  { id: 'current', label: $t('Dabartiniai'), active: source.filters.value.membership === 'current' },
  { id: 'former', label: $t('Buvę'), active: source.filters.value.membership === 'former' },
]);

function toggleQuickFilter(id: string): void {
  source.setFilter('membership', source.filters.value.membership === id ? undefined : id);
}

const columns = computed<CollectionColumn[]>(() => [
  { key: 'name', label: $t('Vardas'), sortField: 'name' },
  { key: 'email', label: $t('El. paštas') },
  { key: 'years', label: $t('Metai'), class: 'w-40' },
  { key: 'duties', label: $t('Pareigos'), class: 'w-44' },
  ...(isTrash ? [{ key: 'actions', label: $t('Veiksmai'), class: 'w-px text-right', pinned: true }] : []),
]);

const userKey = (user: UserRow) => String(user.id);

const toMergeRecords = (users: UserRow[]) =>
  users.map(user => ({ id: user.id, label: user.name, context: user.email, email: user.email }));

function merged(): void {
  mergeRecords.value = [];
  mergeMode.value = false;
  source.refresh();
}
</script>
