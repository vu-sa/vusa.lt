<template>
  <CollectionPage
    :source
    collection="users"
    entity-type="user"
    :eyebrow="`${$t('shell.workspaces.organizacija.title')} · ${$t('shell.sections.nariai')}`"
    :title="$t('Nariai')"
    :lead="$t('Žmonės, turintys pareigybių ar roles VU SA sistemoje.')"
    default-view="table"
    :item-key="userKey"
    :columns
    :search-placeholder="$t('Ieškoti narių')"
  >
    <template #actions>
      <Button v-if="deletedCount > 0" as-child variant="ghost">
        <Link :href="route('users.index', { showDeleted: 'true' })">
          <Trash2 aria-hidden="true" />
          {{ $t('Ištrinti') }} ({{ deletedCount }})
        </Link>
      </Button>
      <Button v-if="canMerge" variant="ghost" @click="mergeMode = true">
        <Merge aria-hidden="true" />
        {{ $t('Sujungti') }}
      </Button>
      <Button v-if="canCreate" as-child variant="brand">
        <Link :href="route('users.create')">
          <Plus aria-hidden="true" />
          {{ $t('Naujas narys (-ė)') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex min-h-16 items-center gap-3 px-3 py-3 sm:px-4">
        <Checkbox v-if="mergeMode" :model-value="selectedIds.includes(userKey(item))" @update:model-value="toggle(item)" />
        <UserAvatar :user="item" :size="40" class="shrink-0" />
        <Link :href="route('users.show', item.id)" prefetch class="min-w-0 flex-1" data-collection-open>
          <span class="block truncate font-medium text-foreground">{{ item.name }}</span>
          <span class="block truncate text-sm text-muted-foreground">{{ item.email }}</span>
        </Link>
        <StatusBadge v-if="noUnit(item)" :status="withoutUnit" />
      </article>
    </template>

    <template #cell="{ item, column }">
      <Checkbox v-if="column.key === 'select'" :model-value="selectedIds.includes(userKey(item))" @update:model-value="toggle(item)" />
      <Link v-else-if="column.key === 'name'" :href="route('users.show', item.id)" prefetch class="font-medium hover:text-brand">
        {{ item.name }}
      </Link>
      <a v-else-if="column.key === 'email' && item.email" :href="`mailto:${item.email}`" class="hover:text-brand">{{ item.email }}</a>
      <a v-else-if="column.key === 'phone' && item.phone" :href="`tel:${item.phone}`" class="tabular-nums hover:text-brand">{{ item.phone }}</a>
      <template v-else-if="column.key === 'last_action'">
        <span v-if="item.last_action" class="tabular-nums">{{ formatDate(new Date(item.last_action as string)) }}</span>
        <span v-else class="text-muted-foreground">{{ $t('Niekada') }}</span>
      </template>
      <template v-else-if="column.key === 'duties'">
        <StatusBadge v-if="noUnit(item)" :status="withoutUnit" />
        <span v-else class="tabular-nums">{{ (item as UserRow).duties_count }}</span>
      </template>
    </template>

    <template #empty>
      <EmptyState
        mode="empty"
        :icon="UserIcon"
        :title="$t('Narių dar nėra')"
        :description="$t('Čia atsiras žmonės, turintys pareigybių ar roles.')"
        :action-label="canCreate ? $t('Naujas narys (-ė)') : undefined"
        :action-href="canCreate ? route('users.create') : undefined"
      />
    </template>
  </CollectionPage>

  <CollectionSelectionBar v-if="mergeMode" :count="selectedIds.length" :count-label="$t('Pasirinkta')" @clear="leaveMerge">
    <Button variant="brand" size="sm" :disabled="selectedIds.length < 2" @click="mergeRecords = selectedRecords">
      <Merge aria-hidden="true" />
      {{ $t('Sujungti') }}
    </Button>
  </CollectionSelectionBar>

  <MergeRecordsDialog
    :open="mergeRecords.length > 0"
    type="users"
    :records="mergeRecords"
    :submit-url="route('users.mergeUsers')"
    target-field="kept_user_id"
    source-field="source_user_ids"
    @close="mergeRecords = []"
    @merged="merged"
  />
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { CircleSlash, Merge, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import CollectionSelectionBar from '@/Components/Collection/CollectionSelectionBar.vue';
import type { CollectionColumn } from '@/Components/Collection/types';
import { UserIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import MergeRecordsDialog, { type MergeRecord } from '@/Components/Merge/MergeRecordsDialog.vue';
import { EmptyState, StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { useAdminNavigation } from '@/Composables/useAdminNavigation';
import { useDatabaseCollectionSource } from '@/Composables/useCollectionSource';
import type { StatusPresentation } from '@/Constants/statuses';
import { formatDate } from '@/Utils/dateTime';

type UserRow = App.Entities.User & { duties_count?: number };

const props = defineProps<{
  users: { data: UserRow[]; meta: { total: number; per_page: number; current_page: number; last_page: number } };
  deletedCount: number;
}>();

const { hasCollectionAction } = useAdminNavigation();
const canCreate = computed(() => Boolean(usePage().props.auth?.can?.create?.user));
const canMerge = computed(() => hasCollectionAction('users.index', 'merge'));

const mergeMode = ref(false);
const selectedIds = ref<string[]>([]);
const mergeRecords = ref<MergeRecord[]>([]);

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
  sortOptions: [
    { value: 'name:asc', label: $t('Pagal vardą') },
    { value: 'last_action:desc', label: $t('Neseniai prisijungę') },
    { value: 'created_at:desc', label: $t('Naujausi pirmiausia') },
  ],
});

/** A member with no duties belongs to no unit, so no other tenant admin sees them until one is assigned. */
const withoutUnit: StatusPresentation = { label: 'Be padalinio', role: 'attention', icon: CircleSlash };
const noUnit = (user: UserRow) => Number(user.duties_count ?? 0) === 0;

const columns = computed<CollectionColumn[]>(() => [
  ...(mergeMode.value ? [{ key: 'select', label: $t('Pasirinkti'), class: 'w-12' }] : []),
  { key: 'name', label: $t('Vardas') },
  { key: 'email', label: $t('El. paštas') },
  { key: 'phone', label: $t('Telefonas'), class: 'w-40' },
  { key: 'last_action', label: $t('Paskutinis prisijungimas'), class: 'w-48' },
  { key: 'duties', label: $t('Pareigų skaičius'), class: 'w-36' },
]);

const userKey = (user: UserRow) => String(user.id);

const selectedRecords = computed<MergeRecord[]>(() => source.items.value
  .filter(user => selectedIds.value.includes(userKey(user)))
  .map(user => ({ id: user.id, label: user.name, context: user.email })));

function toggle(user: UserRow): void {
  const id = userKey(user);
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
</script>
