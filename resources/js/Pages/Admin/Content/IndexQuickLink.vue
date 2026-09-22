<template>
  <div class="mx-auto w-full px-4 py-6 sm:px-6">
    <!-- Header -->
    <header class="mb-6 space-y-1">
      <p class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
        {{ $t('shell.workspaces.website.title') }} · {{ $t('Greitosios nuorodos') }}
      </p>
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">
            {{ isDeleted ? $t('Ištrintos greitosios nuorodos') : $t('Greitosios nuorodos') }}
          </h1>
          <p class="mt-1 text-sm text-muted-foreground">
            {{ isDeleted ? $t('Peržiūrėk ištrintas greitąsias nuorodas arba atkurk jas.') : $t('Nuorodos ir mygtukai, rodomi pradiniame svetainės puslapyje.') }}
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <template v-if="!showDeleted">
            <template v-if="reorderMode">
              <Button variant="ghost" size="sm" @click="cancelReorder">
                <X class="mr-1.5 size-4" />
                {{ $t('Atšaukti') }}
              </Button>
              <Button variant="brand" size="sm" :disabled="!hasChanges" @click="handleOrderUpdate">
                <Save class="mr-1.5 size-4" />
                {{ $t('Išsaugoti tvarką') }}
              </Button>
            </template>
            <template v-else>
              <Button
                v-if="quickLinkList.length > 1"
                variant="outline"
                size="sm"
                @click="reorderMode = true"
              >
                <ArrowUpDown class="mr-1.5 size-4" />
                {{ $t('Keisti tvarką') }}
              </Button>
              <Button as-child variant="brand" size="sm" data-testid="inline-create-button">
                <Link :href="route('quickLinks.create')">
                  <Plus class="mr-1.5 size-4" />
                  {{ $t('Nauja nuoroda') }}
                </Link>
              </Button>
            </template>
          </template>
        </div>
      </div>
    </header>

    <!-- Filters & Trash Controls -->
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4 border-b border-border pb-4">
      <div class="flex flex-wrap items-end gap-4">
        <div v-if="tenantOptions.length > 1" class="min-w-[16rem] space-y-1">
          <label class="text-xs font-medium text-muted-foreground">{{ $t('Padalinys') }}</label>
          <SingleSelect
            v-model="selectedTenant"
            :options="tenantOptions"
            value-field="id"
            label-field="shortname"
            :placeholder="$t('Pasirinkti padalinį...')"
            @update:model-value="handleTenantChange"
          />
        </div>

        <div class="space-y-1">
          <label class="text-xs font-medium text-muted-foreground">{{ $t('Kalba') }}</label>
          <ToggleGroup
            :model-value="currentLang"
            type="single"
            class="justify-start"
            @update:model-value="val => val && handleLangChange(val as string)"
          >
            <ToggleGroupItem value="lt" class="px-3 text-xs font-medium">
              Lietuvių (LT)
            </ToggleGroupItem>
            <ToggleGroupItem value="en" class="px-3 text-xs font-medium">
              English (EN)
            </ToggleGroupItem>
          </ToggleGroup>
        </div>
      </div>

      <TrashViewToggle
        v-if="shouldShowDeletedToggle"
        :show-deleted
        :deleted-count
        @update:show-deleted="handleShowDeletedChange"
      />
    </div>

    <!-- Trash view alert -->
    <div
      v-if="showDeleted"
      :class="[
        'mb-6 flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between',
        'border border-[var(--status-attention-border)] bg-[var(--status-attention-surface)] text-[var(--status-attention)]',
      ]"
    >
      <div class="flex items-start gap-2.5">
        <Trash2 class="mt-0.5 size-4 shrink-0" />
        <div class="space-y-0.5">
          <p class="font-medium">
            {{ $t('trash.showing_deleted_only') }}
          </p>
          <p class="text-sm opacity-90">
            {{ $t('trash.showing_deleted_only_description') }}
          </p>
        </div>
      </div>
      <Button
        variant="outline"
        size="sm"
        class="shrink-0 bg-background"
        @click="handleShowDeletedChange(false)"
      >
        {{ $t('trash.exit_trash_view') }}
      </Button>
    </div>

    <!-- Empty State -->
    <div
      v-if="quickLinkList.length === 0"
      class="flex flex-col items-center justify-center border border-dashed border-border py-12 text-center"
    >
      <LinkIcon class="mb-3 size-10 text-muted-foreground" />
      <p class="text-sm text-muted-foreground">
        {{ showDeleted ? $t('trash.no_deleted_records') : $t('Dar nėra greitųjų nuorodų') }}
      </p>
      <Button
        v-if="!showDeleted"
        as-child
        variant="outline"
        size="sm"
        class="mt-4"
        data-testid="empty-create-button"
      >
        <Link :href="route('quickLinks.create')">
          <Plus class="mr-1.5 size-4" />
          {{ $t('Sukurti pirmą nuorodą') }}
        </Link>
      </Button>
    </div>

    <!-- List -->
    <div v-else class="space-y-2">
      <div v-if="reorderMode" class="border border-border bg-muted/40 p-2 text-xs text-muted-foreground">
        <span>{{ $t('Vilki elementus arba naudok rodyklių mygtukus eiliškumui keisti.') }}</span>
        <span class="ml-2 font-medium">{{ $t('Matoma vusa.lt') }}</span>
      </div>

      <TransitionGroup ref="el" tag="div" class="flex flex-col gap-2">
        <div
          v-for="(item, index) in quickLinkList"
          :key="item.id"
          class="group flex items-center gap-3 border border-border bg-card p-3 transition-colors hover:bg-secondary"
        >
          <!-- Reorder controls -->
          <div v-if="reorderMode && !showDeleted" class="flex items-center gap-1">
            <Button
              variant="ghost"
              size="icon-sm"
              class="handle shrink-0 cursor-grab active:cursor-grabbing text-muted-foreground hover:text-foreground"
              :title="$t('Vilkti')"
            >
              <GripVertical class="size-4" />
            </Button>
            <Button
              variant="ghost"
              size="icon-sm"
              :disabled="index === 0"
              :title="$t('Pakelti aukštyn')"
              @click="moveItem(index, -1)"
            >
              <ArrowUp class="size-4" />
            </Button>
            <Button
              variant="ghost"
              size="icon-sm"
              :disabled="index === quickLinkList.length - 1"
              :title="$t('Nuleisti žemyn')"
              @click="moveItem(index, 1)"
            >
              <ArrowDown class="size-4" />
            </Button>
          </div>

          <!-- Icon -->
          <Icon
            v-if="item.icon"
            :icon="`fluent:${item.icon}`"
            class="size-5 shrink-0 text-muted-foreground"
          />
          <LinkIcon v-else class="size-5 shrink-0 text-muted-foreground" />

          <!-- Details -->
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
              <span class="font-medium text-foreground">{{ item.text }}</span>
              <span
                v-if="item.is_important"
                class="border border-[var(--status-attention-border)] bg-[var(--status-attention-surface)] px-1.5 py-0.2 text-[10px] font-semibold text-[var(--status-attention)]"
              >
                {{ $t('Svarbi') }}
              </span>
            </div>
            <div class="truncate text-xs text-muted-foreground">
              {{ item.link }}
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-1">
            <template v-if="!showDeleted">
              <template v-if="!reorderMode">
                <Button as-child variant="ghost" size="icon-sm" :title="$t('Redaguoti')">
                  <Link :href="route('quickLinks.edit', item.id)">
                    <Pencil class="size-4" />
                  </Link>
                </Button>
                <Button
                  variant="ghost"
                  size="icon-sm"
                  class="text-destructive hover:text-destructive"
                  :title="$t('Ištrinti')"
                  @click="confirmDelete(() => handleDelete(item.id))"
                >
                  <Trash2 class="size-4" />
                </Button>
              </template>
            </template>
            <template v-else>
              <Button
                variant="ghost"
                size="sm"
                class="gap-1.5"
                data-testid="restore-button"
                @click="handleRestore(item.id)"
              >
                <RotateCcw class="size-4" />
                {{ $t('trash.restore') }}
              </Button>
              <Button
                v-if="canForceDelete"
                variant="ghost"
                size="sm"
                class="gap-1.5 text-destructive hover:text-destructive"
                data-testid="force-delete-button"
                @click="openForceDeleteDialog(item)"
              >
                <Trash2 class="size-4" />
                {{ $t('trash.permanently_delete') }}
              </Button>
            </template>
          </div>
        </div>
      </TransitionGroup>
    </div>

    <!-- Dialogs -->
    <DeleteConfirmationDialog
      v-model:is-open="isOpen"
      :title="deleteTitle"
      :message="deleteMessage"
      :is-deleting
      @confirm="executeDelete"
      @cancel="cancelDelete"
    />

    <ConfirmDangerousActionDialog
      v-model:open="isForceDeleteDialogOpen"
      :title="$t('trash.permanently_delete')"
      :description="$t('trash.permanently_delete_description')"
      :confirmation-text="forceDeleteConfirmationText"
      :confirm-label="$t('trash.permanently_delete')"
      @confirm="handleForceDelete"
    />
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useSortable } from '@vueuse/integrations/useSortable';
import {
  ArrowDown,
  ArrowUp,
  ArrowUpDown,
  GripVertical,
  Link as LinkIcon,
  Pencil,
  Plus,
  RotateCcw,
  Save,
  Trash2,
  X,
} from 'lucide-vue-next';

