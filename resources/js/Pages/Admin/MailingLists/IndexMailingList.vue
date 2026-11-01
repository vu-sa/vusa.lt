<template>
  <CollectionPage
    :row-actions
    :source
    collection="mailingLists"
    :eyebrow="$t('shell.workspaces.organizacija.title')"
    :title="$t('shell.sections.konferencijos')"
    :lead="mailmanConfigured ? $t('mailing_lists.lead') : $t('mailing_lists.not_configured')"
    default-view="rows"
    :available-views="['rows', 'table']"
    :item-key="list => list.id"
    :columns
    table-fixed
    actions-width="w-44"
    table-min-width="min-w-[89rem]"
    :search-placeholder="$t('mailing_lists.search')"
  >
    <template #actions>
      <Button
        v-if="canImport && mailmanConfigured"
        variant="outline"
        voice="sentence"
        :disabled="importing"
        @click="importFromMailman"
      >
        <RefreshCw :class="['size-4', importing && 'animate-spin']" aria-hidden="true" />
        {{ $t('mailing_lists.import') }}
      </Button>
    </template>

    <template #row="{ item, actions: recordActions }">
      <CollectionRow :actions="recordActions" :spotlight="item === source?.items.value[0]" class="px-3 py-3 sm:px-4">
        <div class="min-w-0 flex-1">
          <Link :href="route('mailingLists.show', item.id)" data-collection-open class="block truncate font-medium hover:text-brand">
            {{ item.address }}
          </Link>
          <p class="mt-1 text-xs text-muted-foreground">{{ rowFacts(item).join(' · ') }}</p>
        </div>
        <div class="mt-2 flex flex-wrap gap-2">
          <StatusBadge v-if="item.last_sync_status && item.last_sync_status !== 'synced'" :status="mailingListSyncStatuses[item.last_sync_status]" />
          <StatusBadge :status="mailingListModes[item.mode]" />
        </div>
      </CollectionRow>
    </template>

    <template #cell="{ item, column }">
      <Link v-if="column.key === 'address'" :href="route('mailingLists.show', item.id)" data-collection-open class="block truncate font-medium hover:text-brand">
        {{ item.address }}
      </Link>
      <StatusBadge v-else-if="column.key === 'mode'" :status="mailingListModes[item.mode]" />
      <span v-else-if="column.key === 'tenant'" class="text-xs">{{ item.tenant?.shortname ?? '—' }}</span>
      <span v-else-if="column.key === 'member_count'" class="tabular-nums">{{ item.member_count }}</span>
      <span v-else-if="column.key === 'created_at' || column.key === 'updated_at'" class="tabular-nums">{{ formatRecordDate(item[column.key]) }}</span>
      <span v-else-if="column.key === 'posts_last_year'" class="tabular-nums">{{ item.posts_last_year ?? '—' }}</span>
      <span v-else-if="column.key === 'last_post_at'" class="text-xs tabular-nums">{{ item.last_post_at ? formatDate(item.last_post_at) : '—' }}</span>
    </template>

    <template #empty>
      <EmptyState
        :mode="source.query.value.trim() || source.activeFilterCount.value ? 'no-results' : 'empty'"
        :icon="MailingListIcon"
        :title="$t('mailing_lists.empty_title')"
        :description="mailmanConfigured ? $t('mailing_lists.empty_description') : $t('mailing_lists.not_configured')"
      />
    </template>
  </CollectionPage>

  <MailingListSheetForm v-model:open="createOpen" :tenants :domain />
</template>

<script setup lang="ts">
import { Pencil, ArrowUpRight, Mail as MailingListIcon, RefreshCw } from '@lucide/vue';
import { Link, router } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { computed, ref, toRef } from 'vue';

import type { AdminRecordAction } from '@/Features/Admin/RecordActions/types';
import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import { useRowAbilities } from '@/Composables/useRowAbilities';
import type { CollectionColumn } from '@/Components/Collection/types';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { EmptyState, StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useLocalCollectionSource } from '@/Composables/useCollectionSource';
import { mailingListModes, mailingListSyncStatuses } from '@/Constants/statuses';
import MailingListSheetForm from '@/Features/Admin/MailingLists/MailingListSheetForm.vue';
import type { MailingListSummary } from '@/Features/Admin/MailingLists/types';
import { formatDate, formatNearDate, formatRecordDate } from '@/Utils/dateTime';

type MailingListRow = MailingListSummary & { sources_count: number };

