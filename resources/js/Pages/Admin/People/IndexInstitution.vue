<template>
  <CollectionPage
    v-model:selection="selection"
    :row-actions="item => actionsFor(item)"
    :source
    collection="institutions"
    :eyebrow
    :title="$t('Institucijos')"
    :lead="$t('VU SA ir VU organai, kuriuose dirba studentų atstovai.')"
    default-view="rows"
    :item-key="institutionKey"
    :columns
    table-fixed
    actions-width="w-44"
    table-min-width="min-w-[60rem]"
    :row-muted="isInactive"
    @row-action="(item, key) => onRowAction(key, item)"
    :search-placeholder="$t('Ieškoti institucijų')"
    :trash="{ count: deletedCount, active: isTrash }"
    :quick-filters
    :selectable="!isTrash"
    @quick-filter="toggleFollowedOnly"
  >
    <template #actions>
      <Button v-if="canCreate && !isTrash" as-child variant="brand" size="lg">
        <Link :href="route('institutions.create')">
          <Plus aria-hidden="true" />
          {{ $t('Nauja institucija') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item, actions: recordActions, view }">
      <CollectionRow
        :actions="view === 'preview' ? [] : recordActions"
        :spotlight="item === source?.items.value[0]"
        class="px-3 py-2.5 sm:px-4"
        data-slot="institution-collection-row"
        @select="key => onRowAction(key, item)"
      >
        <InstitutionTitleCell :institution="item" :title="nameOf(item)" :inactive="isInactive(item)" :href="isTrash ? undefined : route('institutions.show', item.id)" />
        <p class="mt-0.5 flex flex-wrap items-center gap-x-1.5 text-xs text-muted-foreground">
          <template v-for="(part, index) in rowFacts(item)" :key="part">
            <span v-if="index > 0" aria-hidden="true">·</span>
            <span>{{ part }}</span>
          </template>
          <template v-if="isFollowed(item)">
            <span v-if="rowFacts(item).length > 0" aria-hidden="true">·</span>
            <span class="inline-flex items-center gap-1 text-foreground" data-slot="institution-followed">
              <Eye class="size-3.5" aria-hidden="true" />
              {{ $t('Seki') }}
            </span>
          </template>
        </p>
      </CollectionRow>
    </template>

    <template #cell="{ item, column }">
      <InstitutionTitleCell v-if="column.key === 'name'" :institution="item" :title="nameOf(item)" :inactive="isInactive(item)" :href="isTrash ? undefined : route('institutions.show', item.id)">
        <template v-if="isFollowed(item)" #sub>
          <span class="inline-flex items-center gap-1">
            <Eye class="size-3.5" aria-hidden="true" />
            {{ $t('Seki') }}
          </span>
        </template>
      </InstitutionTitleCell>
      <template v-else-if="column.key === 'activity'">
        <StatusBadge v-if="activityStatus(item)" :status="activityStatus(item)!" />
        <template v-else>
          —
        </template>
      </template>
      <template v-else-if="column.key === 'tenant'">
        {{ item.tenant_shortname ?? '—' }}
      </template>
      <template v-else-if="column.key === 'types'">
        <span class="line-clamp-2">{{ getSearchTranslations(item, 'type_titles').join(', ') || '—' }}</span>
      </template>
      <span v-else-if="column.key === 'created_at' || column.key === 'updated_at'" class="tabular-nums">{{ formatRecordDate(item[column.key]) }}</span>
      <InstitutionMembersSummary
        v-else-if="column.key === 'members'"
        :institution-id="item.id"
        :current-count="item.current_user_names?.length ?? 0"
        :total-count="item.members_total ?? 0"
      />
    </template>

    <template v-if="!isTrash" #preview="{ item, actions: recordActions }">
      <InstitutionPreview :key="item.id" :institution="item">
        <template #status>
          <CollectionActivityStatus
            entity="institutions"
            :record-id="item.id"
            :active="item.is_active ?? true"
            :editable="canUpdateStatus(item)"
            :spotlight="String(item.id) === String(source.items.value[0]?.id)"
            @updated="active => statusUpdated(item, active)"
          />
        </template>
        <template #actions>
          <CollectionRowActions :actions="recordActions" emphasis full-labels @select="key => onRowAction(key, item)" />
        </template>
      </InstitutionPreview>
    </template>

    <template #bulk-actions="{ selected, clear }">
      <Button
        v-if="selected.some(item => !isFollowed(item) && canFollow(item))"
        variant="outline"
        voice="sentence"
        :disabled="subscriptions.bulkLoading.value"
        @click="setFollowed(selected, true, clear)"
      >
        <Eye aria-hidden="true" />
        {{ $t('Sekti') }}
      </Button>
      <Button
        v-if="selected.some(isFollowed)"
        variant="outline"
        voice="sentence"
        :disabled="subscriptions.bulkLoading.value"
        @click="setFollowed(selected, false, clear)"
      >
        <EyeOff aria-hidden="true" />
        {{ $t('Nebesekti') }}
      </Button>
    </template>

    <template #empty>
      <EmptyState
        v-if="followedOnly && followedIds.size === 0"
        mode="empty"
        :icon="Eye"
        :title="$t('Dar nieko neseki')"
        :description="$t('Pažymėk institucijas sąraše ir spausk „Sekti“ – jų posėdžiai atsiras tavo pradžioje.')"
        :action-label="$t('Rodyti visas institucijas')"
        @action="toggleFollowedOnly"
      />
      <EmptyState
        v-else
        mode="empty"
        :icon="InstitutionIcon"
        :title="isTrash ? $t('Ištrintų institucijų nėra') : $t('Institucijų dar nėra')"
        :description="isTrash ? undefined : $t('Čia atsiras VU SA ir VU organai. Sukūrusi instituciją, jos puslapyje pridėsi pareigybes ir narius.')"
        :action-label="canCreate && !isTrash ? $t('Nauja institucija') : undefined"
        :action-href="canCreate && !isTrash ? route('institutions.create') : undefined"
      />
    </template>
  </CollectionPage>

  <CollectionConfirmAction :dialog="actions.dialog.value" @confirm="actions.confirm" @cancel="actions.pending.value = null" />
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Trash2, Pencil, ArrowUpRight, Eye, EyeOff, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';

import { getSearchTranslation, getSearchTranslations } from '@/Composables/useTranslatedTitle';
import CollectionActivityStatus from '@/Components/Collection/CollectionActivityStatus.vue';
import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import CollectionRowActions, { type CollectionRowAction } from '@/Components/Collection/CollectionRowActions.vue';
import { quickFilterLabel } from '@/Components/Collection/quickFilterLabel';
import type { CollectionColumn, CollectionQuickFilter } from '@/Components/Collection/types';
import { InstitutionIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { EmptyState, StatusBadge } from '@/Components/Patterns';
import InstitutionTitleCell from '@/Components/Institutions/InstitutionTitleCell.vue';
import InstitutionMembersSummary from '@/Components/Institutions/InstitutionMembersSummary.vue';
import InstitutionPreview from '@/Components/Institutions/InstitutionPreview.vue';
import { Button } from '@/Components/ui/button';
import { institutionActivityStatuses, operatingStatuses, statusFor } from '@/Constants/statuses';
import { useRowAbilities } from '@/Composables/useRowAbilities';
import { useCollectionRecordActions } from '@/Composables/useCollectionRecordActions';
import { useInstitutionSubscription } from '@/Composables/useInstitutionSubscription';
import { escapeFilterValue } from '@/Features/Admin/AdminSearch/Services/AdminSearchService';
import {
  isTrashView,
  useTrashCollectionSource,
  useTypesenseCollectionSource,
  type CollectionSource,
} from '@/Composables/useCollectionSource';
import type { InstitutionSearchResult } from '@/Shared/Search/types';
import { formatRecordDate } from '@/Utils/dateTime';

type InstitutionRow = InstitutionSearchResult & { force_delete_blocked_reason?: string | null };

const props = defineProps<{
  /** Soft-deleted institutions the viewer could restore. */
  deletedCount: number;
  statusUpdateScope?: { all: boolean; tenantIds: number[] };
  /** Following is per user, so Typesense cannot filter on it; the ids become a filter instead. */
  followedInstitutionIds: string[];
  /** The user's own padaliniai: a first visit starts filtered to them. */
  defaultTenantShortnames: string[];
  /** Institution types whose meetings are public (MeetingSettings). */
  publicMeetingTypeIds: number[];
}>();

const page = usePage();
const isTrash = isTrashView();
const canCreate = computed(() => Boolean(page.props.auth?.can?.create?.institution));
const canForceDelete = computed(() => Boolean(page.props.auth?.can?.forceDelete?.institution));

const eyebrow = computed(() => $t('shell.workspaces.atstovavimas.title'));

// --- Sekamos -----------------------------------------------------------------------------------

const followedIds = ref(new Set(props.followedInstitutionIds));
const followedOnly = ref(new URLSearchParams(window.location.search).get('followed') === '1');
const selection = ref<string[]>([]);
const subscriptions = useInstitutionSubscription();

// No follows still filters: an empty list is the honest answer, and `id:=[]` is not valid syntax.
const followedFilter = computed(() => {
  if (!followedOnly.value) {
    return undefined;
  }

  const ids = [...followedIds.value];

  return `id:=[${ids.length > 0 ? ids.map(escapeFilterValue).join(',') : '__none__'}]`;
});

const quickFilters = computed<CollectionQuickFilter[]>(() => isTrash
  ? []
  : [{ id: 'followed', label: quickFilterLabel($t('Sekamos'), followedIds.value.size), active: followedOnly.value }]);

function toggleFollowedOnly(): void {
  const url = new URL(window.location.href);
  if (followedOnly.value) {
    url.searchParams.delete('followed');
  }
  else {
    url.searchParams.set('followed', '1');
  }
  url.searchParams.delete('pages');
  window.history.replaceState(window.history.state, '', url.toString());

  selection.value = [];
  followedOnly.value = !followedOnly.value;
}

const isFollowed = (institution: InstitutionRow) => followedIds.value.has(String(institution.id));

function markFollowed(ids: string[], followed: boolean): void {
  const next = new Set(followedIds.value);
  ids.forEach(id => (followed ? next.add(id) : next.delete(id)));
  followedIds.value = next;
}

async function toggleFollow(institution: InstitutionRow): Promise<void> {
  const id = String(institution.id);
  const followed = await subscriptions.toggleFollow(id, { is_followed: isFollowed(institution), is_duty_based: false });
  markFollowed([id], followed);
}

async function setFollowed(institutions: InstitutionRow[], followed: boolean, clear: () => void): Promise<void> {
  const ids = institutions
    .filter(institution => isFollowed(institution) !== followed && (!followed || canFollow(institution)))
    .map(institution => String(institution.id));

  if (await subscriptions.setFollowedMany(ids, followed)) {
    markFollowed(ids, followed);
    clear();
  }
}

// Built directly rather than through useTrashAwareSource: following needs the key's access scope.
const liveSource = isTrash
  ? null
  : useTypesenseCollectionSource<InstitutionRow>({
      collection: 'institutions',
      preserveUrlKeys: ['view', 'item', 'followed'],
      baseFilterBy: followedFilter,
      defaultFilters: { tenant_shortname: props.defaultTenantShortnames },
      valueLabel: (field, value) => (field === 'is_active' ? $t(operatingStatuses[String(value) === 'true' ? 'active' : 'inactive'].label) : undefined),
    });
const source: CollectionSource<InstitutionRow> = liveSource ?? useTrashCollectionSource<InstitutionRow>('institutions');

/**
 * Following means hearing about the meetings, so it is offered where they are public or the user
 * reaches the institution in full — the client side of InstitutionPolicy::follow().
 */
function canFollow(institution: InstitutionRow): boolean {
  const scope = liveSource?.accessScope.value;

  return Boolean(scope && (
    scope.isSuperAdmin
    || (institution.type_ids ?? []).some(typeId => props.publicMeetingTypeIds.includes(typeId))
    || (institution.tenant_id !== undefined && scope.tenantIds.includes(Number(institution.tenant_id)))
    || scope.institutionIds.includes(String(institution.id))
  ));
}

const rowAbilities = useRowAbilities('institutions', () => isTrash ? [] : source.items.value.map(item => item.id));

const actions = useCollectionRecordActions({
  source,
  routePrefix: 'institutions',
  onMutated: id => rowAbilities.invalidate([id]),
  restoreOpensRecord: true,
  canRestore: () => canCreate.value,
  canForceDelete: () => canForceDelete.value,
});

const institutionKey = (institution: InstitutionRow) => String(institution.id);
const nameOf = (institution: InstitutionRow) => getSearchTranslation(institution, 'name').trim()
  || `${$t('Be pavadinimo')} · ${institution.alias || institution.id}`;
const actionsFor = (institution: InstitutionRow): CollectionRowAction[] => isTrash
  ? actions.rowActions(institution, nameOf(institution), true).map(action => action.key.startsWith('restore:')
      ? { ...action, label: $t('Atkurti ir atidaryti') }
      : action)
  : [
      { key: 'open', label: $t('Atidaryti'), icon: ArrowUpRight, href: route('institutions.show', institution.id), role: 'open' as const, primary: true },
      ...(rowAbilities.can(institution.id, 'update') ? [{ key: 'edit', label: $t('Redaguoti'), icon: Pencil, role: 'edit' as const, menuOnly: true, href: route('institutions.edit', institution.id) }] : []),
      ...(rowAbilities.can(institution.id, 'delete') ? [{ key: `delete:${institution.id}:${nameOf(institution)}`, label: $t('Ištrinti'), icon: Trash2, destructive: true }] : []),
      ...(isFollowed(institution)
        ? [{ key: 'follow', label: $t('Nebesekti'), icon: EyeOff }]
        : canFollow(institution) ? [{ key: 'follow', label: $t('Sekti'), icon: Eye }] : []),
    ];

function onRowAction(key: string, institution: InstitutionRow): void {
  if (key === 'follow') {
    void toggleFollow(institution);
    return;
  }

  actions.select(key, institution.force_delete_blocked_reason);
}

function statusUpdated(item: InstitutionRow, active: boolean): void {
  rowAbilities.invalidate([item.id]);
  source.patchItems([String(item.id)], active ? { is_active: true } : { is_active: false, current_user_names: [] });
  source.refresh();
}

const rowFacts = (item: InstitutionRow): string[] => [
  item.tenant_shortname,
  ...getSearchTranslations(item, 'type_titles'),
  item.current_user_names?.length ? $t('Narių: :count', { count: String(item.current_user_names.length) }) : null,
].filter((part): part is string => Boolean(part));

const isInactive = (item: InstitutionRow) => item.is_active === false || item.is_active === 0;

// A closed institution owes no meetings, so "Vėluoja" would be a false alarm.
const activityStatus = (item: InstitutionRow) => (isInactive(item) ? null : statusFor(institutionActivityStatuses, item.activity_status));

const canUpdateStatus = (item: InstitutionRow) => !isTrash && Boolean(props.statusUpdateScope?.all || props.statusUpdateScope?.tenantIds.includes(Number(item.tenant_id)));

const columns = computed<CollectionColumn[]>(() => [
  { key: 'name', label: $t('Institucija'), sortField: 'name_lt' },
  { key: 'tenant', label: $t('Padalinys'), class: 'w-28' },
  { key: 'activity', label: $t('Veiklos būklė'), class: 'w-40' },
  { key: 'types', label: $t('Tipas'), class: 'w-48', defaultHidden: true },
  { key: 'members', label: $t('Nariai'), class: 'w-40' },
  { key: 'created_at', label: $t('Sukurta'), class: 'w-36', sortField: 'created_at', defaultHidden: true },
  { key: 'updated_at', label: $t('Atnaujinta'), class: 'w-36', sortField: 'updated_at', defaultHidden: true },
]);
</script>
