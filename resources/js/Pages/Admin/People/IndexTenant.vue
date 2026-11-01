<template>
  <CollectionPage
    :row-actions="item => rowActions(item)"
    :source
    collection="tenants"
    :eyebrow="$t('shell.workspaces.organizacija.title')"
    :title="$t('Padaliniai')"
    :lead="$t('VU SA padaliniai: kiekvienas turi savo svetainės dalį, narius ir pareigybes.')"
    default-view="table"
    :item-key="tenant => String(tenant.id)"
    :columns
    table-fixed
    actions-width="w-44"
    table-min-width="min-w-[65rem]"
    :search-placeholder="$t('Ieškoti padalinių')"
    @row-action="(item, key) => selectRowAction(key, item)"
  >
    <template #actions>
      <Button v-if="canCreate" variant="brand" size="lg" @click="openSheet()">
        <Plus aria-hidden="true" />
        {{ $t('Naujas padalinys') }}
      </Button>
    </template>

    <template #row="{ item, actions: recordActions }">
      <CollectionRow :actions="recordActions" :spotlight="item === source.items.value[0]" class="px-3 py-2.5 sm:px-4" @select="key => selectRowAction(key, item)">
        <div class="min-w-0 flex-1">
          <CollectionPrimaryCell :title="item.fullname" :clickable="item.can.update" :sub="item.alias" mono @open="openSheet(item)" />
          <p class="mt-2 text-xs text-muted-foreground">
            {{ item.shortname }} · {{ item.type }}
          </p>
          <p class="mt-2 text-sm text-muted-foreground">
            {{ $t('forms.tenants.primary_institution') }}: {{ item.primary_institution?.name ?? $t('forms.tenants.not_assigned') }}
            <InactiveInstitutionBadge :active="item.primary_institution?.is_active" />
          </p>
        </div>
      </CollectionRow>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell
        v-if="column.key === 'fullname'"
        :title="item.fullname"
        :clickable="item.can.update"
        :sub="item.alias"
        mono
        @open="openSheet(item)"
      />
      <span v-else-if="column.key === 'shortname'">{{ item.shortname }}</span>
      <span v-else-if="column.key === 'type'" class="text-muted-foreground">{{ item.type }}</span>
      <span v-else-if="column.key === 'primary_institution'" class="text-sm">
        {{ item.primary_institution?.name ?? $t('forms.tenants.not_assigned') }}
        <InactiveInstitutionBadge :active="item.primary_institution?.is_active" />
      </span>
      <span v-else-if="column.key === 'created_at' || column.key === 'updated_at'" class="tabular-nums">{{ formatRecordDate(item[column.key]) }}</span>
    </template>
  </CollectionPage>

  <TenantSheetForm
    v-model:open="sheetOpen"
    :tenant="editingTenant"
    @saved="onSaved"
  />

  <ConfirmDialog
    :open="targetToDelete !== null"
    :title="$t('Ištrinti padalinį?')"
    :description="$t('Padalinys bus pašalintas iš sistemos.')"
    :confirm-label="$t('Ištrinti')"
    destructive
    @update:open="!$event && (targetToDelete = null)"
    @confirm="deleteTenant"
  />
</template>

<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref, toRef } from 'vue';

import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import { formatRecordDate } from '@/Utils/dateTime';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import type { CollectionRowAction } from '@/Components/Collection/CollectionRowActions.vue';
import type { CollectionColumn } from '@/Components/Collection/types';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import InactiveInstitutionBadge from '@/Components/Institutions/InactiveInstitutionBadge.vue';
import { ConfirmDialog } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useLocalCollectionSource } from '@/Composables/useCollectionSource';
import TenantSheetForm, { type TenantInput, type TenantInstitution } from '@/Features/Admin/Tenants/TenantSheetForm.vue';

type TenantRow = Pick<App.Entities.Tenant, 'id' | 'fullname' | 'shortname' | 'alias' | 'type' | 'created_at' | 'updated_at'> & {
  can: { update: boolean; delete: boolean };
  shortname_vu?: string;
  primary_institution_id?: string | null;
  primary_institution?: TenantInstitution | null;
  goals_enabled?: boolean;
};

const props = defineProps<{
  tenants: TenantRow[];
}>();