import DeleteConfirmationDialog from '@/Components/Dialogs/DeleteConfirmationDialog.vue';
import ConfirmDangerousActionDialog from '@/Components/ui/data-table/ConfirmDangerousActionDialog.vue';
import { Button } from '@/Components/ui/button';
import { SingleSelect } from '@/Components/ui/single-select';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import TrashViewToggle from '@/Components/Tables/TrashViewToggle.vue';
import { useDeleteConfirmation } from '@/Composables/useDeleteConfirmation';

interface QuickLinkListItem {
  id: number;
  text: string;
  link: string;
  icon?: string | null;
  order?: number | null;
  is_important?: boolean;
}

const props = defineProps<{
  quickLinks: App.Entities.QuickLink[];
  tenant: App.Entities.Tenant | null;
  tenants: Array<{ id: number; shortname: string; type: string }>;
  currentLang: string;
  showDeleted?: boolean;
  deletedCount?: number;
}>();

const el = ref<HTMLElement | null>(null);
const itemPendingForceDelete = ref<QuickLinkListItem | null>(null);
const isForceDeleteDialogOpen = ref(false);
const reorderMode = ref(false);
const page = usePage();

const quickLinkList = ref<QuickLinkListItem[]>(
  props.quickLinks.map(quickLink => ({
    id: quickLink.id,
    text: quickLink.text,
    link: quickLink.link,
    icon: quickLink.icon,
    order: quickLink.order,
    is_important: quickLink.is_important,
  })),
);

