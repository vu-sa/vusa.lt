<template>
  <CollectionPage
    :row-actions="item => actionsFor(item)"
    @row-action="(item, key) => actions.select(key, item.force_delete_blocked_reason)"
    :source
    collection="users"
    entity-type="user"
    :eyebrow="`${$t('shell.workspaces.organizacija.title')} · ${$t('shell.sections.nariai')}`"
    :title="$t('Nariai')"
    :lead="isTrash
      ? $t('Ištrinti nariai. Atkurk paskyrą, jei jos dar reikia.')
      : $t('users.index.lead')"
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

    <template #row="{ item, actions: recordActions }">
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
        <CollectionRowActions :spotlight="item === source?.items.value[0]" :actions="recordActions" @select="key => actions.select(key, item.force_delete_blocked_reason)" />
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
    </template>

    <template #preview="{ item, actions: recordActions }">
      <UserPreview :key="item.id" :user="userPreviewFromRecord(item, isTrash)">
        <template #media>
          <UserAvatar :user="item" :size="56" />
        </template>
        <template #actions>
          <CollectionRowActions :actions="recordActions" @select="key => actions.select(key, item.force_delete_blocked_reason)" />
        </template>
        <PreviewSection v-if="!isTrash && !noUnit(item)" :title="$t('Pareigos')">
          <MemberTermsSummary
            :duties="item.duties ?? []"
            :current-count="Number(item.current_duties_count ?? 0)"
            :total-count="Number(item.duties_count ?? 0)"
          />
        </PreviewSection>
      </UserPreview>
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
        :description="isTrash ? undefined : $t('users.index.empty_description')"
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
    :extra-payload="{ login_email: loginEmail, dutiable_email_ids: dutiableEmailIds }"
    :submit-disabled="mergeDetailsLoading"
    @close="mergeRecords = []"
    @merged="merged"
  >
    <template #details="{ targetId }">
      <UserMergeDetails
        v-model="loginEmail"
        v-model:dutiable-email-ids="dutiableEmailIds"
        :records="mergeRecords"
        :target-id
        @loading="mergeDetailsLoading = $event"
      />
    </template>
  </MergeRecordsDialog>
  <CollectionConfirmAction :dialog="actions.dialog.value" @confirm="actions.confirm" @cancel="actions.pending.value = null" />
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowUpRight, CircleSlash, Merge, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRowActions, { type CollectionRowAction } from '@/Components/Collection/CollectionRowActions.vue';
import type { CollectionColumn, CollectionQuickFilter } from '@/Components/Collection/types';
import { UserIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import MergeRecordsDialog, { type MergeRecord } from '@/Components/Merge/MergeRecordsDialog.vue';
import UserMergeDetails from '@/Components/Merge/UserMergeDetails.vue';
import { EmptyState, PreviewSection, StatusBadge } from '@/Components/Patterns';
import UserPreview from '@/Components/Users/UserPreview.vue';
import { userPreviewFromRecord } from '@/Components/Users/userPreview';
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
  can_update_identity: boolean;
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
const mergeRecords = ref<(MergeRecord & { email: string | null; can_update_identity: boolean })[]>([]);
const loginEmail = ref<string | null>(null);
const dutiableEmailIds = ref<string[]>([]);
const mergeDetailsLoading = ref(false);

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
    get label() { return $t('Narystė'); },
    single: true,
    values: [
      { value: 'current', get label() { return $t('Dabartiniai'); } },
      { value: 'former', get label() { return $t('Buvę'); } },
    ],
  }, ...(!isTrash
    ? [{
        field: 'duplicates',
        get label() { return $t('users.filters.duplicates_group'); },
        single: true,
        values: [{ value: 'possible', get label() { return $t('users.filters.possible'); } }],
      }]
    : []), {
    field: 'future_duty',
    get label() { return $t('users.filters.duty_timing'); },
    single: true,
    values: [{ value: 'scheduled', get label() { return $t('users.filters.scheduled'); } }],
  }],
  sortOptions: () => [
    { value: 'name:asc', label: $t('Pagal vardą (A–Z)') },
    { value: 'name:desc', label: $t('Pagal vardą (Z–A)') },
    { value: 'email:asc', label: $t('Pagal el. paštą (A–Z)') },
    { value: 'email:desc', label: $t('Pagal el. paštą (Z–A)') },
    { value: 'last_action:desc', label: $t('Neseniai prisijungę') },
    { value: 'last_action:asc', label: $t('Seniausiai prisijungę') },
    { value: 'created_at:desc', label: $t('Naujausi pirmiausia') },
    { value: 'created_at:asc', label: $t('Seniausi pirmiausia') },
  ],
  preserveUrlKeys: ['showDeleted'],
});

const actions = useCollectionRecordActions({
  source,
  routePrefix: 'users',
  canRestore: () => canCreate.value,
  canForceDelete: () => canForceDelete.value,
});
const actionsFor = (user: UserRow): CollectionRowAction[] => isTrash
  ? actions.rowActions(user, user.name, true)
  : [{ key: 'open', label: $t('Atidaryti'), role: 'open' as const, icon: ArrowUpRight, href: route('users.show', user.id) }];

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
  ...(!isTrash
    ? [{
        id: 'duplicates',
        label: $t('users.filters.duplicates'),
        active: duplicatesActive.value,
        spotlight: {
          id: 'user-duplicates-v1',
          title: $t('users.filters.duplicates'),
          description: $t('users.filters.duplicates_spotlight'),
        },
      }]
    : []),
]);

const duplicatesActive = computed(() => !isTrash && source.filters.value.duplicates === 'possible');

// Trash navigation remounts the page; remove the live-only filter restored from its URL.
if (isTrash && new URLSearchParams(window.location.search).has('duplicates')) {
  source.setFilter('duplicates', undefined);
}

function toggleQuickFilter(id: string): void {
  if (id === 'duplicates') {
    source.setFilter('duplicates', duplicatesActive.value ? undefined : 'possible');
    return;
  }
  source.setFilter('membership', source.filters.value.membership === id ? undefined : id);
}

const columns = computed<CollectionColumn[]>(() => [
  { key: 'name', label: $t('Vardas'), sortField: 'name' },
  { key: 'email', label: $t('El. paštas'), sortField: 'email' },
  { key: 'years', label: $t('Metai'), class: 'w-40' },
  { key: 'duties', label: $t('Pareigos'), class: 'w-44' },
]);

const userKey = (user: UserRow) => String(user.id);

const toMergeRecords = (users: UserRow[]) =>
  users.map(user => ({ id: user.id, label: user.name, context: user.email, email: user.email, can_update_identity: user.can_update_identity }));

function merged(): void {
  mergeRecords.value = [];
  mergeMode.value = false;
  source.refresh();
}
</script>
