<template>
  <CollectionPage
    :row-actions="item => actionsFor(item)"
    :source
    collection="quickLinks"
    :eyebrow="$t('shell.workspaces.svetaine.title')"
    :title="isTrash ? $t('Ištrintos greitosios nuorodos') : $t('Greitosios nuorodos')"
    :lead="isTrash
      ? $t('Peržiūrėk ištrintas greitąsias nuorodas arba atkurk jas.')
      : $t('Nuorodos ir mygtukai, rodomi pradiniame svetainės puslapyje.')"
    default-view="table"
    :available-views="['table', 'rows']"
    :item-key="link => String(link.id)"
    :columns
    table-fixed
    actions-width="w-44"
    table-min-width="min-w-[61rem]"
    :quick-filters="languageFilters"
    :trash="{ count: deletedCount ?? 0, active: isTrash }"
    :keep-params="['tenant_id', 'language']"
    :search-placeholder="$t('Ieškoti nuorodų')"
    @row-action="(item, key) => selectAction(item, key)"
    @quick-filter="id => source.toggleFilter('language', id)"
  >
    <template #actions>
      <Button
        v-if="!isTrash && reorderTenants.length > 0 && quickLinks.length > 0"
        variant="outline"
        size="lg"
        data-testid="reorder-button"
        @click="openReorder()"
      >
        <ArrowUpDown aria-hidden="true" />
        {{ $t('Keisti tvarką') }}
      </Button>
      <Button v-if="canCreate && !isTrash" as-child variant="brand" size="lg" data-testid="inline-create-button">
        <Link :href="createHref">
          <Plus aria-hidden="true" />
          {{ $t('Nauja nuoroda') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item, actions: recordActions }">
      <CollectionRow :actions="recordActions" :spotlight="item === source?.items.value[0]" class="px-3 py-2.5 sm:px-4" @select="key => selectAction(item, key)">
        <template #lead>
          <QuickLinkIconMark :icon="item.icon" />
        </template>
        <div class="min-w-0 flex-1">
          <CollectionPrimaryCell
            :title="item.text"
            :href="isTrash ? undefined : route('quickLinks.edit', item.id)"
            :sub="[tenantLabel(item.tenant_id), item.lang?.toUpperCase(), item.link].filter(Boolean).join(' · ')"
            mono
          />
        </div>
        <StatusBadge v-if="item.is_important" :status="importantStatus" class="shrink-0" />
      </CollectionRow>
    </template>

    <template #cell="{ item, column }">
      <div v-if="column.key === 'text'" class="flex items-center gap-3">
        <QuickLinkIconMark :icon="item.icon" />
        <CollectionPrimaryCell :title="item.text" :href="isTrash ? undefined : route('quickLinks.edit', item.id)" />
      </div>
      <span v-else-if="column.key === 'link'" class="font-mono text-xs text-muted-foreground">{{ item.link }}</span>
      <span v-else-if="column.key === 'tenant'" class="text-xs">{{ tenantLabel(item.tenant_id) }}</span>
      <span v-else-if="column.key === 'lang'" class="text-xs">{{ item.lang?.toUpperCase() }}</span>
      <StatusBadge v-else-if="column.key === 'important' && item.is_important" :status="importantStatus" />
      <span v-else-if="column.key === 'order'" class="tabular-nums text-muted-foreground">{{ item.order ?? '—' }}</span>
      <span v-else-if="column.key === 'created_at' || column.key === 'updated_at'" class="tabular-nums">{{ formatRecordDate(item[column.key]) }}</span>
    </template>

    <template v-if="!isTrash" #empty>
      <EmptyState
        :title="$t('Dar nėra greitųjų nuorodų')"
        :description="$t('Nuorodos ir mygtukai, rodomi pradiniame svetainės puslapyje.')"
        :action-label="canCreate ? $t('Sukurti pirmą nuorodą') : undefined"
        :action-href="canCreate ? createHref : undefined"
      />
    </template>
  </CollectionPage>

  <CollectionConfirmAction :dialog="actions.dialog.value" @confirm="actions.confirm" @cancel="actions.pending.value = null" />

  <SheetForm
    v-model:open="reorderOpen"
    :title="$t('Keisti tvarką')"
    :description="$t('Vilki elementus arba naudok rodyklių mygtukus eiliškumui keisti.')"
    :save-label="$t('Išsaugoti tvarką')"
    :processing="savingOrder"
    :dirty="orderChanged"
    :disabled="!orderChanged"
    @submit="saveOrder"
  >
    <div class="grid gap-4 sm:grid-cols-2">
      <FormFieldWrapper id="reorder-tenant" :label="$t('Padalinys')">
        <NativeSelect id="reorder-tenant" v-model="reorderTenantId" :options="reorderTenantOptions" :disabled="savingOrder" />
      </FormFieldWrapper>
      <FormFieldWrapper id="reorder-language" :label="$t('Kalba')">
        <NativeSelect id="reorder-language" v-model="reorderLang" :options="languageOptions" :disabled="savingOrder" />
      </FormFieldWrapper>
    </div>
    <ol ref="reorderList" class="divide-y divide-border border-y border-border" data-testid="reorder-list">
      <li
        v-for="(item, index) in orderedLinks"
        :key="item.id"
        class="flex items-center gap-2 bg-background py-2"
        data-testid="reorder-item"
      >
        <span
          class="handle flex size-9 shrink-0 cursor-grab items-center justify-center text-muted-foreground active:cursor-grabbing pointer-coarse:size-11"
          :aria-label="$t('Vilkti')"
        >
          <GripVertical class="size-4" aria-hidden="true" />
        </span>
        <span class="w-6 shrink-0 text-right text-xs tabular-nums text-muted-foreground">{{ index + 1 }}</span>
        <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ item.text }}</span>
        <Button
          type="button"
          variant="ghost"
          size="icon"
          class="pointer-coarse:size-11"
          :disabled="index === 0"
          :aria-label="$t('Pakelti aukštyn')"
          data-testid="move-up"
          @click="moveItem(index, -1)"
        >
          <ArrowUp class="size-4" />
        </Button>
        <Button
          type="button"
          variant="ghost"
          size="icon"
          class="pointer-coarse:size-11"
          :disabled="index === orderedLinks.length - 1"
          :aria-label="$t('Nuleisti žemyn')"
          data-testid="move-down"
          @click="moveItem(index, 1)"
        >
          <ArrowDown class="size-4" />
        </Button>
      </li>
    </ol>
  </SheetForm>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowDown, ArrowUp, ArrowUpDown, GripVertical, Link as LinkIcon, Plus, Star } from '@lucide/vue';