const showDeleted = computed(() => Boolean(props.showDeleted));
const isDeleted = computed(() => showDeleted.value);
const deletedCount = computed(() => props.deletedCount ?? 0);
const hasDeletedCount = computed(() => deletedCount.value > 0);
const shouldShowDeletedToggle = computed(() => showDeleted.value || hasDeletedCount.value);
const canForceDelete = computed(() => (page.props.auth?.can as { forceDelete?: Record<string, boolean> } | undefined)?.forceDelete?.quickLink ?? false);
const forceDeleteConfirmationText = computed(() => itemPendingForceDelete.value?.text?.trim() || String(itemPendingForceDelete.value?.id ?? ''));

const initialOrder = computed(() => props.quickLinks.map(q => q.id));

const hasChanges = computed(() => {
  const currentOrder = quickLinkList.value.map(q => q.id);
  return JSON.stringify(currentOrder) !== JSON.stringify(initialOrder.value);
});

useSortable(el, quickLinkList, {
  handle: '.handle',
  forceFallback: true,
  animation: 150,
});

watch(() => props.quickLinks, (newLinks) => {
  quickLinkList.value = newLinks.map(quickLink => ({
    id: quickLink.id,
    text: quickLink.text,
    link: quickLink.link,
    icon: quickLink.icon,
    order: quickLink.order,
    is_important: quickLink.is_important,
  }));
}, { deep: true });