const props = defineProps<{
  mailingLists: MailingListRow[];
  canCreate: boolean;
  canImport: boolean;
  mailmanConfigured: boolean;
  tenants: App.Entities.Tenant[];
  domain: string;
  openCreate: boolean;
}>();

const createOpen = ref(props.openCreate && props.canCreate);
const importing = ref(false);

const yearAgo = new Date(Date.now() - 365 * 24 * 60 * 60 * 1000).toISOString();

const source = useLocalCollectionSource<MailingListRow>({
  items: toRef(props, 'mailingLists'),
  searchText: list => [list.address, list.display_name, list.description, list.tenant?.shortname],
  defaultSort: 'address:asc',
  sortOptions: () => [
    { value: 'created_at:asc', label: $t('Seniausiai sukurti'), by: list => list.created_at ?? '' },
    { value: 'created_at:desc', label: $t('Naujausiai sukurti'), by: list => list.created_at ?? '' },
    { value: 'updated_at:asc', label: $t('Seniausiai atnaujinti'), by: list => list.updated_at ?? '' },
    { value: 'updated_at:desc', label: $t('Paskutiniai atnaujinti'), by: list => list.updated_at ?? '' },
    { value: 'address:asc', label: $t('Pagal pavadinimą (A–Z)'), by: list => list.address },
    { value: 'address:desc', label: $t('Pagal pavadinimą (Z–A)'), by: list => list.address },
    { value: 'last_post_at:desc', label: $t('Naujausias laiškas'), by: list => list.last_post_at ?? null },
    { value: 'last_post_at:asc', label: $t('Seniausias laiškas'), by: list => list.last_post_at ?? null },
    { value: 'member_count:desc', label: $t('Daugiausia gavėjų'), by: list => list.member_count },
    { value: 'member_count:asc', label: $t('Mažiausiai gavėjų'), by: list => list.member_count },
  ],
  facets: [
    { field: 'mode', label: $t('mailing_lists.fields.mode'), get: list => list.mode, valueLabel: value => $t(`mailing_lists.modes.${value}`) },
    { field: 'tenant', label: $tChoice('entities.tenant.model', 1), get: list => list.tenant?.shortname },
    {
      field: 'quiet',
      label: $t('mailing_lists.filters.quiet'),
      get: list => (!list.last_post_at || list.last_post_at < yearAgo ? $t('mailing_lists.filters.quiet') : undefined),
    },
  ],
});

const columns = computed<CollectionColumn[]>(() => [
  { key: 'address', label: $t('mailing_lists.fields.local_part'), sortField: 'address' },
  { key: 'mode', label: $t('mailing_lists.fields.mode'), class: 'w-44' },
  { key: 'tenant', label: $tChoice('entities.tenant.model', 1), class: 'w-24' },
  { key: 'member_count', label: $t('mailing_lists.fields.members'), class: 'w-24', sortField: 'member_count' },
  { key: 'posts_last_year', label: $t('mailing_lists.fields.posts_last_year'), class: 'w-28' },
  { key: 'created_at', label: $t('Sukurta'), class: 'w-36', sortField: 'created_at', defaultHidden: true },
  { key: 'updated_at', label: $t('Atnaujinta'), class: 'w-36', sortField: 'updated_at', defaultHidden: true },
  { key: 'last_post_at', label: $t('mailing_lists.fields.last_post'), class: 'w-32', sortField: 'last_post_at' },
]);

const rowFacts = (item: MailingListRow): string[] => [
  item.display_name !== item.address ? item.display_name : null,
  item.tenant?.shortname,
  `${$t('mailing_lists.fields.members')}: ${item.member_count}`,
  `${$t('mailing_lists.fields.last_post')}: ${item.last_post_at ? formatNearDate(item.last_post_at) : '—'}`,
].filter((part): part is string => Boolean(part));

function importFromMailman(): void {
  importing.value = true;
  router.post(route('mailingLists.import'), {}, {
    preserveScroll: true,
    onFinish: () => { importing.value = false; },
  });
}
const rowActions = (item: MailingListRow): AdminRecordAction[] => [
  { key: 'open', label: $t('Atidaryti'), icon: ArrowUpRight, role: 'open' as const, primary: true, href: route('mailingLists.show', item.id) },
  ...(rowAbilities.can(item.id, 'update') ? [{ key: 'edit', label: $t('Redaguoti'), icon: Pencil, role: 'edit' as const, menuOnly: true, href: route('mailingLists.show', { mailingList: item.id, action: 'edit' }) }] : []),
];

const rowAbilities = useRowAbilities('mailingLists', () => source.items.value.map(item => item.id));
</script>