import { computed, h, ref, toRef, watch, type FunctionalComponent } from 'vue';

import { useMotionAwareSortable } from '@/Composables/useMotionAwareSortable';
import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import { quickFilterLabel } from '@/Components/Collection/quickFilterLabel';
import type { CollectionColumn, CollectionQuickFilter } from '@/Components/Collection/types';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { EmptyState, SheetForm, StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { NativeSelect } from '@/Components/ui/native-select';
import { useCollectionRecordActions } from '@/Composables/useCollectionRecordActions';
import { isTrashView, useLocalCollectionSource } from '@/Composables/useCollectionSource';
import type { StatusPresentation } from '@/Constants/statuses';
import { formatRecordDate } from '@/Utils/dateTime';

type QuickLinkRow = App.Entities.QuickLink;
interface TenantOption { id: number; shortname: string; type: string }

const props = defineProps<{
  quickLinks: QuickLinkRow[];
  tenant: TenantOption | null;
  tenants: TenantOption[];
  reorderTenants: TenantOption[];
  currentLang: string;
  showDeleted?: boolean;
  deletedCount?: number;
}>();

const page = usePage();
const isTrash = isTrashView();
const canCreate = computed(() => Boolean(page.props.auth?.can?.create?.quickLink ?? true));
const canForceDelete = computed(() => Boolean(page.props.auth?.can?.forceDelete?.quickLink));

const importantStatus: StatusPresentation = { label: $t('Svarbi'), role: 'attention', icon: Star };

/** The icon is CMS data (a Fluent name), so it resolves at runtime. */
const QuickLinkIconMark: FunctionalComponent<{ icon?: string | null }> = ({ icon }) => icon
  ? h(Icon, { 'icon': `fluent:${icon}`, 'class': 'size-5 shrink-0 text-muted-foreground', 'aria-hidden': 'true' })
  : h(LinkIcon, { 'class': 'size-5 shrink-0 text-muted-foreground', 'aria-hidden': 'true' });

const tenantLabel = (id: number) => props.tenants.find(tenant => tenant.id === id)?.shortname ?? '—';
const languageOptions = [{ value: 'lt', label: 'LT' }, { value: 'en', label: 'EN' }];
const reorderTenantOptions = computed(() => props.reorderTenants.map(tenant => ({ value: tenant.id, label: tenant.shortname })));
const selectedTenant = computed(() => (source.filters.value.tenant_id as string[] | undefined)?.[0]);
const selectedLanguage = computed(() => (source.filters.value.language as string[] | undefined)?.[0]);
const createHref = computed(() => route('quickLinks.create', {
  tenant: selectedTenant.value ?? props.tenant?.id,
  lang: selectedLanguage.value ?? props.currentLang,
}));
const languageFilters = computed<CollectionQuickFilter[]>(() => languageOptions.map(option => ({
  id: option.value,
  label: quickFilterLabel(option.label, props.quickLinks.filter(link => link.lang === option.value).length),
  active: (source.filters.value.language as string[] | undefined)?.includes(option.value) ?? false,
})));

// --- Collection ------------------------------------------------------------------------------

const source = useLocalCollectionSource<QuickLinkRow>({
  items: toRef(props, 'quickLinks'),
  searchText: link => [link.text, link.link],
  defaultSort: 'order:asc',
  facets: [
    { field: 'tenant_id', label: $t('Padalinys'), get: link => String(link.tenant_id), valueLabel: id => tenantLabel(Number(id)) },
    { field: 'language', label: $t('Kalba'), get: link => link.lang, valueLabel: lang => lang.toUpperCase() },
  ],
  sortOptions: () => [
    { value: 'updated_at:desc', label: `${$t('Atnaujinta')} ↓`, by: item => item.updated_at ? new Date(item.updated_at).getTime() : 0 },
    { value: 'updated_at:asc', label: `${$t('Atnaujinta')} ↑`, by: item => item.updated_at ? new Date(item.updated_at).getTime() : 0 },
    { value: 'created_at:desc', label: `${$t('Sukurta')} ↓`, by: item => item.created_at ? new Date(item.created_at).getTime() : 0 },
    { value: 'created_at:asc', label: `${$t('Sukurta')} ↑`, by: item => item.created_at ? new Date(item.created_at).getTime() : 0 },
    { value: 'order:asc', label: $t('Pagal tvarką'), by: link => link.order ?? 0 },
    { value: 'order:desc', label: $t('Pagal tvarką (atvirkščiai)'), by: link => link.order ?? 0 },
    { value: 'text:asc', label: $t('Pagal pavadinimą (A–Z)'), by: link => link.text },
    { value: 'text:desc', label: $t('Pagal pavadinimą (Z–A)'), by: link => link.text },
  ],
});

const actions = useCollectionRecordActions({
  source,
  routePrefix: 'quickLinks',
  canForceDelete: () => canForceDelete.value,
});
const actionsFor = (link: QuickLinkRow) => [
  ...actions.rowActions(link, link.text, isTrash),
  ...(!isTrash && props.reorderTenants.some(tenant => tenant.id === link.tenant_id)
    ? [{ key: 'reorder', label: $t('Keisti tvarką'), icon: ArrowUpDown, menuOnly: true }]
    : []),
];

function selectAction(link: QuickLinkRow, key: string): void {
  if (key === 'reorder') {
    openReorder(link);
  }
  else {
    actions.select(key);
  }
}

const columns = computed<CollectionColumn[]>(() => [
  { key: 'text', label: $t('Nuoroda'), sortField: 'text' },
  { key: 'tenant', label: $t('Padalinys'), class: 'w-36' },
  { key: 'lang', label: $t('Kalba'), class: 'w-20' },
  { key: 'link', label: $t('Adresas'), class: 'w-64' },
  { key: 'important', label: $t('Svarbi'), class: 'w-28' },
  { key: 'order', label: $t('Eilė'), class: 'w-20', sortField: 'order' },
  { key: 'created_at', label: $t('Sukurta'), class: 'w-36', sortField: 'created_at', defaultHidden: true },
  { key: 'updated_at', label: $t('Atnaujinta'), class: 'w-36', sortField: 'updated_at', defaultHidden: true },
]);

// --- Order is a mode (.ai/rules/admin-forms.md) ---------------------------------------------

const reorderOpen = ref(false);
const reorderList = ref<HTMLElement | null>(null);
const orderedLinks = ref<QuickLinkRow[]>([]);
const savingOrder = ref(false);
const reorderTenantId = ref<number | string>();
const reorderLang = ref('lt');

const scopedLinks = computed(() => props.quickLinks
  .filter(link => link.tenant_id === Number(reorderTenantId.value) && link.lang === reorderLang.value)
  .sort((a, b) => (a.order ?? 0) - (b.order ?? 0)));
const orderChanged = computed(() => orderedLinks.value.map(link => link.id).join() !== scopedLinks.value.map(link => link.id).join());

watch(scopedLinks, (links) => {
  orderedLinks.value = [...links];
});

useMotionAwareSortable(reorderList, orderedLinks, {
  handle: '.handle',
  forceFallback: true,
  // The list only exists while the sheet is open.
  watchElement: true,
  animation: 150,
});

function openReorder(link?: QuickLinkRow): void {
  const preferredTenant = link?.tenant_id ?? Number(selectedTenant.value ?? props.tenant?.id);
  reorderTenantId.value = props.reorderTenants.find(tenant => tenant.id === preferredTenant)?.id
    ?? props.reorderTenants[0]?.id;
  reorderLang.value = link?.lang ?? selectedLanguage.value ?? props.currentLang;
  if (!link && !scopedLinks.value.length) {
    const fallback = props.quickLinks.find(item => item.tenant_id === Number(reorderTenantId.value))
      ?? props.quickLinks.find(item => props.reorderTenants.some(tenant => tenant.id === item.tenant_id));
    if (fallback) {
      reorderTenantId.value = fallback.tenant_id;
      reorderLang.value = fallback.lang;
    }
  }
  orderedLinks.value = [...scopedLinks.value];
  reorderOpen.value = true;
}

function moveItem(index: number, direction: -1 | 1): void {
  const target = index + direction;

  if (target < 0 || target >= orderedLinks.value.length) {
    return;
  }

  const [item] = orderedLinks.value.splice(index, 1);
  orderedLinks.value.splice(target, 0, item);
}

function saveOrder(): void {
  if (!orderChanged.value || savingOrder.value) {
    return;
  }

  router.post(route('quickLinks.update-order'), {
    orderList: orderedLinks.value.map((link, index) => ({ id: link.id, order: index + 1 })),
    tenant_id: Number(reorderTenantId.value),
    lang: reorderLang.value,
  }, {
    preserveScroll: true,
    onStart: () => {
      savingOrder.value = true;
    },
    onSuccess: () => {
      reorderOpen.value = false;
    },
    onFinish: () => {
      savingOrder.value = false;
    },
  });
}
</script>
