<template>
  <CollectionPage
    :source
    collection="tenants"
    entity-type="tenant"
    :eyebrow="`${$t('shell.workspaces.organizacija.title')} · ${$t('shell.sections.padaliniai')}`"
    :title="$t('Padaliniai')"
    :lead="$t('VU SA padaliniai: kiekvienas turi savo svetainės dalį, narius ir pareigybes.')"
    default-view="table"
    :item-key="tenant => String(tenant.id)"
    :columns
    :search-placeholder="$t('Ieškoti padalinių')"
  >
    <template #actions>
      <Button v-if="canCreate" variant="brand" size="lg" @click="openSheet()">
        <Plus aria-hidden="true" />
        {{ $t('Naujas padalinys') }}
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center">
        <div class="min-w-0 flex-1">
          <CollectionPrimaryCell :title="item.fullname" clickable :sub="item.alias" mono @open="openSheet(item)" />
          <p class="mt-2 text-xs text-muted-foreground">
            {{ item.shortname }} · {{ item.type }}
          </p>
        </div>
        <div class="flex items-center gap-1">
          <Button variant="outline" size="sm" @click="openSheet(item)">
            <Pencil aria-hidden="true" class="size-4" />
            {{ $t('Redaguoti') }}
          </Button>
          <Button
            v-if="canCreate"
            variant="outline"
            size="sm"
            class="text-destructive hover:text-destructive"
            @click="targetToDelete = item"
          >
            <Trash2 aria-hidden="true" class="size-4" />
            {{ $t('Ištrinti') }}
          </Button>
        </div>
      </article>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell
        v-if="column.key === 'fullname'"
        :title="item.fullname"
        clickable
        :sub="item.alias"
        mono
        @open="openSheet(item)"
      />
      <span v-else-if="column.key === 'shortname'">{{ item.shortname }}</span>
      <span v-else-if="column.key === 'type'" class="text-muted-foreground">{{ item.type }}</span>
      <div v-else-if="column.key === 'actions'" class="flex justify-end gap-1">
        <Button variant="outline" size="sm" @click="openSheet(item)">
          <Pencil aria-hidden="true" class="size-4" />
          {{ $t('Redaguoti') }}
        </Button>
        <Button
          v-if="canCreate"
          variant="outline"
          size="sm"
          class="text-destructive hover:text-destructive"
          @click="targetToDelete = item"
        >
          <Trash2 aria-hidden="true" class="size-4" />
          {{ $t('Ištrinti') }}
        </Button>
      </div>
    </template>
  </CollectionPage>

  <TenantSheetForm
    v-model:open="sheetOpen"
    :tenant="editingTenant"
    :assignable-institutions
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
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref, toRef } from 'vue';

import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import type { CollectionColumn } from '@/Components/Collection/types';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { ConfirmDialog } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useLocalCollectionSource } from '@/Composables/useCollectionSource';
import TenantSheetForm, { type TenantInput } from '@/Features/Admin/Tenants/TenantSheetForm.vue';

type TenantRow = Pick<App.Entities.Tenant, 'id' | 'fullname' | 'shortname' | 'alias' | 'type'> & {
  shortname_vu?: string;
  primary_institution_id?: number | string | null;
};

const props = defineProps<{
  tenants: TenantRow[];
  assignableInstitutions?: App.Entities.Institution[];
}>();

const canCreate = computed(() => Boolean(usePage().props.auth?.can?.create?.tenant));

const sheetOpen = ref(false);
const editingTenant = ref<TenantInput | null>(null);
const targetToDelete = ref<TenantRow | null>(null);

const source = useLocalCollectionSource<TenantRow>({
  items: toRef(props, 'tenants'),
  searchText: tenant => [tenant.fullname, tenant.shortname, tenant.alias],
  defaultSort: 'fullname:asc',
  sortOptions: [
    { value: 'fullname:asc', label: $t('Pagal pavadinimą (A–Z)'), by: tenant => tenant.fullname },
    { value: 'fullname:desc', label: $t('Pagal pavadinimą (Z–A)'), by: tenant => tenant.fullname },
  ],
  facets: [{ field: 'type', label: $t('Tipas'), get: tenant => tenant.type }],
});

const columns = computed<CollectionColumn[]>(() => [
  { key: 'fullname', label: $t('Padalinys'), sortField: 'fullname' },
  { key: 'shortname', label: $t('Trumpinys'), class: 'w-40' },
  { key: 'type', label: $t('Tipas'), class: 'w-40' },
  { key: 'actions', label: $t('Veiksmai'), class: 'w-px text-right', pinned: true },
]);

function openSheet(item: TenantRow | null = null): void {
  if (item) {
    editingTenant.value = {
      id: item.id,
      fullname: item.fullname,
      shortname: item.shortname,
      type: item.type,
      alias: item.alias,
      shortname_vu: item.shortname_vu ?? '',
      primary_institution_id: item.primary_institution_id ?? null,
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
