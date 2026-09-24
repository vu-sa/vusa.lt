<template>
  <CollectionPage
    :source
    collection="quickLinks"
    entity-type="quick_link"
    :eyebrow="`${$t('shell.workspaces.website.title')} · ${$t('Greitosios nuorodos')}`"
    :title="isTrash ? $t('Ištrintos greitosios nuorodos') : $t('Greitosios nuorodos')"
    :lead="isTrash
      ? $t('Peržiūrėk ištrintas greitąsias nuorodas arba atkurk jas.')
      : $t('Nuorodos ir mygtukai, rodomi pradiniame svetainės puslapyje.')"
    default-view="rows"
    :available-views="['rows', 'table']"
    :item-key="link => String(link.id)"
    :columns
    :quick-filters="languageFilters"
    :trash="{ count: deletedCount ?? 0, active: isTrash }"
    :keep-params="['tenant', 'lang']"
    :search-placeholder="$t('Ieškoti nuorodų')"
    @quick-filter="changeScope({ lang: $event })"
  >
    <template #actions>
      <div v-if="tenants.length > 1" class="w-full sm:w-56">
        <SingleSelect
          :model-value="selectedTenant"
          :options="tenants"
          value-field="id"
          label-field="shortname"
          :aria-label="$t('Padalinys')"
          :placeholder="$t('Pasirinkti padalinį...')"
          @update:model-value="tenant => tenant && changeScope({ tenant: tenant.id })"
        />
      </div>
      <Button
        v-if="!isTrash && quickLinks.length > 1"
        variant="outline"
        size="lg"
        data-testid="reorder-button"
        @click="openReorder"
      >
        <ArrowUpDown aria-hidden="true" />
        {{ $t('Keisti tvarką') }}
      </Button>
      <Button v-if="canCreate && !isTrash" as-child variant="brand" size="lg" data-testid="inline-create-button">
        <Link :href="route('quickLinks.create')">
          <Plus aria-hidden="true" />
          {{ $t('Nauja nuoroda') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex min-h-14 items-center gap-3 px-3 py-2.5 sm:px-4">
        <QuickLinkIconMark :icon="item.icon" />
        <div class="min-w-0 flex-1">
          <CollectionPrimaryCell
            :title="item.text"
            :href="isTrash ? undefined : route('quickLinks.edit', item.id)"
            :sub="item.link"
            mono
          />
        </div>
        <StatusBadge v-if="item.is_important" :status="importantStatus" class="shrink-0" />
        <CollectionRowActions :actions="actionsFor(item)" @select="key => actions.select(key)" />
      </article>
    </template>

    <template #cell="{ item, column }">
      <div v-if="column.key === 'text'" class="flex items-center gap-3">
        <QuickLinkIconMark :icon="item.icon" />
        <CollectionPrimaryCell :title="item.text" :href="isTrash ? undefined : route('quickLinks.edit', item.id)" />
      </div>
      <span v-else-if="column.key === 'link'" class="font-mono text-xs text-muted-foreground">{{ item.link }}</span>
      <StatusBadge v-else-if="column.key === 'important' && item.is_important" :status="importantStatus" />
      <span v-else-if="column.key === 'order'" class="tabular-nums text-muted-foreground">{{ item.order ?? '—' }}</span>
      <CollectionRowActions v-else-if="column.key === 'actions'" :actions="actionsFor(item)" @select="key => actions.select(key)" />
    </template>

    <template v-if="!isTrash" #empty>
      <EmptyState
        :title="$t('Dar nėra greitųjų nuorodų')"
        :description="$t('Nuorodos ir mygtukai, rodomi pradiniame svetainės puslapyje.')"
        :action-label="canCreate ? $t('Sukurti pirmą nuorodą') : undefined"
        :action-href="canCreate ? route('quickLinks.create') : undefined"
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
    <p class="text-xs font-bold uppercase tracking-[0.18em] text-muted-foreground">
      {{ $t('Matoma vusa.lt') }} · {{ tenant?.shortname }} · {{ currentLang.toUpperCase() }}
    </p>
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
import { ArrowDown, ArrowUp, ArrowUpDown, GripVertical, Link as LinkIcon, Plus, Star } from 'lucide-vue-next';
import { useSortable } from '@vueuse/integrations/useSortable';
import { computed, h, ref, toRef, type FunctionalComponent } from 'vue';

import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import type { CollectionColumn, CollectionQuickFilter } from '@/Components/Collection/types';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { EmptyState, SheetForm, StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { SingleSelect } from '@/Components/ui/single-select';
import { useCollectionRecordActions } from '@/Composables/useCollectionRecordActions';
import { isTrashView, useLocalCollectionSource } from '@/Composables/useCollectionSource';
import type { StatusPresentation } from '@/Constants/statuses';

type QuickLinkRow = App.Entities.QuickLink;
interface TenantOption { id: number; shortname: string; type: string }

const props = defineProps<{
  quickLinks: QuickLinkRow[];
  tenant: App.Entities.Tenant | null;
  tenants: TenantOption[];
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

// --- Scope: tenant and language decide what the server sends, and what order means --------

const selectedTenant = computed(() => props.tenants.find(t => t.id === props.tenant?.id) ?? null);

const languageFilters = computed<CollectionQuickFilter[]>(() => [
  { id: 'lt', label: 'LT', active: props.currentLang === 'lt' },
  { id: 'en', label: 'EN', active: props.currentLang === 'en' },
]);

function changeScope(scope: { tenant?: number; lang?: string }): void {
  router.get(route('quickLinks.index'), {
    tenant: scope.tenant ?? props.tenant?.id,
    lang: scope.lang ?? props.currentLang,
    ...(isTrash ? { showDeleted: true } : {}),
  }, { preserveState: false });
}

// --- Collection ------------------------------------------------------------------------------

const source = useLocalCollectionSource<QuickLinkRow>({
  items: toRef(props, 'quickLinks'),
  searchText: link => [link.text, link.link],
  defaultSort: 'order:asc',
  sortOptions: [
    { value: 'order:asc', label: $t('Pagal tvarką'), by: link => link.order ?? 0 },
    { value: 'text:asc', label: $t('Pagal pavadinimą (A–Z)'), by: link => link.text },
    { value: 'text:desc', label: $t('Pagal pavadinimą (Z–A)'), by: link => link.text },
  ],
});

const actions = useCollectionRecordActions({
  routePrefix: 'quickLinks',
  canForceDelete: () => canForceDelete.value,
});
const actionsFor = (link: QuickLinkRow) => actions.rowActions(link, link.text, isTrash);

const columns = computed<CollectionColumn[]>(() => [
  { key: 'text', label: $t('Nuoroda'), sortField: 'text' },
  { key: 'link', label: $t('Adresas') },
  { key: 'important', label: $t('Svarbi'), class: 'w-28' },
  { key: 'order', label: $t('Eilė'), class: 'w-20', sortField: 'order' },
  { key: 'actions', label: $t('Veiksmai'), class: 'w-px text-right', pinned: true },
]);

// --- Order is a mode (.ai/rules/admin-forms.md) ---------------------------------------------

const reorderOpen = ref(false);
const reorderList = ref<HTMLElement | null>(null);
const orderedLinks = ref<QuickLinkRow[]>([]);
const savingOrder = ref(false);

const savedOrder = computed(() => [...props.quickLinks]
  .sort((a, b) => (a.order ?? 0) - (b.order ?? 0))
  .map(link => link.id));

const orderChanged = computed(() => orderedLinks.value.map(link => link.id).join() !== savedOrder.value.join());

useSortable(reorderList, orderedLinks, {
  handle: '.handle',
  forceFallback: true,
  // The list only exists while the sheet is open.
  watchElement: true,
  animation: 150,
});

function openReorder(): void {
  orderedLinks.value = savedOrder.value
    .map(id => props.quickLinks.find(link => link.id === id))
    .filter((link): link is QuickLinkRow => link !== undefined);
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
  router.post(route('quickLinks.update-order'), {
    orderList: orderedLinks.value.map((link, index) => ({ id: link.id, order: index + 1 })),
    tenant_id: props.tenant?.id,
    lang: props.currentLang,
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
