<template>
  <CollectionPage
    v-model:selection="selection"
    :source
    collection="institutions"
    entity-type="institution"
    :eyebrow
    :title="$t('Institucijos')"
    :lead="$t('VU SA ir VU organai, kuriuose dirba studentų atstovai.')"
    default-view="rows"
    :item-key="institutionKey"
    :columns
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

    <template #row="{ item }">
      <div class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center" data-slot="institution-collection-row">
        <div class="min-w-0 flex-1">
          <CollectionPrimaryCell :title="nameOf(item)" :href="isTrash ? undefined : route('institutions.show', item.id)" />
          <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
            <span v-if="item.tenant_shortname">{{ item.tenant_shortname }}</span>
            <span v-if="item.type_titles?.length">{{ item.type_titles.join(', ') }}</span>
            <span v-if="item.current_user_names?.length">
              {{ $t('Narių: :count', { count: String(item.current_user_names.length) }) }}
            </span>
            <span v-if="isFollowed(item)" class="inline-flex items-center gap-1 text-foreground" data-slot="institution-followed">
              <Eye class="size-3.5" aria-hidden="true" />
              {{ $t('Seki') }}
            </span>
          </p>
        </div>
        <CollectionRowActions :actions="actionsFor(item)" @select="key => onRowAction(key, item)" />
      </div>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell
        v-if="column.key === 'name'"
        :title="nameOf(item)"
        :href="isTrash ? undefined : route('institutions.show', item.id)"
      />
      <template v-else-if="column.key === 'tenant'">
        {{ item.tenant_shortname ?? '—' }}
      </template>
      <template v-else-if="column.key === 'types'">
        {{ item.type_titles?.join(', ') || '—' }}
      </template>
      <span v-else-if="column.key === 'members'" class="tabular-nums">
        {{ item.current_user_names?.length ?? 0 }}
      </span>
      <span v-else-if="column.key === 'followed'" class="inline-flex items-center gap-1">
        <template v-if="isFollowed(item)">
          <Eye class="size-3.5" aria-hidden="true" />
          {{ $t('Seki') }}
        </template>
        <span v-else class="text-muted-foreground">—</span>
      </span>
      <CollectionRowActions
        v-else-if="column.key === 'actions'"
        :actions="actionsFor(item)"
        @select="key => onRowAction(key, item)"
      />
    </template>

    <template v-if="!isTrash" #preview="{ item }">
      <div class="flex flex-col gap-4 p-5">
        <h2 class="text-lg font-semibold">
          {{ nameOf(item) }}
        </h2>
        <p v-if="item.tenant_shortname" class="text-sm text-muted-foreground">
          {{ item.tenant_shortname }}
        </p>
        <Button as-child variant="outline" voice="sentence">
          <Link :href="route('institutions.show', item.id)">
            {{ $t('Atidaryti') }}
          </Link>
        </Button>
      </div>
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
import { ArrowUpRight, Eye, EyeOff, Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRowActions, { type CollectionRowAction } from '@/Components/Collection/CollectionRowActions.vue';
import type { CollectionColumn, CollectionQuickFilter } from '@/Components/Collection/types';
import { InstitutionIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
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

type InstitutionRow = InstitutionSearchResult & { force_delete_blocked_reason?: string | null };

const props = defineProps<{
  /** Soft-deleted institutions the viewer could restore. */
  deletedCount: number;
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

const eyebrow = computed(() => `${$t('shell.workspaces.atstovavimas.title')} · ${$t('shell.sections.institucijos')}`);

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
  : [{ id: 'followed', label: `${$t('Sekamos')} (${followedIds.value.size})`, active: followedOnly.value }]);

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
  const followed = await subscriptions.toggleFollow(id, { is_followed: isFollowed(institution), is_muted: false, is_duty_based: false });
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

// Live institutions are edited on their record page; the list only acts on the trash.
const actions = useCollectionRecordActions({
  routePrefix: 'institutions',
  canRestore: () => canCreate.value,
  canForceDelete: () => canForceDelete.value,
});

const institutionKey = (institution: InstitutionRow) => String(institution.id);
const nameOf = (institution: InstitutionRow) => institution.name_lt || institution.name_en || $t('Be pavadinimo');
const actionsFor = (institution: InstitutionRow): CollectionRowAction[] => isTrash
  ? actions.rowActions(institution, nameOf(institution), true)
  : [
      { key: 'open', label: $t('Atidaryti'), icon: ArrowUpRight, href: route('institutions.show', institution.id), labelled: true },
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

const columns = computed<CollectionColumn[]>(() => [
  { key: 'name', label: $t('Institucija') },
  { key: 'tenant', label: $t('Padalinys'), class: 'w-32' },
  { key: 'types', label: $t('Tipas') },
  { key: 'members', label: $t('Nariai'), class: 'w-24' },
  ...(isTrash ? [] : [{ key: 'followed', label: $t('Sekimas'), class: 'w-24' }]),
  { key: 'actions', label: $t('Veiksmai'), class: 'w-px text-right', pinned: true },
]);
</script>