watch(() => quickLinkList.value, () => {
  quickLinkList.value.forEach((item, index) => {
    item.order = index + 1;
  });
}, { deep: true });

const tenantOptions = computed(() => props.tenants);

const selectedTenant = computed({
  get: () => props.tenant ? tenantOptions.value.find(t => t.id === props.tenant.id) ?? null : null,
  set: () => {},
});

function moveItem(index: number, direction: -1 | 1): void {
  const targetIndex = index + direction;
  if (targetIndex < 0 || targetIndex >= quickLinkList.value.length) return;
  const item = quickLinkList.value[index];
  quickLinkList.value.splice(index, 1);
  quickLinkList.value.splice(targetIndex, 0, item);
}

function cancelReorder(): void {
  reorderMode.value = false;
  quickLinkList.value = props.quickLinks.map(quickLink => ({
    id: quickLink.id,
    text: quickLink.text,
    link: quickLink.link,
    icon: quickLink.icon,
    order: quickLink.order,
    is_important: quickLink.is_important,
  }));
}

function handleTenantChange(tenant: { id: number; shortname: string; type: string } | null) {
  if (tenant) {
    router.get(route('quickLinks.index'), { tenant: tenant.id, lang: props.currentLang, showDeleted: showDeleted.value }, { preserveState: false });
  }
}

function handleLangChange(lang: string) {
  router.get(route('quickLinks.index'), { tenant: props.tenant?.id, lang, showDeleted: showDeleted.value }, { preserveState: false });
}

function handleShowDeletedChange(checked: boolean) {
  router.get(route('quickLinks.index'), { tenant: props.tenant?.id, lang: props.currentLang, showDeleted: checked }, {
    preserveScroll: true,
    preserveState: false,
  });
}

function handleOrderUpdate() {
  const orderList = quickLinkList.value.map((item, index) => ({
    id: item.id,
    order: index + 1,
  }));

  router.post(route('quickLinks.update-order'), {
    orderList,
    tenant_id: props.tenant?.id,
    lang: props.currentLang,
  }, {
    onSuccess: () => {
      reorderMode.value = false;
    },
  });
}

function handleDelete(id: number) {
  router.delete(route('quickLinks.destroy', id), {
    preserveScroll: true,
    preserveState: true,
  });
}

function handleRestore(id: number) {
  router.patch(route('quickLinks.restore', id), {}, {
    preserveScroll: true,
  });
}

function openForceDeleteDialog(item: QuickLinkListItem) {
  itemPendingForceDelete.value = item;
  isForceDeleteDialogOpen.value = true;
}

function handleForceDelete() {
  if (!itemPendingForceDelete.value) {
    return;
  }

  router.delete(route('quickLinks.forceDelete', itemPendingForceDelete.value.id), {
    preserveScroll: true,
  });
}

const {
  isOpen,
  isDeleting,
  title: deleteTitle,
  message: deleteMessage,
  confirmDelete,
  executeDelete,
  cancelDelete,
} = useDeleteConfirmation({
  title: 'Ištrinti greitąją nuorodą?',
  message: 'Ar tikrai norite ištrinti šią greitąją nuorodą? Šis veiksmas neatšaukiamas.',
  preserveScroll: true,
  preserveState: true,
});
</script>