const canCreate = computed(() => Boolean(usePage().props.auth?.can?.create?.tenant));
const rowActions = (item: TenantRow): CollectionRowAction[] => [
  ...(item.can.update ? [{ key: 'edit', role: 'edit' as const, primary: true, label: $t('Redaguoti'), icon: Pencil }] : []),
  ...(item.can.delete ? [{ key: 'delete', label: $t('Ištrinti'), icon: Trash2, destructive: true }] : []),
];

function selectRowAction(key: string, item: TenantRow): void {
  if (key === 'edit') openSheet(item);
  if (key === 'delete') targetToDelete.value = item;
}

const sheetOpen = ref(false);
const editingTenant = ref<(TenantInput & { primary_institution?: TenantInstitution | null }) | null>(null);
const targetToDelete = ref<TenantRow | null>(null);

const source = useLocalCollectionSource<TenantRow>({
  items: toRef(props, 'tenants'),
  searchText: tenant => [tenant.fullname, tenant.shortname, tenant.alias],
  defaultSort: 'fullname:asc',
  sortOptions: () => [
    { value: 'fullname:asc', label: $t('Pagal pavadinimą (A–Z)'), by: tenant => tenant.fullname },
    { value: 'fullname:desc', label: $t('Pagal pavadinimą (Z–A)'), by: tenant => tenant.fullname },
    { value: 'shortname:asc', label: $t('Pagal trumpinį (A–Z)'), by: tenant => tenant.shortname },
    { value: 'shortname:desc', label: $t('Pagal trumpinį (Z–A)'), by: tenant => tenant.shortname },
    { value: 'type:asc', label: $t('Pagal tipą (A–Z)'), by: tenant => tenant.type ?? '' },
    { value: 'type:desc', label: $t('Pagal tipą (Z–A)'), by: tenant => tenant.type ?? '' },
    { value: 'created_at:asc', label: `${$t('Sukurta')} · ↑`, by: tenant => tenant.created_at ? new Date(tenant.created_at).getTime() : 0 },
    { value: 'created_at:desc', label: `${$t('Sukurta')} · ↓`, by: tenant => tenant.created_at ? new Date(tenant.created_at).getTime() : 0 },
    { value: 'updated_at:asc', label: `${$t('Atnaujinta')} · ↑`, by: tenant => tenant.updated_at ? new Date(tenant.updated_at).getTime() : 0 },
    { value: 'updated_at:desc', label: `${$t('Atnaujinta')} · ↓`, by: tenant => tenant.updated_at ? new Date(tenant.updated_at).getTime() : 0 },
  ],
  facets: [{ field: 'type', label: $t('Tipas'), get: tenant => tenant.type }],
});

const columns = computed<CollectionColumn[]>(() => [
  { key: 'fullname', label: $t('Padalinys'), sortField: 'fullname' },
  { key: 'shortname', label: $t('Trumpinys'), class: 'w-40', sortField: 'shortname' },
  { key: 'type', label: $t('Tipas'), class: 'w-40', sortField: 'type' },
  { key: 'primary_institution', label: $t('forms.tenants.primary_institution'), class: 'w-64' },
  { key: 'created_at', label: $t('Sukurta'), class: 'w-36', sortField: 'created_at', defaultHidden: true },
  { key: 'updated_at', label: $t('Atnaujinta'), class: 'w-36', sortField: 'updated_at', defaultHidden: true },
]);

function openSheet(item: TenantRow | null = null): void {
  if (item) {
    editingTenant.value = {
      id: item.id,
      created_at: item.created_at,
      updated_at: item.updated_at,
      fullname: item.fullname,
      shortname: item.shortname,
      type: item.type,
      alias: item.alias,
      shortname_vu: item.shortname_vu ?? '',
      primary_institution_id: item.primary_institution_id ?? null,
      primary_institution: item.primary_institution ?? null,
      goals_enabled: Boolean(item.goals_enabled),
    };
  }
  else {
    editingTenant.value = null;
  }
  sheetOpen.value = true;
}

function onSaved(): void {
  editingTenant.value = null;
  router.reload({ only: ['tenants'] });
}

function deleteTenant(): void {
  if (!targetToDelete.value) return;

  const { id } = targetToDelete.value;
  targetToDelete.value = null;

  router.delete(route('tenants.destroy', id), {
    preserveScroll: true,
    onSuccess: () => {
      router.reload({ only: ['tenants'] });
    },
  });
}
</script>
